"""
Migración one-shot: añade columnas del Hero del home a site_settings.
Setea valores iniciales tomados del hardcoded actual de la home.
Idempotente — ejecutable múltiples veces sin romper nada.

Uso:
    python migrate_hero_settings.py
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from sqlalchemy import text
from app.database import engine, SessionLocal, Base
import app.infrastructure.database.models  # noqa: F401
from app.infrastructure.database.models.site_settings_model import SiteSettingsModel

Base.metadata.create_all(bind=engine)
db = SessionLocal()

NEW_COLUMNS = [
    ("hero_label",     "VARCHAR(80)"),
    ("hero_title",     "VARCHAR(150)"),
    ("hero_subtitle",  "VARCHAR(400)"),
    ("hero_cta_text",  "VARCHAR(50)"),
    ("hero_cta_url",   "VARCHAR(200)"),
    ("hero_cta2_text", "VARCHAR(50)"),
    ("hero_cta2_url",  "VARCHAR(200)"),
    ("hero_image_url", "VARCHAR(500)"),
]

existing = [row[1] for row in db.execute(text("PRAGMA table_info(site_settings)")).fetchall()]

for col_name, col_type in NEW_COLUMNS:
    if col_name not in existing:
        db.execute(text(f"ALTER TABLE site_settings ADD COLUMN {col_name} {col_type}"))
        print(f"  [OK] columna {col_name} anadida")
    else:
        print(f"  [-]  {col_name} ya existia")
db.commit()

# Defaults iniciales — los valores que estaban hardcoded en page.tsx
s = db.query(SiteSettingsModel).filter(SiteSettingsModel.id == 1).first()
if s:
    if not s.hero_label:
        s.hero_label = "Importadores directos"
    if not s.hero_title:
        s.hero_title = "Repuestos para Excavadoras Hidraulicas"
    if not s.hero_subtitle:
        s.hero_subtitle = (
            "Mas de 9 anos importando directamente los mejores repuestos para "
            "maquinaria pesada. Disponibilidad inmediata y atencion personalizada."
        )
    if not s.hero_cta_text:
        s.hero_cta_text = "Ver catalogo"
    if not s.hero_cta_url:
        s.hero_cta_url = "/repuestos"
    if not s.hero_cta2_text:
        s.hero_cta2_text = "Cotizar por WhatsApp"
    if not s.hero_cta2_url:
        # Por defecto apunta al WhatsApp de la organizacion (se calcula client-side)
        s.hero_cta2_url = "whatsapp:default"
    if not s.hero_image_url:
        s.hero_image_url = "/noshadow-excabadora-768x576.webp"
    db.commit()
    print("  [OK] valores hero por defecto seteados en fila id=1")

db.close()
print("\nMigracion completada.")
