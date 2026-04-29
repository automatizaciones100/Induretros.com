"""
Migración one-shot: añade columnas de contacto y redes sociales a site_settings.
Idempotente — ejecutable múltiples veces sin romper nada.

Uso:
    python migrate_contact_settings.py
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

# Columnas nuevas que añadir
NEW_COLUMNS = [
    ("contact_email",          "VARCHAR(120)"),
    ("contact_address",        "VARCHAR(300)"),
    ("contact_business_hours", "VARCHAR(150)"),
    ("whatsapp_number",        "VARCHAR(30)"),
    ("facebook_url",           "VARCHAR(300)"),
    ("instagram_url",          "VARCHAR(300)"),
    ("youtube_url",            "VARCHAR(300)"),
    ("tiktok_url",             "VARCHAR(300)"),
    ("linkedin_url",           "VARCHAR(300)"),
]

existing = [row[1] for row in db.execute(text("PRAGMA table_info(site_settings)")).fetchall()]

for col_name, col_type in NEW_COLUMNS:
    if col_name not in existing:
        db.execute(text(f"ALTER TABLE site_settings ADD COLUMN {col_name} {col_type}"))
        print(f"  [OK] columna {col_name} anadida")
    else:
        print(f"  [-]  {col_name} ya existia")
db.commit()

# Set defaults de contacto (con los valores actuales del sitio para no perder nada)
s = db.query(SiteSettingsModel).filter(SiteSettingsModel.id == 1).first()
if s:
    if not s.contact_email:
        s.contact_email = "ventas@induretros.com"
    if not s.contact_address:
        s.contact_address = "Centro Empresarial Promision, Medellin, Colombia"
    if not s.contact_business_hours:
        s.contact_business_hours = "Lunes a Viernes: 7:00 am - 5:00 pm"
    if not s.whatsapp_number:
        s.whatsapp_number = "573007192973"
    if not s.facebook_url:
        s.facebook_url = "https://www.facebook.com/induretros"
    if not s.instagram_url:
        s.instagram_url = "https://www.instagram.com/induretros"
    if not s.youtube_url:
        s.youtube_url = "https://www.youtube.com/@induretros"
    db.commit()
    print("  [OK] valores por defecto seteados en fila id=1")

db.close()
print("\nMigracion completada.")
