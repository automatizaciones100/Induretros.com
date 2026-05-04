"""
Migración one-shot: siembra páginas de aterrizaje SEO en legal_pages.
Idempotente — sólo inserta si el slug no existe.

Convención de slugs:
  - 'nosotros'         → /nosotros
  - 'marca-{X}'        → /marcas/{X}
  - 'ciudad-{X}'       → /ciudades/{X}

Uso:
    python migrate_landing_pages.py
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from app.database import engine, SessionLocal, Base
import app.infrastructure.database.models  # noqa: F401
from app.infrastructure.database.models.legal_page_model import LegalPageModel

Base.metadata.create_all(bind=engine)

NOSOTROS_CONTENT = """
<h2>Quienes somos</h2>
<p>Induretros es una empresa colombiana especializada en la <strong>importacion y
venta de repuestos para excavadoras hidraulicas y maquinaria pesada</strong>.
Llevamos mas de 9 anos sirviendo a operadores, contratistas y empresas de
construccion en toda Colombia y la region.</p>

<h2>Que hacemos</h2>
<p>Importamos directamente desde fabrica los repuestos que necesitas para
mantener tu maquinaria operativa. Eliminamos intermediarios para entregarte
mejor precio y disponibilidad inmediata de las referencias mas solicitadas.</p>
<ul>
  <li>Repuestos para todas las marcas: Caterpillar, Komatsu, Kobelco,
      Hyundai, Doosan, Sany, Volvo, Case, Hitachi, entre otras.</li>
  <li>Filtros, balineras, partes hidraulicas y electricas, lubricantes,
      empaquetaduras, tren de rodaje, piezas de desgaste y mas.</li>
  <li>Asesoria personalizada para identificar la referencia exacta del
      repuesto que necesitas.</li>
</ul>

<h2>Por que elegirnos</h2>
<ul>
  <li><strong>Importadores directos</strong>: traemos los repuestos de fabrica.</li>
  <li><strong>+9 anos de experiencia</strong>: conocemos cada modelo y cada parte.</li>
  <li><strong>Garantia original</strong>: respaldo postventa en cada compra.</li>
  <li><strong>Atencion humana</strong>: un asesor real que te ayuda por WhatsApp.</li>
</ul>

<h2>Cobertura</h2>
<p>Despachamos a toda Colombia y atendemos clientes en Peru, Ecuador, Chile,
Mexico y otros paises de la region. Nuestra base esta en Medellin.</p>

<h2>Contactanos</h2>
<p>Si tienes una excavadora parada o necesitas cotizar un repuesto, escribenos
por <a href="https://wa.me/573007192973" target="_blank" rel="noopener noreferrer">WhatsApp</a>
o desde el <a href="/contacto">formulario de contacto</a>. Tenemos asesores
listos para ayudarte.</p>
""".strip()


def brand_content(name: str) -> str:
    return (f"""
<h2>Repuestos {name} en Colombia</h2>
<p>En Induretros somos especialistas en repuestos para maquinaria
<strong>{name}</strong>. Importamos directamente las partes mas solicitadas
para excavadoras y maquinaria pesada de esta marca, con disponibilidad
inmediata y los mejores precios del mercado colombiano.</p>

<h2>Que repuestos {name} manejamos</h2>
<ul>
  <li>Filtros de aceite, aire, combustible e hidraulico</li>
  <li>Bombas hidraulicas, motores de giro y motores de traslacion</li>
  <li>Partes electricas: alternadores, sensores, cableados, switches</li>
  <li>Empaquetaduras, sellos, oring y juntas</li>
  <li>Tren de rodaje: zapatas, sprockets, ruedas guia, cadenas</li>
  <li>Piezas de desgaste: dientes, bujes, pasadores</li>
  <li>Lubricantes y aceites recomendados por fabrica</li>
</ul>

<h2>Garantia y respaldo</h2>
<p>Todos los repuestos {name} que vendemos cuentan con garantia de fabrica.
Si tienes dudas sobre la referencia exacta, nuestro equipo tecnico te ayuda
a identificarla con la informacion de tu maquina (modelo, ano, numero de
serie).</p>

<h2>Pide tu cotizacion {name}</h2>
<p>Escribenos por WhatsApp con el numero de la pieza o describenos lo que
necesitas. Te cotizamos de inmediato y te enviamos a cualquier ciudad de
Colombia.</p>
""").strip()


def city_content(city: str) -> str:
    return (f"""
<h2>Repuestos para excavadoras en {city}</h2>
<p>En Induretros despachamos repuestos para excavadoras hidraulicas y
maquinaria pesada a toda <strong>{city}</strong> y municipios cercanos.
Llevamos mas de 9 anos sirviendo a operadores y empresas de construccion
de la region con repuestos importados directamente de fabrica.</p>

<h2>Tiempo de entrega a {city}</h2>
<p>Despachamos por Servientrega, Envia o Coordinadora. Para {city} y zonas
metropolitanas el tiempo estimado es de 1 a 3 dias habiles dependiendo de
la transportadora y la disponibilidad de la pieza.</p>

<h2>Que pides desde {city}</h2>
<ul>
  <li>Filtros, balineras y empaquetaduras para excavadoras</li>
  <li>Bombas hidraulicas, motores de giro y de traslacion</li>
  <li>Partes electricas: alternadores, monitores, sensores</li>
  <li>Tren de rodaje, dientes y partes de desgaste</li>
  <li>Lubricantes y aceites para maquinaria pesada</li>
</ul>

<h2>Marcas que cubrimos en {city}</h2>
<p>Importamos repuestos para Caterpillar, Komatsu, Kobelco, Hyundai, Doosan,
Sany, Volvo, Case, Hitachi y mas. Si tu maquina es de otra marca, escribenos
por WhatsApp para verificar disponibilidad.</p>

<h2>Pide cotizacion desde {city}</h2>
<p>Escribenos por WhatsApp con la referencia o descripcion del repuesto que
necesitas, te cotizamos al instante y coordinamos el envio.</p>
""").strip()


SEEDS = []

# Página "Nosotros"
SEEDS.append(("nosotros", "Sobre Induretros — Repuestos para maquinaria pesada", NOSOTROS_CONTENT))

# Marcas (a partir del listado de search console)
BRANDS = [
    ("caterpillar", "Caterpillar"),
    ("komatsu", "Komatsu"),
    ("kobelco", "Kobelco"),
    ("hyundai", "Hyundai"),
    ("doosan", "Doosan"),
    ("sany", "Sany"),
    ("volvo", "Volvo"),
    ("case", "Case"),
    ("kawasaki", "Kawasaki"),
    ("hitachi", "Hitachi"),
    ("liugong", "LiuGong"),
    ("kato", "Kato"),
]
for slug, name in BRANDS:
    SEEDS.append((
        f"marca-{slug}",
        f"Repuestos {name} en Colombia — Induretros",
        brand_content(name),
    ))

# Ciudades (de search console y queries locales)
CITIES = [
    ("medellin", "Medellin"),
    ("bogota", "Bogota"),
    ("cali", "Cali"),
    ("barranquilla", "Barranquilla"),
    ("bucaramanga", "Bucaramanga"),
    ("cucuta", "Cucuta"),
    ("pereira", "Pereira"),
    ("monteria", "Monteria"),
]
for slug, city in CITIES:
    SEEDS.append((
        f"ciudad-{slug}",
        f"Repuestos para excavadoras en {city} — Induretros",
        city_content(city),
    ))

db = SessionLocal()
inserted = 0
skipped = 0
for slug, title, content in SEEDS:
    if db.query(LegalPageModel).filter(LegalPageModel.slug == slug).first() is None:
        db.add(LegalPageModel(slug=slug, title=title, content=content))
        inserted += 1
    else:
        skipped += 1
db.commit()
db.close()
print(f"  [OK] Inserted: {inserted} pages")
print(f"  [-]  Skipped (already existed): {skipped} pages")
print(f"\nTotal seeded: {len(SEEDS)} landing pages")
print("\nMigracion completada.")
