"""
Migración one-shot: mueve las URLs de imágenes de categorías de /static/images/
(servido por el backend) a /categorias/ (asset estático en frontend/public/).

Motivo:
  Las imágenes de categorías son 13 assets que cambian raras veces. Servirlas
  desde el CDN del frontend (Amplify) es más rápido y permite que el backend
  no las maneje. UGC (productos) sigue yendo a S3 vía /images/.

Patrón:
  /static/images/filtros.webp → /categorias/filtros.webp
  (sólo el prefijo cambia; el nombre del archivo se respeta para no romper
   las copias ya hechas en frontend/public/categorias/.)

Idempotente: sólo actualiza filas que aún tienen el prefijo legacy.

Uso:
    python migrate_category_images_to_static.py
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from sqlalchemy import text
from app.database import engine

LEGACY_PREFIX = "/static/images/"
NEW_PREFIX = "/categorias/"

with engine.begin() as conn:
    # Vista previa
    rows = conn.execute(
        text(
            "SELECT id, slug, image_url FROM categories "
            "WHERE image_url LIKE :p ORDER BY display_order"
        ),
        {"p": f"{LEGACY_PREFIX}%"},
    ).fetchall()

    if not rows:
        print("Nada que migrar — no hay categorías con prefijo legacy '/static/images/'.")
        sys.exit(0)

    print(f"Se actualizarán {len(rows)} categorías:")
    for row in rows:
        old = row.image_url
        new = NEW_PREFIX + old[len(LEGACY_PREFIX):]
        print(f"  [{row.slug}]  {old} -> {new}")

    # UPDATE en bloque — reemplaza sólo el prefijo, conserva el filename.
    # Compatible con SQLite y PostgreSQL.
    conn.execute(
        text(
            "UPDATE categories "
            "SET image_url = :new_prefix || SUBSTR(image_url, :legacy_len + 1) "
            "WHERE image_url LIKE :pattern"
        ),
        {
            "new_prefix": NEW_PREFIX,
            "legacy_len": len(LEGACY_PREFIX),
            "pattern": f"{LEGACY_PREFIX}%",
        },
    )

print(f"\nOK: {len(rows)} categorías actualizadas a prefijo '{NEW_PREFIX}'.")
