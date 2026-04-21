"""Script para poblar la base de datos con datos de prueba."""
from app.database import engine, SessionLocal, Base
import app.infrastructure.database.models  # noqa: F401 — registra todos los modelos en Base.metadata
from app.infrastructure.database.models.product_model import ProductModel, CategoryModel

Base.metadata.create_all(bind=engine)

db = SessionLocal()

# Limpiar datos anteriores
db.query(ProductModel).delete()
db.query(CategoryModel).delete()
db.commit()

# Categorías
cats_data = [
    {"name": "Accesorios para maquinaria pesada", "slug": "accesorios-maquinaria-pesada"},
    {"name": "Balineras para maquinaria pesada", "slug": "balineras-para-maquinaria-pesada"},
    {"name": "Filtros para maquinaria pesada", "slug": "filtros-para-maquinaria-pesada"},
    {"name": "Partes hidráulicas", "slug": "partes-hidraulicas"},
    {"name": "Partes eléctricas", "slug": "partes-electricas"},
    {"name": "Piezas de desgaste", "slug": "piezas-de-desgaste"},
    {"name": "Empaquetaduras", "slug": "empaquetaduras-para-maquinaria-pesada"},
    {"name": "Lubricantes", "slug": "lubricantes-para-maquinaria-pesada"},
]

cats = {}
for c in cats_data:
    obj = CategoryModel(**c)
    db.add(obj)
    db.flush()
    cats[c["slug"]] = obj.id

db.commit()

# Productos de muestra
products_data = [
    # Filtros
    {"name": "Filtro de aceite para excavadora Komatsu PC200", "slug": "filtro-aceite-komatsu-pc200", "sku": "FLT-001", "price": 45000, "category_id": cats["filtros-para-maquinaria-pesada"], "in_stock": True, "featured": True, "short_description": "Filtro de aceite original para excavadora Komatsu PC200. Alta eficiencia de filtración."},
    {"name": "Filtro hidráulico excavadora Caterpillar 320", "slug": "filtro-hidraulico-caterpillar-320", "sku": "FLT-002", "price": 78000, "category_id": cats["filtros-para-maquinaria-pesada"], "in_stock": True, "featured": True, "short_description": "Filtro hidráulico de alta presión compatible con Caterpillar 320D y 320E."},
    {"name": "Filtro de aire primario Hitachi ZX200", "slug": "filtro-aire-hitachi-zx200", "sku": "FLT-003", "price": 55000, "category_id": cats["filtros-para-maquinaria-pesada"], "in_stock": True, "featured": False},
    {"name": "Filtro de combustible Volvo EC210", "slug": "filtro-combustible-volvo-ec210", "sku": "FLT-004", "price": 38000, "category_id": cats["filtros-para-maquinaria-pesada"], "in_stock": True, "featured": False},
    # Partes hidráulicas
    {"name": "Bomba hidráulica principal Komatsu PC300", "slug": "bomba-hidraulica-komatsu-pc300", "sku": "HID-001", "price": 4500000, "category_id": cats["partes-hidraulicas"], "in_stock": True, "featured": True, "short_description": "Bomba hidráulica de pistones axiales para Komatsu PC300-7 y PC300-8."},
    {"name": "Motor de giro Caterpillar 330", "slug": "motor-giro-caterpillar-330", "sku": "HID-002", "price": 3200000, "category_id": cats["partes-hidraulicas"], "in_stock": False, "featured": False},
    {"name": "Válvula de control principal Hyundai R210", "slug": "valvula-control-hyundai-r210", "sku": "HID-003", "price": 1850000, "category_id": cats["partes-hidraulicas"], "in_stock": True, "featured": True},
    {"name": "Cilindro de cuchara Komatsu PC200", "slug": "cilindro-cuchara-komatsu-pc200", "sku": "HID-004", "price": 980000, "category_id": cats["partes-hidraulicas"], "in_stock": True, "featured": False},
    # Balineras
    {"name": "Balinera de bomba hidráulica Komatsu", "slug": "balinera-bomba-hidraulica-komatsu", "sku": "BAL-001", "price": 125000, "category_id": cats["balineras-para-maquinaria-pesada"], "in_stock": True, "featured": False},
    {"name": "Balinera de motor de traslación Caterpillar", "slug": "balinera-motor-traslacion-caterpillar", "sku": "BAL-002", "price": 185000, "category_id": cats["balineras-para-maquinaria-pesada"], "in_stock": True, "featured": False},
    # Piezas de desgaste
    {"name": "Dientes de balde tipo Esco J350", "slug": "dientes-balde-esco-j350", "sku": "DES-001", "price": 95000, "regular_price": 120000, "sale_price": 95000, "category_id": cats["piezas-de-desgaste"], "in_stock": True, "featured": True, "short_description": "Dientes de balde tipo Esco J350, adaptador incluido. Para excavadoras de 20 toneladas."},
    {"name": "Cuchilla lateral para balde excavadora", "slug": "cuchilla-lateral-balde-excavadora", "sku": "DES-002", "price": 145000, "category_id": cats["piezas-de-desgaste"], "in_stock": True, "featured": False},
    # Accesorios
    {"name": "Espejo retrovisor panorámico excavadora", "slug": "espejo-retrovisor-panoramico-excavadora", "sku": "ACC-001", "price": 85000, "category_id": cats["accesorios-maquinaria-pesada"], "in_stock": True, "featured": False},
    {"name": "Medidor de temperatura motor Komatsu", "slug": "medidor-temperatura-motor-komatsu", "sku": "ACC-002", "price": 220000, "category_id": cats["accesorios-maquinaria-pesada"], "in_stock": True, "featured": True},
    # Partes eléctricas
    {"name": "Alternador Komatsu PC200-7", "slug": "alternador-komatsu-pc200-7", "sku": "ELE-001", "price": 650000, "category_id": cats["partes-electricas"], "in_stock": True, "featured": False},
    {"name": "Sensor de presión hidráulica Caterpillar", "slug": "sensor-presion-hidraulica-caterpillar", "sku": "ELE-002", "price": 180000, "category_id": cats["partes-electricas"], "in_stock": True, "featured": True},
]

for p in products_data:
    db.add(ProductModel(**p))

db.commit()
db.close()
print(f"OK: Base de datos creada con {len(cats_data)} categorías y {len(products_data)} productos.")
