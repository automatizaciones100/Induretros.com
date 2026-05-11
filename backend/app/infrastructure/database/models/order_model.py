from sqlalchemy import Column, Integer, String, Float, ForeignKey, DateTime, Enum
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
from app.database import Base
from app.domain.entities.order import OrderStatus


class OrderModel(Base):
    __tablename__ = "orders"

    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=True, index=True)
    status = Column(Enum(OrderStatus), default=OrderStatus.pending)
    total = Column(Float, nullable=False)
    customer_name = Column(String(255), nullable=False)
    customer_email = Column(String(255), nullable=False)
    customer_phone = Column(String(50), nullable=True)
    shipping_address = Column(String(500), nullable=True)
    notes = Column(String(1000), nullable=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now())

    # Atribución de marketing (Capa 1) — fuente de la conversión
    utm_source = Column(String(100), nullable=True, index=True)
    utm_medium = Column(String(50), nullable=True)
    utm_campaign = Column(String(150), nullable=True, index=True)
    utm_term = Column(String(150), nullable=True)
    utm_content = Column(String(150), nullable=True)
    gclid = Column(String(255), nullable=True)
    landing_page = Column(String(500), nullable=True)

    user = relationship("UserModel", back_populates="orders")
    items = relationship("OrderItemModel", back_populates="order")


class OrderItemModel(Base):
    __tablename__ = "order_items"

    id = Column(Integer, primary_key=True, index=True)
    order_id = Column(Integer, ForeignKey("orders.id"), nullable=False)
    product_id = Column(Integer, ForeignKey("products.id"), nullable=False)
    quantity = Column(Integer, nullable=False, default=1)
    unit_price = Column(Float, nullable=False)
    subtotal = Column(Float, nullable=False)

    order = relationship("OrderModel", back_populates="items")
    product = relationship("ProductModel", back_populates="order_items")
