"""
Tabla de eventos de analítica in-house.

NO se guarda IP ni user-agent en claro: solo session_id (UUID random generado
por el navegador). Esto cumple A.5.34 (privacidad / PII) sin renunciar a las
métricas de tráfico.

event_type:
  - "pageview"       → ruta visitada
  - "product_view"   → ficha de producto cargada (path = /producto/slug)
  - "click"          → click en CTA (target = nombre del botón)
  - "add_to_cart"    → producto agregado al carrito
"""
from sqlalchemy import Column, Integer, String, DateTime, ForeignKey, Index
from sqlalchemy.sql import func
from app.database import Base


class AnalyticsEventModel(Base):
    __tablename__ = "analytics_events"

    id = Column(Integer, primary_key=True, index=True)
    event_type = Column(String(50), nullable=False, index=True)
    session_id = Column(String(36), nullable=False, index=True)
    path = Column(String(500), nullable=True)
    target = Column(String(200), nullable=True)
    product_id = Column(Integer, ForeignKey("products.id"), nullable=True, index=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now(), index=True)


# Índice compuesto para el filtro más común: eventos de un tipo en una ventana temporal
Index("ix_analytics_type_created", AnalyticsEventModel.event_type, AnalyticsEventModel.created_at)
