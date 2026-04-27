"""
Migración one-shot:
1. Añade columna display_order a categories (si falta)
2. Renombra las 8 categorías existentes a nombres/slugs cortos
3. Inserta las 5 categorías nuevas
4. Asigna display_order según el orden definido por Induretros

Idempotente — se puede ejecutar varias veces sin romper nada.

Uso:
    python migrate_categories.py
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from sqlalchemy import text
from app.database import engine, SessionLocal, Base
import app.infrastructure.database.models  # noqa: F401 — registra modelos
from app.infrastructure.database.models.product_model import CategoryModel

Base.metadata.create_all(bind=engine)

db = SessionLocal()

# ── 1. Añadir columna display_order si falta ──
existing_cols = [row[1] for row in db.execute(text("PRAGMA table_info(categories)")).fetchall()]
if "display_order" not in existing_cols:
    db.execute(text("ALTER TABLE categories ADD COLUMN display_order INTEGER DEFAULT 0 NOT NULL"))
    print("  [OK] columna display_order añadida a categories")
else:
    print("  [-] display_order ya existía")
db.commit()

# ── 2. Plan completo: 13 categorías en el orden de Induretros ──
# Mapeo (old_slug, new_name, new_slug, display_order). old_slug=None → categoría nueva.
PLAN = [
    ("accesorios-maquinaria-pesada",          "Accesorios",                              "accesorios",                              1),
    ("balineras-para-maquinaria-pesada",      "Balineras",                               "balineras",                               2),
    ("empaquetaduras-para-maquinaria-pesada", "Empaquetadura",                           "empaquetadura",                           3),
    ("filtros-para-maquinaria-pesada",        "Filtros",                                 "filtros",                                 4),
    ("partes-electricas",                     "Partes eléctricas",                       "partes-electricas",                       5),
    (None,                                    "Partes completas y estructura",           "partes-completas-y-estructura",           6),
    ("lubricantes-para-maquinaria-pesada",    "Lubricantes",                             "lubricantes",                             7),
    (None,                                    "Partes de motor",                         "partes-de-motor",                         8),
    ("partes-hidraulicas",                    "Partes hidráulicas",                      "partes-hidraulicas",                      9),
    (None,                                    "Tren de rodaje",                          "tren-de-rodaje",                          10),
    (None,                                    "Reductores",                              "reductores",                              11),
    ("piezas-de-desgaste",                    "Piezas de desgaste",                      "piezas-de-desgaste",                      12),
    (None,                                    "Válvulas, solenoides y electroválvulas",  "valvulas-solenoides-y-electrovalvulas",  13),
]

renamed = added = skipped = 0

for old_slug, new_name, new_slug, order in PLAN:
    # Si ya existe alguien con el slug nuevo, solo asegurar el display_order y nombre
    by_new_slug = db.query(CategoryModel).filter(CategoryModel.slug == new_slug).first()
    if by_new_slug:
        if by_new_slug.name != new_name or by_new_slug.display_order != order:
            by_new_slug.name = new_name
            by_new_slug.display_order = order
            db.commit()
            print(f"  [UPDATE] {new_slug:<45} order={order}")
        else:
            skipped += 1
        continue

    # ¿Hay una categoría vieja con old_slug que toca renombrar?
    if old_slug:
        existing = db.query(CategoryModel).filter(CategoryModel.slug == old_slug).first()
        if existing:
            existing.name = new_name
            existing.slug = new_slug
            existing.display_order = order
            db.commit()
            renamed += 1
            print(f"  [RENAME] {old_slug:<45} -> {new_slug} (order={order})")
            continue

    # Si no, insertar nueva
    db.add(CategoryModel(
        name=new_name,
        slug=new_slug,
        display_order=order,
    ))
    db.commit()
    added += 1
    print(f"  [NEW]    {new_slug:<45} order={order}")

db.close()

print(f"""
{'-'*60}
  Renombradas: {renamed}
  Nuevas:      {added}
  Sin cambios: {skipped}
{'-'*60}

Migracion completada.
""")
