"""
Configuración global del sitio (single-row).
Aplica a metadata global (Open Graph, Twitter) y valores por defecto cuando
los campos SEO de un producto/categoría están vacíos.
"""
from sqlalchemy import Column, Integer, String, Text, DateTime
from sqlalchemy.sql import func
from app.database import Base


class SiteSettingsModel(Base):
    __tablename__ = "site_settings"

    id = Column(Integer, primary_key=True, default=1)
    site_title = Column(String(100), nullable=True)
    title_template = Column(String(120), nullable=True)
    default_description = Column(String(200), nullable=True)
    default_keywords = Column(String(500), nullable=True)
    default_og_image = Column(String(500), nullable=True)
    twitter_handle = Column(String(50), nullable=True)
    organization_name = Column(String(120), nullable=True)
    organization_phone = Column(String(30), nullable=True)
    updated_at = Column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())
