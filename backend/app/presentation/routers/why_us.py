"""
CRUD de los bloques '¿Por qué elegirnos?' del home.
"""
from typing import Optional
from fastapi import APIRouter, Depends, HTTPException, Request
from pydantic import BaseModel, Field, field_validator
from sqlalchemy.orm import Session
import bleach

from app.database import get_db
from app.infrastructure.database.models.why_us_model import WhyUsItemModel
from app.presentation.dependencies import get_current_admin
from app.presentation.rate_limiter import limiter
from app.infrastructure.audit.change_log import record_change

router = APIRouter(prefix="/api/why-us", tags=["why-us"])


class WhyUsBody(BaseModel):
    title: str = Field(..., min_length=1, max_length=100)
    description: str = Field(..., min_length=1, max_length=500)
    icon: Optional[str] = Field(None, max_length=50)
    position: int = Field(0, ge=0, le=999)
    active: bool = True

    @field_validator("title", "description", "icon", mode="before")
    @classmethod
    def strip_html(cls, v):
        if v is None:
            return v
        return bleach.clean(str(v), tags=[], attributes={}, strip=True)


def _to_dict(w: WhyUsItemModel) -> dict:
    return {
        "id": w.id,
        "title": w.title,
        "description": w.description,
        "icon": w.icon,
        "position": w.position,
        "active": w.active,
    }


# ───────── Público ─────────

@router.get("")
def list_active(db: Session = Depends(get_db)):
    items = (
        db.query(WhyUsItemModel)
        .filter(WhyUsItemModel.active == True)  # noqa: E712
        .order_by(WhyUsItemModel.position, WhyUsItemModel.id)
        .all()
    )
    return [_to_dict(w) for w in items]


# ───────── Admin (CRUD) ─────────

@router.get("/admin/all")
@limiter.limit("60/minute")
def list_all_admin(
    request: Request,
    db: Session = Depends(get_db),
    _admin: dict = Depends(get_current_admin),
):
    items = db.query(WhyUsItemModel).order_by(WhyUsItemModel.position, WhyUsItemModel.id).all()
    return [_to_dict(w) for w in items]


@router.post("", status_code=201)
@limiter.limit("30/minute")
def create_item(
    request: Request,
    body: WhyUsBody,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    item = WhyUsItemModel(**body.model_dump())
    db.add(item)
    db.commit()
    db.refresh(item)
    after = _to_dict(item)
    record_change(db, "why_us", str(item.id), "create", None, after, admin, request)
    return after


@router.put("/{item_id}")
@limiter.limit("60/minute")
def update_item(
    item_id: int,
    body: WhyUsBody,
    request: Request,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    item = db.query(WhyUsItemModel).filter(WhyUsItemModel.id == item_id).first()
    if not item:
        raise HTTPException(status_code=404, detail="Bloque no encontrado")
    before = _to_dict(item)
    for key, value in body.model_dump().items():
        setattr(item, key, value)
    db.commit()
    db.refresh(item)
    after = _to_dict(item)
    record_change(db, "why_us", str(item.id), "update", before, after, admin, request)
    return after


@router.delete("/{item_id}", status_code=204)
@limiter.limit("30/minute")
def delete_item(
    item_id: int,
    request: Request,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    item = db.query(WhyUsItemModel).filter(WhyUsItemModel.id == item_id).first()
    if not item:
        raise HTTPException(status_code=404, detail="Bloque no encontrado")
    before = _to_dict(item)
    db.delete(item)
    db.commit()
    record_change(db, "why_us", str(item_id), "delete", before, None, admin, request)
