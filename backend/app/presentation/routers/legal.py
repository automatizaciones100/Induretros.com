"""
Páginas legales editables (garantía, términos, privacidad…). Cada página
se identifica por slug. La consulta pública es por slug; el admin tiene
upsert por slug + listado.
"""
import re
from datetime import datetime
from typing import Optional
from fastapi import APIRouter, Depends, HTTPException, Request
from pydantic import BaseModel, Field, field_validator
from sqlalchemy.orm import Session
import bleach

from app.database import get_db
from app.infrastructure.database.models.legal_page_model import LegalPageModel
from app.presentation.dependencies import get_current_admin
from app.presentation.rate_limiter import limiter
from app.infrastructure.logging.security_logger import log_admin_action

router = APIRouter(prefix="/api/legal", tags=["legal"])

# Tags permitidas en el contenido HTML de páginas legales. Más amplio que
# FAQ porque queremos encabezados y formateo enriquecido.
_ALLOWED_TAGS = [
    "h2", "h3", "h4", "p", "br",
    "strong", "em", "u",
    "ul", "ol", "li",
    "a", "blockquote", "hr",
]
_ALLOWED_ATTRS = {"a": ["href", "title", "target", "rel"]}

_SLUG_RE = re.compile(r"^[a-z0-9]+(?:-[a-z0-9]+)*$")


class LegalPageBody(BaseModel):
    title: str = Field(..., min_length=1, max_length=200)
    content: str = Field(..., min_length=1, max_length=50000)

    @field_validator("title", mode="before")
    @classmethod
    def strip_html_title(cls, v):
        if v is None:
            return v
        return bleach.clean(str(v), tags=[], attributes={}, strip=True)

    @field_validator("content", mode="before")
    @classmethod
    def sanitize_content(cls, v):
        if v is None:
            return v
        return bleach.clean(str(v), tags=_ALLOWED_TAGS, attributes=_ALLOWED_ATTRS, strip=True)


class LegalPageCreateBody(LegalPageBody):
    slug: str = Field(..., min_length=2, max_length=50)

    @field_validator("slug", mode="before")
    @classmethod
    def validate_slug(cls, v):
        s = str(v).strip().lower()
        if not _SLUG_RE.match(s):
            raise ValueError("slug debe ser kebab-case (a-z, 0-9, '-')")
        return s


def _to_dict(p: LegalPageModel) -> dict:
    return {
        "id": p.id,
        "slug": p.slug,
        "title": p.title,
        "content": p.content,
        "updated_at": p.updated_at.isoformat() if p.updated_at else None,
    }


# ───────── Público ─────────

@router.get("/{slug}")
def get_page(slug: str, db: Session = Depends(get_db)):
    page = db.query(LegalPageModel).filter(LegalPageModel.slug == slug).first()
    if not page:
        raise HTTPException(status_code=404, detail="Página no encontrada")
    return _to_dict(page)


# ───────── Admin ─────────

@router.get("/admin/all")
@limiter.limit("60/minute")
def list_all_admin(
    request: Request,
    db: Session = Depends(get_db),
    _admin: dict = Depends(get_current_admin),
):
    pages = db.query(LegalPageModel).order_by(LegalPageModel.slug).all()
    return [_to_dict(p) for p in pages]


@router.post("", status_code=201)
@limiter.limit("30/minute")
def create_page(
    request: Request,
    body: LegalPageCreateBody,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    if db.query(LegalPageModel).filter(LegalPageModel.slug == body.slug).first():
        raise HTTPException(status_code=409, detail=f"Ya existe una página con slug '{body.slug}'")
    page = LegalPageModel(slug=body.slug, title=body.title, content=body.content)
    db.add(page)
    db.commit()
    db.refresh(page)
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="create_legal_page",
        resource=f"legal:{page.slug}",
        ip=ip,
    )
    return _to_dict(page)


@router.put("/{slug}")
@limiter.limit("60/minute")
def update_page(
    slug: str,
    body: LegalPageBody,
    request: Request,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    page = db.query(LegalPageModel).filter(LegalPageModel.slug == slug).first()
    if not page:
        raise HTTPException(status_code=404, detail="Página no encontrada")
    page.title = body.title
    page.content = body.content
    # SQLite no dispara onupdate sin un cambio explícito en algunos drivers
    page.updated_at = datetime.utcnow()
    db.commit()
    db.refresh(page)
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="update_legal_page",
        resource=f"legal:{page.slug}",
        ip=ip,
    )
    return _to_dict(page)


@router.delete("/{slug}", status_code=204)
@limiter.limit("30/minute")
def delete_page(
    slug: str,
    request: Request,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    page = db.query(LegalPageModel).filter(LegalPageModel.slug == slug).first()
    if not page:
        raise HTTPException(status_code=404, detail="Página no encontrada")
    db.delete(page)
    db.commit()
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="delete_legal_page",
        resource=f"legal:{slug}",
        ip=ip,
    )
