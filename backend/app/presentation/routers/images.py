"""
Carga masiva de imágenes de productos.

Convención de nombres de archivo:
  - Por SKU:  FLT-001.jpg  →  busca producto con sku='FLT-001'
  - Por slug: filtro-aceite-komatsu-pc200.webp  →  busca producto con slug=...

Formatos aceptados: jpg, jpeg, png, webp, avif
Tamaño máximo por archivo: 5 MB
Máximo de archivos por request: 50
"""
import os
import re
import uuid
from pathlib import Path
from typing import Annotated

from fastapi import APIRouter, Depends, File, HTTPException, Request, UploadFile, status
from fastapi.responses import JSONResponse

from app.database import get_db
from app.infrastructure.database.repositories.product_repository import SQLAlchemyProductRepository
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
# URL base que el frontend usará para acceder a las imágenes
IMAGE_BASE_URL = "/static/images"


def _safe_filename(name: str) -> str:
    """Elimina caracteres peligrosos del nombre de archivo."""
    name = re.sub(r"[^a-zA-Z0-9._\-]", "_", name)
    return name[:200]


def _identify_product(stem: str, repo: SQLAlchemyProductRepository):
    """
    Intenta encontrar el producto por SKU primero, luego por slug.
    stem: nombre del archivo sin extensión (ej. 'FLT-001' o 'filtro-aceite-komatsu')
    """
    product = repo.get_by_sku(stem)
    if not product:
        product = repo.get_by_slug(stem)
    return product


@router.post("/upload", status_code=207)
@limiter.limit("10/minute")
async def upload_images(
    request: Request,
    files: Annotated[list[UploadFile], File(description="Imágenes de productos (máx. 50 archivos, 5 MB c/u)")],
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    """
    Subida masiva de imágenes de productos.

    Cada archivo debe llamarse igual que el SKU o slug del producto:
      - `FLT-001.jpg`   → asocia al producto con SKU 'FLT-001'
      - `filtro-aceite-komatsu-pc200.webp` → asocia al slug correspondiente

    Retorna un reporte con el resultado de cada archivo (207 Multi-Status).
    """
    if len(files) > MAX_FILES:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail=f"Máximo {MAX_FILES} archivos por request. Enviaste {len(files)}.",
        )

    repo = SQLAlchemyProductRepository(db)
    ip = request.client.host if request.client else "unknown"
    user_id = int(admin.get("sub", 0))

    results = []

    for file in files:
        filename = file.filename or ""
        ext = Path(filename).suffix.lower()
        stem = Path(filename).stem

        # Validar extensión
        if ext not in ALLOWED_EXTENSIONS:
            results.append({
                "file": filename,
                "status": "error",
                "detail": f"Formato no permitido '{ext}'. Usa: {', '.join(ALLOWED_EXTENSIONS)}",
            })
            continue

        # Validar tamaño
        content = await file.read()
        if len(content) > MAX_FILE_SIZE:
            results.append({
                "file": filename,
                "status": "error",
                "detail": f"Archivo demasiado grande ({len(content) // 1024} KB). Máximo 5 MB.",
            })
            continue

        # Buscar producto por SKU o slug
        product = _identify_product(stem, repo)
        if not product:
            results.append({
                "file": filename,
                "status": "not_found",
                "detail": f"No se encontró producto con SKU o slug '{stem}'",
            })
            continue

        # Guardar archivo con nombre único para evitar colisiones
        safe_stem = _safe_filename(stem)
        dest_filename = f"{safe_stem}{ext}"
        dest_path = IMAGES_DIR / dest_filename

        dest_path.write_bytes(content)

        # Actualizar imagen del producto en la BD
        image_url = f"{IMAGE_BASE_URL}/{dest_filename}"
        repo.update_image_url(product.id, image_url)

        log_admin_action(
            user_id=user_id,
            action="upload_image",
            resource=f"product:{product.slug}",
            ip=ip,
        )

        results.append({
            "file": filename,
            "status": "ok",
            "product_slug": product.slug,
            "product_name": product.name,
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
