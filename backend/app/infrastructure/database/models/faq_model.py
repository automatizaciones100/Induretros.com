"""
Preguntas frecuentes que se muestran en la página /faq, agrupadas por categoría.
Marketing las gestiona desde el admin.
"""
from sqlalchemy import Column, Integer, String, Boolean, DateTime, Text
from sqlalchemy.sql import func
from app.database import Base


class FaqItemModel(Base):
    __tablename__ = "faq_items"

    id = Column(Integer, primary_key=True, index=True)
    question = Column(String(200), nullable=False)
    answer = Column(Text, nullable=False)
    # Agrupador opcional: 'Pedidos', 'Envíos', 'Pagos', 'Productos', 'Garantía', etc.
    category = Column(String(50), nullable=True, index=True)
    position = Column(Integer, default=0, nullable=False, index=True)
    active = Column(Boolean, default=True, nullable=False)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
