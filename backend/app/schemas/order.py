from pydantic import BaseModel, EmailStr
from typing import Optional
from datetime import datetime
from app.models.order import OrderStatus


class OrderItemCreate(BaseModel):
    product_id: int
    quantity: int


class OrderCreate(BaseModel):
    customer_name: str
    customer_email: EmailStr
    customer_phone: Optional[str] = None
    shipping_address: Optional[str] = None
    notes: Optional[str] = None
    items: list[OrderItemCreate]


class OrderItemOut(BaseModel):
    id: int
    product_id: int
    quantity: int
    unit_price: float
    subtotal: float

    class Config:
        from_attributes = True


class OrderOut(BaseModel):
    id: int
    status: OrderStatus
    total: float
    customer_name: str
    customer_email: str
    customer_phone: Optional[str] = None
    shipping_address: Optional[str] = None
    notes: Optional[str] = None
    items: list[OrderItemOut] = []
    created_at: datetime

    class Config:
        from_attributes = True
