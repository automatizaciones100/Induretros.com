"""
Importación masiva de imágenes desde una carpeta local.

Uso:
    python bulk_images.py ./ruta/a/imagenes/

Convención de nombres:
    FLT-001.jpg              → producto con SKU 'FLT-001'
    filtro-aceite-komatsu.webp → producto con slug 'filtro-aceite-komatsu'

El script copia las imágenes a static/images/ y actualiza image_url en la BD.
No requiere que el servidor esté corriendo.

Formatos aceptados: .jpg .jpeg .png .webp .avif
"""
import sys
import shutil
from pathlib import Path

# ── Setup ──────────────────────────────────────────────────────────────────────
# Asegura que los imports de app.* funcionen desde este script
sys.path.insert(0, str(Path(__file__).parent))

from app.database import SessionLocal, Base, engine
import app.infrastructure.database.models  # noqa: F401 — registra modelos
from app.infrastructure.database.repositories.product_repository import SQLAlchemyProductRepository

Base.metadata.create_all(bind=engine)

ALLOWED = {".jpg", ".jpeg", ".png", ".webp", ".avif"}
DEST_DIR = Path(__file__).parent / "static" / "images"
DEST_DIR.mkdir(parents=True, exist_ok=True)
IMAGE_BASE_URL = "/static/images"

# ── Lógica principal ───────────────────────────────────────────────────────────

def run(source_dir: Path) -> None:
    if not source_dir.is_dir():
        print(f"[ERROR] '{source_dir}' no es un directorio válido.")
        sys.exit(1)

    image_files = [f for f in source_dir.iterdir() if f.is_file() and f.suffix.lower() in ALLOWED]

    if not image_files:
        print(f"[WARN] No se encontraron imágenes en '{source_dir}'.")
        return

    print(f"\nProcesando {len(image_files)} imágenes desde '{source_dir}'...\n")

    db = SessionLocal()
    repo = SQLAlchemyProductRepository(db)

    ok = not_found = skipped = 0

    for img in sorted(image_files):
        stem = img.stem          # nombre sin extensión
        ext  = img.suffix.lower()

        # Buscar por SKU primero, luego por slug
        product = repo.get_by_sku(stem) or repo.get_by_slug(stem)

        if not product:
            print(f"  [NOT FOUND] {img.name:<45} → sin producto con SKU/slug '{stem}'")
            not_found += 1
            continue

        # Copiar al directorio de estáticos
        dest = DEST_DIR / f"{stem}{ext}"
        shutil.copy2(img, dest)

        image_url = f"{IMAGE_BASE_URL}/{stem}{ext}"
        repo.update_image_url(product.id, image_url)

        print(f"  [OK]        {img.name:<45} → {product.name[:50]}")
        ok += 1

    db.close()

    print(f"""
{'─'*55}
  Total:        {len(image_files)}
  Actualizados: {ok}
  No hallados:  {not_found}
{'─'*55}
""")
    if not_found:
        print("Tip: renombra los archivos sin encontrar usando el SKU del producto (ej. FLT-001.jpg)")


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(__doc__)
        sys.exit(0)
    run(Path(sys.argv[1]))
