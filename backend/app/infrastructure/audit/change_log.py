"""
Helper para registrar cambios en entidades editables del admin con
snapshots before/after que permitan rollback (Hito 5.10).

Uso típico desde un router:

    from app.infrastructure.audit.change_log import record_change

    # Update:
    before = serialize_xxx(obj)        # antes de mutar
    obj.title = body.title
    db.commit()
    db.refresh(obj)
    after = serialize_xxx(obj)
    record_change(db, "why_us", str(obj.id), "update", before, after, admin, request)

Cada llamada también se replica al security_logger (stream JSON para SIEM).
"""
import json
from typing import Any, Optional
from sqlalchemy.orm import Session
from fastapi import Request

from app.infrastructure.database.models.change_log_model import ChangeLogModel
from app.infrastructure.logging.security_logger import log_admin_action


def _to_json(value: Optional[Any]) -> Optional[str]:
    if value is None:
        return None
    try:
        return json.dumps(value, default=str, ensure_ascii=False)
    except (TypeError, ValueError):
        return None


def _client_ip(request: Optional[Request]) -> str:
    if request is None or request.client is None:
        return "unknown"
    return request.client.host


def record_change(
    db: Session,
    entity_type: str,
    entity_id: str,
    action: str,
    before: Optional[dict],
    after: Optional[dict],
    admin: dict,
    request: Optional[Request] = None,
) -> ChangeLogModel:
    """
    Registra un cambio en la tabla change_log y lo replica al SIEM.

    `action` debe ser uno de: 'create', 'update', 'delete', 'restore'.
    `before` y `after` son los dicts JSON-serializables del estado.
        - create:  before=None,   after=<estado>
        - update:  before=<prev>, after=<nuevo>
        - delete:  before=<prev>, after=None
        - restore: before=<prev>, after=<restaurado>
    """
    user_id = int(admin.get("sub", 0)) if admin else 0
    user_email = admin.get("email") if admin else None
    ip = _client_ip(request)

    entry = ChangeLogModel(
        entity_type=entity_type,
        entity_id=str(entity_id),
        action=action,
        before_json=_to_json(before),
        after_json=_to_json(after),
        user_id=user_id or None,
        user_email=user_email,
        ip=ip,
    )
    db.add(entry)
    db.commit()
    db.refresh(entry)

    # Replicar al stream de seguridad (compatibilidad con SIEM existente)
    log_admin_action(
        user_id=user_id,
        action=f"{action}_{entity_type}",
        resource=f"{entity_type}:{entity_id}",
        ip=ip,
    )
    return entry
