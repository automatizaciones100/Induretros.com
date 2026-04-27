import enum
from dataclasses import dataclass, field
from datetime import datetime
from typing import Optional


class OrderStatus(str, enum.Enum):
    pending = "pending"
    processing = "processing"
    completed = "completed"
    cancelled = "cancelled"


@dataclass
class OrderItem:
    id: Optional[int]
    order_id: Optional[int]
    product_id: int
    quantity: int
    unit_price: float
    subtotal: float


@dataclass
class Order:
    id: Optional[int]
    customer_name: str
    customer_email: str
    total: float
    status: OrderStatus = OrderStatus.pending
    user_id: Optional[int] = None
    customer_phone: Optional[str] = None
    shipping_address: Optional[str] = None
    notes: Optional[str] = None
    created_at: Optional[datetime] = None
    items: list[OrderItem] = field(default_factory=list)
