"""
Historial de cambios y rollback (Hito 5.10).

Permite a un admin:
  - Listar todos los cambios sobre entidades editables (paginado, con filtros).
  - Restaurar un cambio específico:
      * UPDATE → revierte campos a `before_json`
      * DELETE → recrea la entidad desde `before_json`
      * CREATE → elimina la entidad creada
  - Cada restauración genera su propia entrada de tipo 'restore' (auto-auditable).
"""
import json
from datetime import datetime
from typing import Optional
from fastapi import APIRouter, Depends, HTTPException, Query, Request
from sqlalchemy.orm import Session

from app.database import get_db
from app.infrastructure.database.models.change_log_model import ChangeLogModel
from app.infrastructure.database.models.why_us_model import WhyUsItemModel
from app.infrastructure.database.models.faq_model import FaqItemModel
from app.infrastructure.database.models.testimonial_model import TestimonialModel
from app.infrastructure.database.models.home_stat_model import HomeStatModel
from app.infrastructure.database.models.announcement_model import AnnouncementModel
from app.infrastructure.database.models.legal_page_model import LegalPageModel
from app.infrastructure.database.models.site_settings_model import SiteSettingsModel
from app.presentation.dependencies import get_current_admin
from app.presentation.rate_limiter import limiter
from app.infrastructure.audit.change_log import record_change

router = APIRouter(prefix="/api/admin/changes", tags=["changes"])


# ╭───────────────────────────────────────────────────────────────╮
# │  Registry de entidades restaurables                            │
# │                                                                │
# │  Cada entrada conoce:                                          │
# │    - el modelo SQLAlchemy                                      │
# │    - el campo PK ('id' numérico o 'slug')                      │
# │    - los campos restaurables (whitelist; excluye created_at,   │
# │      updated_at e id auto-incremental)                         │
# │    - una función para extraer un nombre legible para la UI     │
# ╰───────────────────────────────────────────────────────────────╯
REGISTRY: dict = {
    "why_us": {
        "model": WhyUsItemModel,
        "pk": "id",
        "fields": ["title", "description", "icon", "position", "active"],
        "label": lambda d: d.get("title", "—"),
    },
    "faq": {
        "model": FaqItemModel,
        "pk": "id",
        "fields": ["question", "answer", "category", "position", "active"],
        "label": lambda d: d.get("question", "—"),
    },
    "testimonial": {
        "model": TestimonialModel,
        "pk": "id",
        "fields": ["client_name", "client_company", "comment", "rating", "photo_url", "position", "active"],
        "label": lambda d: d.get("client_name", "—"),
    },
    "home_stat": {
        "model": HomeStatModel,
        "pk": "id",
        "fields": ["position", "value", "label", "icon", "active"],
        "label": lambda d: f"{d.get('value', '')} {d.get('label', '')}".strip() or "—",
    },
    "announcement": {
        "model": AnnouncementModel,
        "pk": "id",
        "fields": ["text", "link_url", "link_text", "theme", "active", "dismissible", "expires_at", "priority"],
        "label": lambda d: (d.get("text") or "—")[:60],
    },
    "legal_page": {
        "model": LegalPageModel,
        "pk": "slug",
        "fields": ["title", "content"],
        "label": lambda d: d.get("title") or d.get("slug") or "—",
    },
    "site_settings": {
        "model": SiteSettingsModel,
        "pk": "id",
        # Singleton — restauramos todos los campos editables
        "fields": [
            "site_title", "title_template", "default_description", "default_keywords",
            "default_og_image", "twitter_handle",
            "organization_name", "organization_phone",
            "contact_email", "contact_address", "contact_business_hours",
            "whatsapp_number",
            "facebook_url", "instagram_url", "youtube_url", "tiktok_url", "linkedin_url",
            "hero_label", "hero_title", "hero_subtitle",
            "hero_cta_text", "hero_cta_url", "hero_cta2_text", "hero_cta2_url",
            "hero_image_url",
        ],
        "label": lambda d: "Configuración del sitio",
    },
}


def _entry_to_dict(c: ChangeLogModel) -> dict:
    spec = REGISTRY.get(c.entity_type, {})
    label_fn = spec.get("label", lambda _d: "—")
    snapshot = c.after_json or c.before_json
    name = "—"
    try:
        if snapshot:
            name = label_fn(json.loads(snapshot))
    except (ValueError, TypeError):
        pass
    return {
        "id": c.id,
        "entity_type": c.entity_type,
        "entity_id": c.entity_id,
        "entity_label": name,
        "action": c.action,
        "before": json.loads(c.before_json) if c.before_json else None,
        "after": json.loads(c.after_json) if c.after_json else None,
        "user_id": c.user_id,
        "user_email": c.user_email,
        "ip": c.ip,
        "restored": c.restored,
        "restored_at": c.restored_at.isoformat() if c.restored_at else None,
        "created_at": c.created_at.isoformat() if c.created_at else None,
        "restorable": _is_restorable(c),
    }


def _is_restorable(c: ChangeLogModel) -> bool:
    if c.restored:
        return False
    if c.entity_type not in REGISTRY:
        return False
    if c.action not in ("create", "update", "delete"):
        return False
    return True


# ───────── Listado ─────────

@router.get("")
@limiter.limit("60/minute")
def list_changes(
    request: Request,
    entity_type: Optional[str] = Query(None),
    limit: int = Query(50, ge=1, le=200),
    offset: int = Query(0, ge=0),
    db: Session = Depends(get_db),
    _admin: dict = Depends(get_current_admin),
):
    q = db.query(ChangeLogModel)
    if entity_type:
        q = q.filter(ChangeLogModel.entity_type == entity_type)
    total = q.count()
    items = q.order_by(ChangeLogModel.created_at.desc()).offset(offset).limit(limit).all()
    return {
        "total": total,
        "limit": limit,
        "offset": offset,
        "items": [_entry_to_dict(c) for c in items],
    }


# ───────── Restaurar ─────────

def _convert_value(field_name: str, value):
    """Convierte strings ISO a datetime para campos de tiempo. Idempotente."""
    if value is None:
        return None
    if field_name == "expires_at" and isinstance(value, str):
        try:
            return datetime.fromisoformat(value.replace("Z", "+00:00"))
        except ValueError:
            return None
    return value


def _apply_state(obj, state: dict, fields: list):
    for field in fields:
        if field in state:
            setattr(obj, field, _convert_value(field, state[field]))


def _filter_by_pk(model, pk: str, value):
    pk_col = getattr(model, pk)
    if pk == "id":
        try:
            value = int(value)
        except (TypeError, ValueError):
            return None
    return pk_col == value


@router.post("/{change_id}/restore", status_code=200)
@limiter.limit("20/minute")
def restore_change(
    change_id: int,
    request: Request,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    entry = db.query(ChangeLogModel).filter(ChangeLogModel.id == change_id).first()
    if not entry:
        raise HTTPException(status_code=404, detail="Cambio no encontrado")
    if entry.restored:
        raise HTTPException(status_code=409, detail="Este cambio ya fue restaurado anteriormente")

    spec = REGISTRY.get(entry.entity_type)
    if spec is None:
        raise HTTPException(status_code=400, detail=f"Tipo de entidad no soportado: {entry.entity_type}")

    model = spec["model"]
    pk = spec["pk"]
    fields = spec["fields"]

    before = json.loads(entry.before_json) if entry.before_json else None
    after = json.loads(entry.after_json) if entry.after_json else None

    pk_filter = _filter_by_pk(model, pk, entry.entity_id)
    if pk_filter is None:
        raise HTTPException(status_code=400, detail="entity_id inválido")

    new_before = None
    new_after = None
    new_action = "restore"

    if entry.action == "create":
        # Deshacer un create → eliminar la entidad si todavía existe
        obj = db.query(model).filter(pk_filter).first()
        if obj is None:
            raise HTTPException(status_code=409, detail="La entidad ya no existe; no hay nada que deshacer")
        new_before = after  # estado actual antes de borrar
        db.delete(obj)
        db.commit()
        new_after = None

    elif entry.action == "update":
        if before is None:
            raise HTTPException(status_code=400, detail="No hay estado anterior para restaurar")
        obj = db.query(model).filter(pk_filter).first()
        if obj is None:
            # Fue borrada después → la recreamos con before
            obj = model()
            if pk == "slug":
                obj.slug = entry.entity_id
            elif pk == "id":
                # Para mantener el mismo id, lo asignamos explícitamente
                obj.id = int(entry.entity_id)
            _apply_state(obj, before, fields)
            db.add(obj)
            new_before = None
        else:
            new_before = {f: getattr(obj, f, None) for f in fields}
            # Convertir datetimes a ISO para JSON
            for k, v in list(new_before.items()):
                if isinstance(v, datetime):
                    new_before[k] = v.isoformat()
            _apply_state(obj, before, fields)
        db.commit()
        db.refresh(obj)
        new_after = {f: getattr(obj, f, None) for f in fields}
        for k, v in list(new_after.items()):
            if isinstance(v, datetime):
                new_after[k] = v.isoformat()

    elif entry.action == "delete":
        if before is None:
            raise HTTPException(status_code=400, detail="No hay estado anterior para restaurar")
        existing = db.query(model).filter(pk_filter).first()
        if existing is not None:
            raise HTTPException(status_code=409, detail="Ya existe una entidad con ese identificador")
        obj = model()
        if pk == "slug":
            obj.slug = entry.entity_id
        elif pk == "id":
            obj.id = int(entry.entity_id)
        _apply_state(obj, before, fields)
        db.add(obj)
        db.commit()
        db.refresh(obj)
        new_before = None
        new_after = {f: getattr(obj, f, None) for f in fields}
        for k, v in list(new_after.items()):
            if isinstance(v, datetime):
                new_after[k] = v.isoformat()
    else:
        raise HTTPException(status_code=400, detail=f"Acción no restaurable: {entry.action}")

    # Marcar la entrada original como restaurada
    entry.restored = True
    entry.restored_at = datetime.utcnow()
    db.commit()

    # Registrar la restauración como un cambio nuevo (auditable)
    record_change(
        db,
        entry.entity_type,
        entry.entity_id,
        new_action,
        new_before,
        new_after,
        admin,
        request,
    )

    return {"ok": True, "change_id": change_id, "entity_type": entry.entity_type, "entity_id": entry.entity_id}
