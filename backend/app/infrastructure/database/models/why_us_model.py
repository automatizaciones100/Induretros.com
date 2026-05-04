"""
Bloques de '¿Por qué elegirnos?' que aparecen en el home.
Cada bloque tiene un ícono, título y descripción corta.
Marketing los gestiona desde el admin.
"""
from sqlalchemy import Column, Integer, String, Boolean, DateTime, Text
from sqlalchemy.sql import func
from app.database import Base


class WhyUsItemModel(Base):
    __tablename__ = "why_us_items"

    id = Column(Integer, primary_key=True, index=True)
    title = Column(String(100), nullable=False)
    description = Column(Text, nullable=False)              # texto corto, 1-3 líneas
    icon = Column(String(50), nullable=True)               # nombre lucide-react
    position = Column(Integer, default=0, nullable=False, index=True)
    active = Column(Boolean, default=True, nullable=False)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
