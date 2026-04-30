"""
CRUD de estadísticas del home (los 4 contadores con ícono).
Endpoint público GET para que el home las consuma; el resto requiere admin.
"""
from typing import Optional
from fastapi import APIRouter, Depends, HTTPException, Request
from pydantic import BaseModel, Field, field_validator
from sqlalchemy.orm import Session
import bleach

from app.database import get_db
from app.infrastructure.database.models.home_stat_model import HomeStatModel
from app.presentation.dependencies import get_current_admin
from app.presentation.rate_limiter import limiter
from app.infrastructure.logging.security_logger import log_admin_action

router = APIRouter(prefix="/api/home-stats", tags=["home-stats"])


class HomeStatBody(BaseModel):
    position: int = Field(0, ge=0, le=999)
    value: str = Field(..., min_length=1, max_length=20)
    label: str = Field(..., min_length=1, max_length=100)
    icon: Optional[str] = Field(None, max_length=50)
    active: bool = True

    @field_validator("value", "label", "icon", mode="before")
    @classmethod
    def strip_html(cls, v):
        if v is None:
            return v
        return bleach.clean(str(v), tags=[], attributes={}, strip=True)


def _to_dict(s: HomeStatModel) -> dict:
    return {
        "id": s.id,
        "position": s.position,
        "value": s.value,
        "label": s.label,
        "icon": s.icon,
        "active": s.active,
    }


# ───────── Público (consumido por el home) ─────────

@router.get("")
def list_active_stats(db: Session = Depends(get_db)):
    """Solo retorna los stats activos, ordenados por position."""
    stats = (
        db.query(HomeStatModel)
        .filter(HomeStatModel.active == True)  # noqa: E712
        .order_by(HomeStatModel.position, HomeStatModel.id)
        .all()
    )
    return [_to_dict(s) for s in stats]


# ───────── Admin (CRUD) ─────────

@router.get("/admin/all")
@limiter.limit("60/minute")
def list_all_admin(
    request: Request,
    db: Session = Depends(get_db),
    _admin: dict = Depends(get_current_admin),
):
    """Retorna TODOS los stats (incluso inactivos) para el panel admin."""
    stats = db.query(HomeStatModel).order_by(HomeStatModel.position, HomeStatModel.id).all()
    return [_to_dict(s) for s in stats]


@router.post("", status_code=201)
@limiter.limit("30/minute")
def create_stat(
    request: Request,
    body: HomeStatBody,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    stat = HomeStatModel(
        position=body.position,
        value=body.value,
        label=body.label,
        icon=body.icon,
        active=body.active,
    )
    db.add(stat)
    db.commit()
    db.refresh(stat)
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="create_home_stat",
        resource=f"stat:{stat.id}",
        ip=ip,
    )
    return _to_dict(stat)


@router.put("/{stat_id}")
@limiter.limit("60/minute")
def update_stat(
    stat_id: int,
    body: HomeStatBody,
    request: Request,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    stat = db.query(HomeStatModel).filter(HomeStatModel.id == stat_id).first()
    if not stat:
        raise HTTPException(status_code=404, detail="Estadística no encontrada")
    stat.position = body.position
    stat.value = body.value
    stat.label = body.label
    stat.icon = body.icon
    stat.active = body.active
    db.commit()
    db.refresh(stat)
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="update_home_stat",
        resource=f"stat:{stat.id}",
        ip=ip,
    )
    return _to_dict(stat)


@router.delete("/{stat_id}", status_code=204)
@limiter.limit("30/minute")
def delete_stat(
    stat_id: int,
    request: Request,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    stat = db.query(HomeStatModel).filter(HomeStatModel.id == stat_id).first()
    if not stat:
        raise HTTPException(status_code=404, detail="Estadística no encontrada")
    db.delete(stat)
    db.commit()
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="delete_home_stat",
        resource=f"stat:{stat_id}",
        ip=ip,
    )
