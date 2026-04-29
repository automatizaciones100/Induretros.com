"""
Importador masivo de productos desde CSV.

Uso:
    python import_products.py productos.csv
    python import_products.py productos.csv --dry-run    # solo validar, no escribir

Comportamiento:
    - Si el SKU del producto YA existe en la BD  →  UPDATE
        (preserva el ID, los pedidos vinculados, las imágenes ya subidas
         y los campos no provistos en el CSV)
    - Si el SKU NO existe                         →  CREATE

Columnas del CSV (header obligatorio en la primera fila):
    sku, name, slug, category_slug, price, regular_price, sale_price,
    stock, in_stock, short_description, description, featured, image_url,
    meta_title, meta_description

Columnas obligatorias:  sku, name, slug
Columnas opcionales:    todas las demás (campos vacíos se interpretan como NULL)
                        en UPDATE, las columnas no incluidas en el CSV se preservan

Booleanos: 'true', '1', 'yes', 'sí' -> True. Cualquier otro valor -> False.
Precios:   números decimales o vacío. Comas y signos $ se ignoran.
Stock:     entero o vacío (vacío -> 0).

Encoding: UTF-8 (con o sin BOM, lo limpia automáticamente).
"""
import csv
import re
import sys
from pathlib import Path
from typing import Optional

sys.path.insert(0, str(Path(__file__).parent))

from app.database import SessionLocal, Base, engine
import app.infrastructure.database.models  # noqa: F401
from app.infrastructure.database.models.product_model import ProductModel, CategoryModel

Base.metadata.create_all(bind=engine)

REQUIRED_COLUMNS = {"sku", "name", "slug"}
OPTIONAL_COLUMNS = {
    "category_slug", "price", "regular_price", "sale_price",
    "stock", "in_stock", "short_description", "description",
    "featured", "image_url", "meta_title", "meta_description",
}
ALL_COLUMNS = REQUIRED_COLUMNS | OPTIONAL_COLUMNS

SLUG_PATTERN = re.compile(r"^[a-z0-9]+(?:-[a-z0-9]+)*$")


def _norm(s: Optional[str]) -> Optional[str]:
    if s is None:
        return None
    s = s.strip()
    return s if s else None


def _parse_bool(s: Optional[str], default: bool = False) -> bool:
    if s is None:
        return default
    s = s.strip().lower()
    return s in ("true", "1", "yes", "sí", "si", "y", "x")


def _parse_decimal(s: Optional[str]) -> Optional[float]:
    if s is None or not s.strip():
        return None
    cleaned = re.sub(r"[$,\s]", "", s.strip())
    try:
        v = float(cleaned)
        return v if v >= 0 else None
    except ValueError:
        return None


def _parse_int(s: Optional[str], default: int = 0) -> int:
    if s is None or not s.strip():
        return default
    try:
        return max(0, int(re.sub(r"[,\s]", "", s.strip())))
    except ValueError:
        return default


def _validate_row(row: dict, line_no: int, categories_by_slug: dict) -> tuple[Optional[dict], Optional[str]]:
    """Devuelve (clean_row, None) o (None, error_message)."""
    sku = _norm(row.get("sku"))
    name = _norm(row.get("name"))
    slug = _norm(row.get("slug"))

    if not sku:
        return None, "sku vacío"
    if not name:
        return None, "name vacío"
    if not slug:
        return None, "slug vacío"
    if not SLUG_PATTERN.match(slug):
        return None, f"slug inválido '{slug}' (solo a-z, 0-9, guiones)"
    if len(name) > 200:
        return None, f"name > 200 caracteres ({len(name)})"

    # Categoría: por slug
    cat_slug = _norm(row.get("category_slug"))
    cat_id = None
    if cat_slug:
        cat = categories_by_slug.get(cat_slug)
        if not cat:
            return None, f"category_slug '{cat_slug}' no existe en la BD"
        cat_id = cat.id

    return {
        "sku": sku,
        "name": name,
        "slug": slug,
        "category_id": cat_id,
        "price": _parse_decimal(row.get("price")),
        "regular_price": _parse_decimal(row.get("regular_price")),
        "sale_price": _parse_decimal(row.get("sale_price")),
        "stock": _parse_int(row.get("stock"), 0),
        "in_stock": _parse_bool(row.get("in_stock"), True),
        "short_description": _norm(row.get("short_description")),
        "description": _norm(row.get("description")),
        "featured": _parse_bool(row.get("featured"), False),
        "image_url": _norm(row.get("image_url")),
        "meta_title": _norm(row.get("meta_title")),
        "meta_description": _norm(row.get("meta_description")),
    }, None


def run(csv_path: Path, dry_run: bool = False) -> int:
    if not csv_path.is_file():
        print(f"[ERROR] '{csv_path}' no es un archivo válido.")
        return 1

    db = SessionLocal()

    # Cargar categorías una vez (para resolver slugs rápidamente)
    categories_by_slug = {c.slug: c for c in db.query(CategoryModel).all()}
    print(f"\nCategorias en BD: {len(categories_by_slug)}")
    print(f"Procesando '{csv_path}'{' (DRY RUN)' if dry_run else ''}...\n")

    # Detectar BOM y abrir con encoding utf-8-sig (limpia el BOM si existe)
    with csv_path.open("r", encoding="utf-8-sig", newline="") as f:
        reader = csv.DictReader(f)
        # Validar columnas
        provided = set(reader.fieldnames or [])
        missing = REQUIRED_COLUMNS - provided
        unknown = provided - ALL_COLUMNS
        if missing:
            print(f"[ERROR] Faltan columnas obligatorias: {sorted(missing)}")
            db.close()
            return 1
        if unknown:
            print(f"[WARN]  Columnas desconocidas (se ignoran): {sorted(unknown)}")

        created = updated = errors = 0
        for line_no, row in enumerate(reader, start=2):  # start=2 porque header es línea 1
            clean, err = _validate_row(row, line_no, categories_by_slug)
            if err:
                print(f"  [ERROR L{line_no:>3}]  {row.get('sku', '?')}  {err}")
                errors += 1
                continue

            existing = db.query(ProductModel).filter(ProductModel.sku == clean["sku"]).first()
            if existing:
                if not dry_run:
                    for key, value in clean.items():
                        setattr(existing, key, value)
                    db.commit()
                print(f"  [UPDATE L{line_no:>3}]  {clean['sku']:<12}  {clean['name'][:60]}")
                updated += 1
            else:
                if not dry_run:
                    db.add(ProductModel(**clean))
                    db.commit()
                print(f"  [CREATE L{line_no:>3}]  {clean['sku']:<12}  {clean['name'][:60]}")
                created += 1

    db.close()

    print(f"""
{'-'*65}
  Total procesados:  {created + updated + errors}
  Creados:           {created}
  Actualizados:      {updated}
  Con error:         {errors}
{'-'*65}
""")
    if dry_run:
        print("DRY RUN: ningun cambio aplicado a la BD.\n")
    return 0 if errors == 0 else 2


if __name__ == "__main__":
    args = sys.argv[1:]
    if not args or args[0] in ("-h", "--help"):
        print(__doc__)
        sys.exit(0)

    dry_run = "--dry-run" in args
    csv_arg = next((a for a in args if not a.startswith("--")), None)
    if not csv_arg:
        print("[ERROR] Falta la ruta al CSV. Ejecuta con --help para ver el uso.")
        sys.exit(1)

    sys.exit(run(Path(csv_arg), dry_run=dry_run))
