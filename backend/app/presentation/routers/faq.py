"""
CRUD de preguntas frecuentes (FAQ).
Endpoint público GET retorna solo las activas, ordenadas por position.
"""
from typing import Optional
from fastapi import APIRouter, Depends, HTTPException, Request
from pydantic import BaseModel, Field, field_validator
from sqlalchemy.orm import Session
import bleach

from app.database import get_db
from app.infrastructure.database.models.faq_model import FaqItemModel
from app.presentation.dependencies import get_current_admin
from app.presentation.rate_limiter import limiter
from app.infrastructure.logging.security_logger import log_admin_action

router = APIRouter(prefix="/api/faq", tags=["faq"])

# Permitimos algo de markup básico en answer (negrita, listas, links)
_ALLOWED_TAGS = ["p", "br", "strong", "em", "ul", "ol", "li", "a"]
_ALLOWED_ATTRS = {"a": ["href", "title"]}


class FaqItemBody(BaseModel):
    question: str = Field(..., min_length=3, max_length=200)
    answer: str = Field(..., min_length=3, max_length=5000)
    category: Optional[str] = Field(None, max_length=50)
    position: int = Field(0, ge=0, le=999)
    active: bool = True

    @field_validator("question", "category", mode="before")
    @classmethod
    def strip_html(cls, v):
        if v is None:
            return v
        return bleach.clean(str(v), tags=[], attributes={}, strip=True)

    @field_validator("answer", mode="before")
    @classmethod
    def sanitize_answer(cls, v):
        if v is None:
            return v
        return bleach.clean(str(v), tags=_ALLOWED_TAGS, attributes=_ALLOWED_ATTRS, strip=True)


def _to_dict(f: FaqItemModel) -> dict:
    return {
        "id": f.id,
        "question": f.question,
        "answer": f.answer,
        "category": f.category,
        "position": f.position,
        "active": f.active,
    }


# ───────── Público ─────────

@router.get("")
def list_active(db: Session = Depends(get_db)):
    items = (
        db.query(FaqItemModel)
        .filter(FaqItemModel.active == True)  # noqa: E712
        .order_by(FaqItemModel.position, FaqItemModel.id)
        .all()
    )
    return [_to_dict(f) for f in items]


# ───────── Admin (CRUD) ─────────

@router.get("/admin/all")
@limiter.limit("60/minute")
def list_all_admin(
    request: Request,
    db: Session = Depends(get_db),
    _admin: dict = Depends(get_current_admin),
):
    items = db.query(FaqItemModel).order_by(FaqItemModel.position, FaqItemModel.id).all()
    return [_to_dict(f) for f in items]


@router.post("", status_code=201)
@limiter.limit("30/minute")
def create_faq(
    request: Request,
    body: FaqItemBody,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    item = FaqItemModel(**body.model_dump())
    db.add(item)
    db.commit()
    db.refresh(item)
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="create_faq",
        resource=f"faq:{item.id}",
        ip=ip,
    )
    return _to_dict(item)


@router.put("/{faq_id}")
@limiter.limit("60/minute")
def update_faq(
    faq_id: int,
    body: FaqItemBody,
    request: Request,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    item = db.query(FaqItemModel).filter(FaqItemModel.id == faq_id).first()
    if not item:
        raise HTTPException(status_code=404, detail="Pregunta no encontrada")
    for key, value in body.model_dump().items():
        setattr(item, key, value)
    db.commit()
    db.refresh(item)
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="update_faq",
        resource=f"faq:{item.id}",
        ip=ip,
    )
    return _to_dict(item)


@router.delete("/{faq_id}", status_code=204)
@limiter.limit("30/minute")
def delete_faq(
    faq_id: int,
    request: Request,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    item = db.query(FaqItemModel).filter(FaqItemModel.id == faq_id).first()
    if not item:
        raise HTTPException(status_code=404, detail="Pregunta no encontrada")
    db.delete(item)
    db.commit()
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="delete_faq",
        resource=f"faq:{faq_id}",
        ip=ip,
    )
