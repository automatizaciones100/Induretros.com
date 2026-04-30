"""
CRUD de banners promocionales / barra de anuncios.
Endpoint público GET retorna SOLO los activos no expirados, ordenados por
prioridad. El frontend muestra el primero (o varios apilados según diseño).
"""
from typing import Optional, Literal
from datetime import datetime, timezone
from fastapi import APIRouter, Depends, HTTPException, Request
from pydantic import BaseModel, Field, field_validator
from sqlalchemy.orm import Session
from sqlalchemy import or_
import bleach

from app.database import get_db
from app.infrastructure.database.models.announcement_model import AnnouncementModel
from app.presentation.dependencies import get_current_admin
from app.presentation.rate_limiter import limiter
from app.infrastructure.logging.security_logger import log_admin_action

router = APIRouter(prefix="/api/announcements", tags=["announcements"])

ALLOWED_THEMES = {"info", "promo", "warning", "success", "alert", "dark"}


class AnnouncementBody(BaseModel):
    text: str = Field(..., min_length=1, max_length=300)
    link_url: Optional[str] = Field(None, max_length=300)
    link_text: Optional[str] = Field(None, max_length=50)
    theme: Literal["info", "promo", "warning", "success", "alert", "dark"] = "dark"
    active: bool = True
    dismissible: bool = True
    expires_at: Optional[datetime] = None
    priority: int = Field(0, ge=0, le=999)

    @field_validator("text", "link_text", mode="before")
    @classmethod
    def strip_html(cls, v):
        if v is None:
            return v
        return bleach.clean(str(v), tags=[], attributes={}, strip=True)

    @field_validator("link_url", mode="before")
    @classmethod
    def validate_url(cls, v):
        if v is None or v == "":
            return None
        v = str(v).strip()
        if not (v.startswith("https://") or v.startswith("http://") or v.startswith("/")):
            raise ValueError("link_url debe empezar con http://, https:// o / (interno)")
        return v


def _to_dict(a: AnnouncementModel) -> dict:
    return {
        "id": a.id,
        "text": a.text,
        "link_url": a.link_url,
        "link_text": a.link_text,
        "theme": a.theme,
        "active": a.active,
        "dismissible": a.dismissible,
        "expires_at": a.expires_at.isoformat() if a.expires_at else None,
        "priority": a.priority,
    }


# ───────── Público (consumido por el sitio) ─────────

@router.get("")
def list_active(db: Session = Depends(get_db)):
    """
    Solo retorna anuncios activos y no expirados, ordenados por priority.
    El frontend muestra el primero (o varios stack según el diseño).
    """
    now = datetime.now(timezone.utc)
    items = (
        db.query(AnnouncementModel)
        .filter(AnnouncementModel.active == True)  # noqa: E712
        .filter(or_(AnnouncementModel.expires_at == None, AnnouncementModel.expires_at > now))  # noqa: E711
        .order_by(AnnouncementModel.priority, AnnouncementModel.id.desc())
        .all()
    )
    return [_to_dict(a) for a in items]


# ───────── Admin (CRUD) ─────────

@router.get("/admin/all")
@limiter.limit("60/minute")
def list_all_admin(
    request: Request,
    db: Session = Depends(get_db),
    _admin: dict = Depends(get_current_admin),
):
    items = (
        db.query(AnnouncementModel)
        .order_by(AnnouncementModel.priority, AnnouncementModel.id.desc())
        .all()
    )
    return [_to_dict(a) for a in items]


@router.post("", status_code=201)
@limiter.limit("30/minute")
def create_announcement(
    request: Request,
    body: AnnouncementBody,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    item = AnnouncementModel(**body.model_dump())
    db.add(item)
    db.commit()
    db.refresh(item)
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="create_announcement",
        resource=f"announcement:{item.id}",
        ip=ip,
    )
    return _to_dict(item)


@router.put("/{ann_id}")
@limiter.limit("60/minute")
def update_announcement(
    ann_id: int,
    body: AnnouncementBody,
    request: Request,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    item = db.query(AnnouncementModel).filter(AnnouncementModel.id == ann_id).first()
    if not item:
        raise HTTPException(status_code=404, detail="Anuncio no encontrado")
    for key, value in body.model_dump().items():
        setattr(item, key, value)
    db.commit()
    db.refresh(item)
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="update_announcement",
        resource=f"announcement:{item.id}",
        ip=ip,
    )
    return _to_dict(item)


@router.delete("/{ann_id}", status_code=204)
@limiter.limit("30/minute")
def delete_announcement(
    ann_id: int,
    request: Request,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    item = db.query(AnnouncementModel).filter(AnnouncementModel.id == ann_id).first()
    if not item:
        raise HTTPException(status_code=404, detail="Anuncio no encontrado")
    db.delete(item)
    db.commit()
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="delete_announcement",
        resource=f"announcement:{ann_id}",
        ip=ip,
    )
