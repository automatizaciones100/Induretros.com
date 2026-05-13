"""
Importación masiva de imágenes desde una carpeta local.
Enlaza automáticamente con productos (por SKU o slug) y categorías (por slug).

Uso:
    python bulk_images.py ./ruta/a/imagenes/

Prioridad al matchear:
    1. FLT-001.jpg              -> producto con SKU 'FLT-001'
    2. filtro-aceite-komatsu.jpg -> producto con slug 'filtro-aceite-komatsu'
    3. filtros.jpg              -> categoria con slug 'filtros'

Storage:
  - Si AWS_S3_BUCKET está configurado → sube a S3 (key: images/<archivo>).
  - Si no → copia a static/images/ (filesystem local, sólo dev).

En ambos casos image_url en BD es '/images/<archivo>' (path canónico,
agnóstico al storage backend).

Formatos aceptados: .jpg .jpeg .png .webp .avif
"""
import sys
import shutil
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from app.config import settings
from app.database import SessionLocal, Base, engine
import app.infrastructure.database.models  # noqa: F401 — registra modelos
from app.infrastructure.database.repositories.product_repository import (
    SQLAlchemyProductRepository,
    SQLAlchemyCategoryRepository,
)
from app.infrastructure.storage import s3_client

Base.metadata.create_all(bind=engine)

ALLOWED = {".jpg", ".jpeg", ".png", ".webp", ".avif"}
# Filesystem destino — sólo se usa cuando S3 no está habilitado (dev).
DEST_DIR = Path(__file__).resolve().parent.parent / "static" / "images"
DEST_DIR.mkdir(parents=True, exist_ok=True)
# Path en BD — canónico, independiente del storage backend.
IMAGE_BASE_URL = "/images"
S3_KEY_PREFIX = "images"
CONTENT_TYPES = {
    ".jpg": "image/jpeg", ".jpeg": "image/jpeg",
    ".png": "image/png", ".webp": "image/webp", ".avif": "image/avif",
}


def run(source_dir: Path) -> None:
    if not source_dir.is_dir():
        print(f"[ERROR] '{source_dir}' no es un directorio valido.")
        sys.exit(1)

    image_files = [f for f in source_dir.iterdir() if f.is_file() and f.suffix.lower() in ALLOWED]

    if not image_files:
        print(f"[WARN] No se encontraron imagenes en '{source_dir}'.")
        return

    use_s3 = s3_client.is_s3_enabled()
    storage_label = f"S3 ({settings.aws_s3_bucket})" if use_s3 else f"filesystem local ({DEST_DIR})"
    print(f"\nProcesando {len(image_files)} imagenes desde '{source_dir}'...")
    print(f"Storage: {storage_label}\n")

    db = SessionLocal()
    product_repo = SQLAlchemyProductRepository(db)
    category_repo = SQLAlchemyCategoryRepository(db)

    ok_p = ok_c = not_found = 0

    for img in sorted(image_files):
        stem = img.stem
        ext = img.suffix.lower()

        # 1) producto por SKU
        product = product_repo.get_by_sku(stem) or product_repo.get_by_slug(stem)
        category = None if product else category_repo.get_by_slug(stem)

        if not product and not category:
            print(f"  [NOT FOUND]  {img.name:<45} sin producto ni categoria con stem '{stem}'")
            not_found += 1
            continue

        filename = f"{stem}{ext}"
        if use_s3:
            s3_client.upload_bytes(
                key=f"{S3_KEY_PREFIX}/{filename}",
                content=img.read_bytes(),
                content_type=CONTENT_TYPES.get(ext, "application/octet-stream"),
            )
        else:
            shutil.copy2(img, DEST_DIR / filename)
        image_url = f"{IMAGE_BASE_URL}/{filename}"

        if product:
            product_repo.update_image_url(product.id, image_url)
            print(f"  [PRODUCT]    {img.name:<45} -> {product.name[:50]}")
            ok_p += 1
        else:
            category_repo.update(category.id, {"image_url": image_url})
            print(f"  [CATEGORY]   {img.name:<45} -> {category.name}")
            ok_c += 1

    db.close()

    print(f"""
{'-'*60}
  Total:                     {len(image_files)}
  Productos actualizados:    {ok_p}
  Categorias actualizadas:   {ok_c}
  No encontrados:            {not_found}
{'-'*60}
""")
    if not_found:
        print("Tip: el nombre del archivo (sin extension) debe coincidir con el")
        print("     SKU/slug del producto o el slug de la categoria.")


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(__doc__)
        sys.exit(0)
    run(Path(sys.argv[1]))
