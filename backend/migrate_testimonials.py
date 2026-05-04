"""
Migración one-shot: crea la tabla testimonials e inserta 3 testimonios
de ejemplo si la tabla está vacía. Idempotente.

Uso:
    python migrate_testimonials.py
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from app.database import engine, SessionLocal, Base
import app.infrastructure.database.models  # noqa: F401
from app.infrastructure.database.models.testimonial_model import TestimonialModel

Base.metadata.create_all(bind=engine)
print("  [OK] tabla testimonials creada o verificada")

db = SessionLocal()
existing = db.query(TestimonialModel).count()
if existing == 0:
    SAMPLES = [
        {
            "position": 1,
            "client_name": "Carlos Ramirez",
            "client_company": "Constructora Andina S.A.S.",
            "comment": "Llevamos 6 anos comprando con Induretros. Siempre tienen lo que necesitamos y los precios son los mejores del mercado. Totalmente recomendados.",
            "rating": 5,
        },
        {
            "position": 2,
            "client_name": "Maria Fernandez",
            "client_company": "Mineria del Norte",
            "comment": "Nos resolvieron una urgencia con un repuesto que no encontrabamos en ningun lado. Atencion personalizada de primera y entrega super rapida.",
            "rating": 5,
        },
        {
            "position": 3,
            "client_name": "Juan Pablo Gomez",
            "client_company": "Operador Komatsu PC200",
            "comment": "Buena relacion calidad-precio. Los filtros y empaquetaduras que compre vinieron originales con su empaque. Muy serios.",
            "rating": 5,
        },
    ]
    for s in SAMPLES:
        db.add(TestimonialModel(**s, active=True))
    db.commit()
    print(f"  [OK] {len(SAMPLES)} testimonios de ejemplo insertados")
else:
    print(f"  [-]  tabla ya tenia {existing} testimonios, no se insertan defaults")

db.close()
print("\nMigracion completada.")
