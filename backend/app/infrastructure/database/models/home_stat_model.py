"""
Estadísticas mostradas en el banner del home (los 4 contadores con ícono):
+9 años de experiencia · +1200 referencias · +500 clientes · 100% garantía.

Marketing puede añadir, editar, quitar o reordenar desde el admin.
"""
from sqlalchemy import Column, Integer, String, Boolean, DateTime
from sqlalchemy.sql import func
from app.database import Base


class HomeStatModel(Base):
    __tablename__ = "home_stats"

    id = Column(Integer, primary_key=True, index=True)
    # Orden de aparición en el home (menor número = más a la izquierda)
    position = Column(Integer, default=0, nullable=False, index=True)
    # Valor grande que se muestra (ej. '+9', '1200', '100%')
    value = Column(String(20), nullable=False)
    # Etiqueta debajo (ej. 'Años de experiencia')
    label = Column(String(100), nullable=False)
    # Nombre del ícono de lucide-react (ej. 'Clock', 'Package', 'Users', 'Award')
    icon = Column(String(50), nullable=True)
    # Si está visible en el home
    active = Column(Boolean, default=True, nullable=False)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
