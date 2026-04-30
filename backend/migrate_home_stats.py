"""
Migración one-shot: crea tabla home_stats e inserta los 4 stats por defecto
si la tabla está vacía. Idempotente.

Uso:
    python migrate_home_stats.py
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from app.database import engine, SessionLocal, Base
import app.infrastructure.database.models  # noqa: F401
from app.infrastructure.database.models.home_stat_model import HomeStatModel

# Crea la tabla si no existe (no afecta otras tablas)
Base.metadata.create_all(bind=engine)
print("  [OK] tabla home_stats creada o verificada")

db = SessionLocal()
existing = db.query(HomeStatModel).count()
if existing == 0:
    DEFAULTS = [
        {"position": 1, "value": "+9",    "label": "Años de experiencia",      "icon": "Clock"},
        {"position": 2, "value": "+1200", "label": "Referencias disponibles",  "icon": "Package"},
        {"position": 3, "value": "+500",  "label": "Clientes satisfechos",     "icon": "Users"},
        {"position": 4, "value": "100%",  "label": "Garantía de calidad",      "icon": "Award"},
    ]
    for s in DEFAULTS:
        db.add(HomeStatModel(**s, active=True))
    db.commit()
    print(f"  [OK] {len(DEFAULTS)} stats por defecto insertados")
else:
    print(f"  [-]  tabla ya tenía {existing} stats, no se insertan defaults")

db.close()
print("\nMigracion completada.")
