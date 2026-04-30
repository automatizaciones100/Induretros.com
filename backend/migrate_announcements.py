"""
Migración one-shot: crea la tabla announcements.
No inserta datos por defecto — los crea marketing desde el admin.
Idempotente.

Uso:
    python migrate_announcements.py
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from app.database import engine, Base
import app.infrastructure.database.models  # noqa: F401

Base.metadata.create_all(bind=engine)
print("  [OK] tabla announcements creada o verificada\n\nMigracion completada.")
