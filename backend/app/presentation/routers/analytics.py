"""
Endpoint público para registrar eventos de analítica in-house.
No requiere autenticación — el frontend genera un session_id anónimo.
"""
import re
from typing import Optional, Literal
from fastapi import APIRouter, Depends, Request
from pydantic import BaseModel, Field
from sqlalchemy.orm import Session

from app.database import get_db
from app.infrastructure.database.models.analytics_model import AnalyticsEventModel
from app.presentation.rate_limiter import limiter

router = APIRouter(prefix="/api/analytics", tags=["analytics"])

_SESSION_PATTERN = re.compile(r"^[a-zA-Z0-9_-]{8,64}$")


class AnalyticsEventInput(BaseModel):
    event_type: Literal["pageview", "product_view", "click", "add_to_cart"]
    session_id: str = Field(..., min_length=8, max_length=64)
    path: Optional[str] = Field(None, max_length=500)
    target: Optional[str] = Field(None, max_length=200)
    product_id: Optional[int] = Field(None, gt=0)


@router.post("/event", status_code=204)
@limiter.limit("60/minute")
def track_event(
    request: Request,
    event: AnalyticsEventInput,
    db: Session = Depends(get_db),
):
    """Registra un evento de analítica. Silencia errores para no romper la UX."""
    if not _SESSION_PATTERN.match(event.session_id):
        return  # session_id mal formado → ignorar silenciosamente

    try:
        db.add(AnalyticsEventModel(
            event_type=event.event_type,
            session_id=event.session_id,
            path=event.path,
            target=event.target,
            product_id=event.product_id,
        ))
        db.commit()
    except Exception:
        db.rollback()
        # No filtrar errores al cliente — analytics es best-effort
