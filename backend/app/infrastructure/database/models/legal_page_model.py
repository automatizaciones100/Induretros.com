"""
Páginas legales editables desde el admin (garantía, términos, privacidad,
política de envíos, etc.). Cada una se identifica por su slug único.
"""
from sqlalchemy import Column, Integer, String, Text, DateTime
from sqlalchemy.sql import func
from app.database import Base


class LegalPageModel(Base):
    __tablename__ = "legal_pages"

    id = Column(Integer, primary_key=True, index=True)
    slug = Column(String(50), unique=True, nullable=False, index=True)
    title = Column(String(200), nullable=False)
    # Contenido HTML sanitizado con bleach (h2, h3, p, ul, ol, li, strong, em, a, br…)
    content = Column(Text, nullable=False)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
