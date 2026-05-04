"""
Snapshots inmutables del estado anterior y posterior de cada cambio
realizado por un admin sobre entidades editables (why_us, faq, testimonials,
home_stats, announcements, legal_pages, site_settings). Habilita el rollback
desde el panel admin (Hito 5.10).
"""
from sqlalchemy import Column, Integer, String, Text, Boolean, DateTime, Index
from sqlalchemy.sql import func
from app.database import Base


class ChangeLogModel(Base):
    __tablename__ = "change_log"

    id = Column(Integer, primary_key=True, index=True)
    # Tipo de entidad: 'why_us', 'faq', 'testimonial', 'home_stat',
    # 'announcement', 'legal_page', 'site_settings'
    entity_type = Column(String(40), nullable=False, index=True)
    # ID del registro afectado. Para legal_pages es el slug; para
    # site_settings siempre es '1' (singleton); para el resto, el id numérico.
    entity_id = Column(String(80), nullable=False, index=True)
    # Acción: 'create' | 'update' | 'delete'
    action = Column(String(10), nullable=False)
    # JSON serializado del estado antes del cambio. None para 'create'.
    before_json = Column(Text, nullable=True)
    # JSON serializado del estado después del cambio. None para 'delete'.
    after_json = Column(Text, nullable=True)
    user_id = Column(Integer, nullable=True, index=True)
    user_email = Column(String(120), nullable=True)
    ip = Column(String(45), nullable=True)
    # Marca cuando una entrada ya fue revertida desde el admin para evitar
    # restaurarla dos veces.
    restored = Column(Boolean, default=False, nullable=False, index=True)
    restored_at = Column(DateTime(timezone=True), nullable=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now(), nullable=False)


# Índice compuesto para listados rápidos por entidad y orden cronológico.
Index("ix_change_log_entity_created", ChangeLogModel.entity_type, ChangeLogModel.created_at.desc())
