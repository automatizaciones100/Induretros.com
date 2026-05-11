from pydantic import BaseModel, EmailStr, Field, field_validator
from typing import Optional
from datetime import datetime
import bleach
from app.domain.entities.order import OrderStatus


class OrderItemCommand(BaseModel):
    product_id: int = Field(..., gt=0)
    quantity: int = Field(..., ge=1, le=999)


class CreateOrderCommand(BaseModel):
    customer_name: str = Field(..., min_length=2, max_length=150)
    customer_email: EmailStr
    customer_phone: Optional[str] = Field(None, max_length=30)
    shipping_address: Optional[str] = Field(None, max_length=300)
    notes: Optional[str] = Field(None, max_length=1000)
    items: list[OrderItemCommand] = Field(..., min_length=1, max_length=50)
    # Atribución de marketing (opcional): el frontend la envía si la capturó
    # del landing. Se sanitiza igual que los otros campos string.
    utm_source: Optional[str] = Field(None, max_length=100)
    utm_medium: Optional[str] = Field(None, max_length=50)
    utm_campaign: Optional[str] = Field(None, max_length=150)
    utm_term: Optional[str] = Field(None, max_length=150)
    utm_content: Optional[str] = Field(None, max_length=150)
    gclid: Optional[str] = Field(None, max_length=255)
    landing_page: Optional[str] = Field(None, max_length=500)

    @field_validator(
        "customer_name", "shipping_address", "notes",
        "utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content",
        "gclid", "landing_page",
        mode="before",
    )
    @classmethod
    def strip_html(cls, v: Optional[str]) -> Optional[str]:
        if v is None:
            return v
        return bleach.clean(v, tags=[], attributes={}, strip=True)


class OrderItemDTO(BaseModel):
    id: int
    product_id: int
    quantity: int
    unit_price: float
    subtotal: float

    model_config = {"from_attributes": True}


class OrderDTO(BaseModel):
    id: int
    status: OrderStatus
    total: float
    customer_name: str
    customer_email: str
    customer_phone: Optional[str] = None
    shipping_address: Optional[str] = None
    notes: Optional[str] = None
    items: list[OrderItemDTO] = []
    created_at: datetime

    model_config = {"from_attributes": True}
