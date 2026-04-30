"""
Banners promocionales / barra de anuncios mostrada arriba del header.
Marketing puede crear varios, activar uno o más, definir colores y vencimiento.
"""
from sqlalchemy import Column, Integer, String, Boolean, DateTime
from sqlalchemy.sql import func
from app.database import Base


class AnnouncementModel(Base):
    __tablename__ = "announcements"

    id = Column(Integer, primary_key=True, index=True)
    text = Column(String(300), nullable=False)
    # URL opcional (si está, el banner es clickeable)
    link_url = Column(String(300), nullable=True)
    link_text = Column(String(50), nullable=True)
    # Tema visual: 'info', 'promo', 'warning', 'success', 'alert', 'dark'
    theme = Column(String(20), default="dark", nullable=False)
    # Si está activo
    active = Column(Boolean, default=True, nullable=False, index=True)
    # Si el usuario puede cerrarlo con X
    dismissible = Column(Boolean, default=True, nullable=False)
    # Fecha de expiración opcional (después de esto el endpoint público no lo retorna)
    expires_at = Column(DateTime(timezone=True), nullable=True)
    # Prioridad: si hay varios activos, se muestra el de menor número primero
    priority = Column(Integer, default=0, nullable=False, index=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
