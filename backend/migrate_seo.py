"""
Migración one-shot para añadir campos SEO a la tabla products
y crear la tabla site_settings si no existe.

Idempotente — se puede ejecutar varias veces sin romper nada.

Uso:
    python migrate_seo.py
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from sqlalchemy import text
from app.database import engine, SessionLocal, Base
import app.infrastructure.database.models  # noqa: F401 — registra modelos
from app.infrastructure.database.models.site_settings_model import SiteSettingsModel

# Crear tablas que no existan (no afecta a las que ya están)
Base.metadata.create_all(bind=engine)

db = SessionLocal()

# ── 1. Añadir columnas SEO a products si faltan ──
existing_cols = [row[1] for row in db.execute(text("PRAGMA table_info(products)")).fetchall()]

if "meta_title" not in existing_cols:
    db.execute(text("ALTER TABLE products ADD COLUMN meta_title VARCHAR(70)"))
    print("  [OK] columna meta_title añadida a products")
else:
    print("  [-] meta_title ya existía en products")

if "meta_description" not in existing_cols:
    db.execute(text("ALTER TABLE products ADD COLUMN meta_description VARCHAR(200)"))
    print("  [OK] columna meta_description añadida a products")
else:
    print("  [-] meta_description ya existía en products")

db.commit()

# ── 2. Asegurar single-row en site_settings ──
existing = db.query(SiteSettingsModel).filter(SiteSettingsModel.id == 1).first()
if not existing:
    defaults = SiteSettingsModel(
        id=1,
        site_title="Induretros",
        title_template="%s | Induretros",
        default_description="Importadores directos de repuestos para excavadoras hidráulicas. Más de 9 años de experiencia. Medellín, Colombia.",
        default_keywords="repuestos excavadoras, hidráulica, maquinaria pesada, Colombia, Medellín",
        default_og_image=None,
        twitter_handle=None,
        organization_name="Induretros S.A.S.",
        organization_phone="(604) 560-2662",
    )
    db.add(defaults)
    db.commit()
    print("  [OK] site_settings inicializado con valores por defecto")
else:
    print("  [-] site_settings ya tenía la fila 1")

db.close()
print("\nMigración completada.")
