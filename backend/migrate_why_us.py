"""
Migración one-shot: crea la tabla why_us_items e inserta 4 bloques de
ejemplo si está vacía. Idempotente.

Uso:
    python migrate_why_us.py
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from app.database import engine, SessionLocal, Base
import app.infrastructure.database.models  # noqa: F401
from app.infrastructure.database.models.why_us_model import WhyUsItemModel

Base.metadata.create_all(bind=engine)
print("  [OK] tabla why_us_items creada o verificada")

db = SessionLocal()
existing = db.query(WhyUsItemModel).count()
if existing == 0:
    SAMPLES = [
        {
            "position": 1,
            "icon": "Truck",
            "title": "Importadores directos",
            "description": "Traemos los repuestos de fabrica eliminando intermediarios. Mejores precios para ti.",
        },
        {
            "position": 2,
            "icon": "Clock",
            "title": "+9 anos de experiencia",
            "description": "Conocemos el sector y los modelos de excavadora. Acertamos a la primera con la referencia correcta.",
        },
        {
            "position": 3,
            "icon": "ShieldCheck",
            "title": "Garantia original",
            "description": "Todos nuestros productos vienen con garantia de fabrica y respaldo postventa.",
        },
        {
            "position": 4,
            "icon": "ThumbsUp",
            "title": "Atencion personalizada",
            "description": "Un asesor humano te ayuda a encontrar exactamente lo que buscas, sin formularios eternos.",
        },
    ]
    for s in SAMPLES:
        db.add(WhyUsItemModel(**s, active=True))
    db.commit()
    print(f"  [OK] {len(SAMPLES)} bloques de ejemplo insertados")
else:
    print(f"  [-]  tabla ya tenia {existing} bloques, no se insertan defaults")

db.close()
print("\nMigracion completada.")
