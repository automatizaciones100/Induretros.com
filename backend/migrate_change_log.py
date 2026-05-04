"""
Migración one-shot: crea la tabla change_log para el historial de cambios
con rollback (Hito 5.10). Idempotente.

Uso:
    python migrate_change_log.py
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from app.database import engine, Base
import app.infrastructure.database.models  # noqa: F401

Base.metadata.create_all(bind=engine)
print("  [OK] tabla change_log creada o verificada")
print("\nMigracion completada.")
