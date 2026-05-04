"""
Testimonios de clientes que se muestran en el home.
Marketing los gestiona desde el admin.
"""
from sqlalchemy import Column, Integer, String, Boolean, DateTime, Text
from sqlalchemy.sql import func
from app.database import Base


class TestimonialModel(Base):
    __tablename__ = "testimonials"

    id = Column(Integer, primary_key=True, index=True)
    client_name = Column(String(100), nullable=False)
    client_company = Column(String(100), nullable=True)  # opcional: empresa o cargo
    comment = Column(Text, nullable=False)               # el testimonio en sí
    rating = Column(Integer, default=5, nullable=False)  # 0-5 estrellas (0 = no mostrar)
    photo_url = Column(String(500), nullable=True)       # foto del cliente
    position = Column(Integer, default=0, nullable=False, index=True)
    active = Column(Boolean, default=True, nullable=False)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
