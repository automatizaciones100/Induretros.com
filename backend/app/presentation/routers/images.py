"""
Carga masiva de imágenes para productos y categorías.

Convención de nombres de archivo (orden de prioridad):
  1. Por SKU de producto:    FLT-001.jpg → producto con sku='FLT-001'
  2. Por slug de producto:   filtro-aceite-komatsu-pc200.webp → producto
  3. Por slug de categoría:  filtros.jpg → categoría con slug='filtros'

Formatos aceptados: jpg, jpeg, png, webp, avif
Tamaño máximo por archivo: 5 MB
Máximo de archivos por request: 50
"""
import re
from pathlib import Path
from typing import Annotated, Literal, Optional

from fastapi import APIRouter, Depends, File, HTTPException, Request, UploadFile, status
from fastapi.responses import JSONResponse

from app.database import get_db
from app.infrastructure.database.repositories.product_repository import (
    SQLAlchemyProductRepository,
    SQLAlchemyCategoryRepository,
)
from app.presentation.dependencies import get_current_admin
from app.presentation.rate_limiter import limiter
from app.infrastructure.logging.security_logger import log_admin_action
from sqlalchemy.orm import Session

router = APIRouter(prefix="/api/images", tags=["images"])

# Directorio donde se guardan las imágenes servidas como estáticos
IMAGES_DIR = Path(__file__).resolve().parents[4] / "static" / "images"
IMAGES_DIR.mkdir(parents=True, exist_ok=True)

ALLOWED_EXTENSIONS = {".jpg", ".jpeg", ".png", ".webp", ".avif"}
MAX_FILE_SIZE = 5 * 1024 * 1024  # 5 MB
MAX_FILES = 50
IMAGE_BASE_URL = "/static/images"


def _safe_filename(name: str) -> str:
    """Elimina caracteres peligrosos del nombre de archivo."""
    name = re.sub(r"[^a-zA-Z0-9._\-]", "_", name)
    return name[:200]


EntityKind = Literal["product", "category"]


def _identify_target(
    stem: str,
    product_repo: SQLAlchemyProductRepository,
    category_repo: SQLAlchemyCategoryRepository,
) -> tuple[Optional[EntityKind], object]:
    """
    Busca a qué entidad enlazar la imagen, en orden de prioridad:
    1. producto por SKU
    2. producto por slug
    3. categoría por slug
    Retorna (kind, entity) o (None, None) si no encuentra.
    """
    product = product_repo.get_by_sku(stem) or product_repo.get_by_slug(stem)
    if product:
        return "product", product

    category = category_repo.get_by_slug(stem)
    if category:
        return "category", category

    return None, None


@router.post("/upload", status_code=207)
@limiter.limit("10/minute")
async def upload_images(
    request: Request,
    files: Annotated[list[UploadFile], File(description="Imágenes de productos o categorías (máx. 50 archivos, 5 MB c/u)")],
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    """
    Subida masiva de imágenes. El archivo se enlaza automáticamente a:
      - el producto cuyo SKU coincida con el nombre del archivo, o
      - el producto cuyo slug coincida, o
      - la categoría cuyo slug coincida.

    Retorna un reporte con el resultado de cada archivo (207 Multi-Status).
    """
    if len(files) > MAX_FILES:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail=f"Máximo {MAX_FILES} archivos por request. Enviaste {len(files)}.",
        )

    product_repo = SQLAlchemyProductRepository(db)
    category_repo = SQLAlchemyCategoryRepository(db)
    ip = request.client.host if request.client else "unknown"
    user_id = int(admin.get("sub", 0))

    results = []

    for file in files:
        filename = file.filename or ""
        ext = Path(filename).suffix.lower()
        stem = Path(filename).stem

        if ext not in ALLOWED_EXTENSIONS:
            results.append({
                "file": filename,
                "status": "error",
                "detail": f"Formato no permitido '{ext}'. Usa: {', '.join(ALLOWED_EXTENSIONS)}",
            })
            continue

        content = await file.read()
        if len(content) > MAX_FILE_SIZE:
            results.append({
                "file": filename,
                "status": "error",
                "detail": f"Archivo demasiado grande ({len(content) // 1024} KB). Máximo 5 MB.",
            })
            continue

        kind, target = _identify_target(stem, product_repo, category_repo)
        if not target:
            results.append({
                "file": filename,
                "status": "not_found",
                "detail": f"No se encontró producto (SKU/slug) ni categoría (slug) que matchee '{stem}'",
            })
            continue

        # Guardar archivo
        safe_stem = _safe_filename(stem)
        dest_filename = f"{safe_stem}{ext}"
        dest_path = IMAGES_DIR / dest_filename
        dest_path.write_bytes(content)
        image_url = f"{IMAGE_BASE_URL}/{dest_filename}"

        # Actualizar la entidad correspondiente
        if kind == "product":
            product_repo.update_image_url(target.id, image_url)
            log_admin_action(user_id=user_id, action="upload_image", resource=f"product:{target.slug}", ip=ip)
            results.append({
                "file": filename,
                "status": "ok",
                "kind": "product",
                "slug": target.slug,
                "name": target.name,
                "image_url": image_url,
            })
        else:  # category
            category_repo.update(target.id, {"image_url": image_url})
            log_admin_action(user_id=user_id, action="upload_image", resource=f"category:{target.slug}", ip=ip)
            results.append({
                "file": filename,
                "status": "ok",
                "kind": "category",
                "slug": target.slug,
                "name": target.name,
                "image_url": image_url,
            })

    ok_count = sum(1 for r in results if r["status"] == "ok")
    error_count = len(results) - ok_count

    return JSONResponse(
        status_code=207,
        content={
            "summary": {"total": len(results), "ok": ok_count, "errors": error_count},
            "results": results,
        },
    )


@router.get("/list")
@limiter.limit("30/minute")
def list_images(
    request: Request,
    _admin: dict = Depends(get_current_admin),
):
    """Lista todas las imágenes subidas con su nombre de archivo."""
    files = [
        {"filename": f.name, "url": f"{IMAGE_BASE_URL}/{f.name}", "size_kb": round(f.stat().st_size / 1024, 1)}
        for f in sorted(IMAGES_DIR.iterdir())
        if f.is_file() and f.suffix.lower() in ALLOWED_EXTENSIONS
    ]
    return {"total": len(files), "images": files}
