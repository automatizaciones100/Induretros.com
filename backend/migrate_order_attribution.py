"""
Migración one-shot: agrega columnas de atribución de marketing a la tabla orders.
Idempotente — sólo agrega columnas que no existen.

Capa 1 del plan de UTM tracking: persistir la fuente del pedido (UTM, gclid,
landing page) para luego analizar cuáles campañas convierten.

Uso:
    python migrate_order_attribution.py
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from sqlalchemy import inspect, text
from app.database import engine

ATTRIBUTION_COLUMNS = [
    ("utm_source", "VARCHAR(100)"),
    ("utm_medium", "VARCHAR(50)"),
    ("utm_campaign", "VARCHAR(150)"),
    ("utm_term", "VARCHAR(150)"),
    ("utm_content", "VARCHAR(150)"),
    ("gclid", "VARCHAR(255)"),
    ("landing_page", "VARCHAR(500)"),
]

inspector = inspect(engine)
existing_columns = {col["name"] for col in inspector.get_columns("orders")}

added = 0
skipped = 0
with engine.begin() as conn:
    for col_name, col_type in ATTRIBUTION_COLUMNS:
        if col_name in existing_columns:
            skipped += 1
            continue
        conn.execute(text(f"ALTER TABLE orders ADD COLUMN {col_name} {col_type}"))
        added += 1
        print(f"  [OK] columna agregada: {col_name} ({col_type})")

# Índices opcionales (utilidad para reportes admin)
indices_existing = {idx["name"] for idx in inspector.get_indexes("orders")}
indices_to_add = [
    ("ix_orders_utm_source", "utm_source"),
    ("ix_orders_utm_campaign", "utm_campaign"),
]
with engine.begin() as conn:
    for idx_name, col in indices_to_add:
        if idx_name in indices_existing or col not in {c[0] for c in ATTRIBUTION_COLUMNS}:
            continue
        # Sólo crear el índice si la columna ya existe (recién insertada o pre-existente)
        try:
            conn.execute(text(f"CREATE INDEX IF NOT EXISTS {idx_name} ON orders ({col})"))
            print(f"  [OK] índice creado: {idx_name}")
        except Exception as e:
            print(f"  [skip] {idx_name}: {e}")

print(f"\nResumen: {added} columnas agregadas, {skipped} ya existían.")
print("Migracion completada.")
