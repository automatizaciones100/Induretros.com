"""
CRUD de testimonios de clientes.
Endpoint público GET retorna solo los activos, ordenados por position.
"""
from typing import Optional
from fastapi import APIRouter, Depends, HTTPException, Request
from pydantic import BaseModel, Field, field_validator
from sqlalchemy.orm import Session
import bleach

from app.database import get_db
from app.infrastructure.database.models.testimonial_model import TestimonialModel
from app.presentation.dependencies import get_current_admin
from app.presentation.rate_limiter import limiter
from app.infrastructure.logging.security_logger import log_admin_action

router = APIRouter(prefix="/api/testimonials", tags=["testimonials"])


class TestimonialBody(BaseModel):
    client_name: str = Field(..., min_length=1, max_length=100)
    client_company: Optional[str] = Field(None, max_length=100)
    comment: str = Field(..., min_length=1, max_length=2000)
    rating: int = Field(5, ge=0, le=5)
    photo_url: Optional[str] = Field(None, max_length=500)
    position: int = Field(0, ge=0, le=999)
    active: bool = True

    @field_validator("client_name", "client_company", "comment", mode="before")
    @classmethod
    def strip_html(cls, v):
        if v is None:
            return v
        return bleach.clean(str(v), tags=[], attributes={}, strip=True)

    @field_validator("photo_url", mode="before")
    @classmethod
    def validate_url(cls, v):
        if v is None or v == "":
            return None
        v = str(v).strip()
        if not (v.startswith("https://") or v.startswith("http://") or v.startswith("/")):
            raise ValueError("photo_url debe ser http://, https:// o ruta relativa")
        return v


def _to_dict(t: TestimonialModel) -> dict:
    return {
        "id": t.id,
        "client_name": t.client_name,
        "client_company": t.client_company,
        "comment": t.comment,
        "rating": t.rating,
        "photo_url": t.photo_url,
        "position": t.position,
        "active": t.active,
    }


# ───────── Público ─────────

@router.get("")
def list_active(db: Session = Depends(get_db)):
    items = (
        db.query(TestimonialModel)
        .filter(TestimonialModel.active == True)  # noqa: E712
        .order_by(TestimonialModel.position, TestimonialModel.id)
        .all()
    )
    return [_to_dict(t) for t in items]


# ───────── Admin (CRUD) ─────────

@router.get("/admin/all")
@limiter.limit("60/minute")
def list_all_admin(
    request: Request,
    db: Session = Depends(get_db),
    _admin: dict = Depends(get_current_admin),
):
    items = db.query(TestimonialModel).order_by(TestimonialModel.position, TestimonialModel.id).all()
    return [_to_dict(t) for t in items]


@router.post("", status_code=201)
@limiter.limit("30/minute")
def create_testimonial(
    request: Request,
    body: TestimonialBody,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    item = TestimonialModel(**body.model_dump())
    db.add(item)
    db.commit()
    db.refresh(item)
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="create_testimonial",
        resource=f"testimonial:{item.id}",
        ip=ip,
    )
    return _to_dict(item)


@router.put("/{testimonial_id}")
@limiter.limit("60/minute")
def update_testimonial(
    testimonial_id: int,
    body: TestimonialBody,
    request: Request,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    item = db.query(TestimonialModel).filter(TestimonialModel.id == testimonial_id).first()
    if not item:
        raise HTTPException(status_code=404, detail="Testimonio no encontrado")
    for key, value in body.model_dump().items():
        setattr(item, key, value)
    db.commit()
    db.refresh(item)
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="update_testimonial",
        resource=f"testimonial:{item.id}",
        ip=ip,
    )
    return _to_dict(item)


@router.delete("/{testimonial_id}", status_code=204)
@limiter.limit("30/minute")
def delete_testimonial(
    testimonial_id: int,
    request: Request,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    item = db.query(TestimonialModel).filter(TestimonialModel.id == testimonial_id).first()
    if not item:
        raise HTTPException(status_code=404, detail="Testimonio no encontrado")
    db.delete(item)
    db.commit()
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="delete_testimonial",
        resource=f"testimonial:{testimonial_id}",
        ip=ip,
    )
