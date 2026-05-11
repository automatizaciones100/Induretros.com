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
    # Atribución de marketing — qué fuente trajo al cliente al pedido
    # (Capa 1 del plan de UTM tracking). Todos opcionales — un pedido directo
    # los tendrá todos en None.
    utm_source: Optional[str] = None
    utm_medium: Optional[str] = None
    utm_campaign: Optional[str] = None
    utm_term: Optional[str] = None
    utm_content: Optional[str] = None
    gclid: Optional[str] = None
    landing_page: Optional[str] = None
