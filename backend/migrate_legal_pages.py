"""
Migración one-shot: crea la tabla legal_pages e inserta la garantía
con contenido por defecto si está vacía. Idempotente.

Uso:
    python migrate_legal_pages.py
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from app.database import engine, SessionLocal, Base
import app.infrastructure.database.models  # noqa: F401
from app.infrastructure.database.models.legal_page_model import LegalPageModel

Base.metadata.create_all(bind=engine)
print("  [OK] tabla legal_pages creada o verificada")

DEFAULT_WARRANTY = """
<h2>Garantia de fabrica</h2>
<p>En Induretros respaldamos la calidad de cada repuesto que vendemos.
Todos nuestros productos cuentan con <strong>garantia de fabrica</strong>
contra defectos de fabricacion.</p>

<h2>Cobertura</h2>
<ul>
  <li>La garantia cubre <strong>defectos de fabricacion</strong> del repuesto.</li>
  <li>Aplica desde la fecha de entrega y se demuestra con la factura o
      comprobante del pedido.</li>
  <li>La duracion depende del tipo de producto: desde 30 dias en piezas
      de desgaste hasta 1 ano en componentes principales.</li>
</ul>

<h2>Que no cubre</h2>
<ul>
  <li>Daños por instalacion incorrecta o falta de mano de obra calificada.</li>
  <li>Desgaste normal por uso intensivo o mal uso del repuesto.</li>
  <li>Daños por golpes, contaminacion del fluido hidraulico, sobrecarga
      o accidentes.</li>
  <li>Modificaciones o reparaciones realizadas por terceros sin nuestra
      autorizacion.</li>
</ul>

<h2>Como reclamar la garantia</h2>
<ol>
  <li>Contactanos por <strong>WhatsApp</strong> al numero oficial dentro
      del periodo de cobertura.</li>
  <li>Envianos fotos o video del repuesto, la falla observada y la factura
      del pedido.</li>
  <li>Nuestro equipo tecnico revisara el caso en un plazo de
      <strong>3 a 5 dias habiles</strong>.</li>
  <li>Si la garantia procede, coordinamos el cambio del repuesto o la
      devolucion del dinero, segun el caso.</li>
</ol>

<p>Para cualquier duda escribenos por WhatsApp y un asesor te orientara
en el proceso.</p>
""".strip()

db = SessionLocal()
existing = db.query(LegalPageModel).filter(LegalPageModel.slug == "garantia").first()
if existing is None:
    db.add(LegalPageModel(
        slug="garantia",
        title="Politica de garantia",
        content=DEFAULT_WARRANTY,
    ))
    db.commit()
    print("  [OK] pagina 'garantia' insertada con contenido por defecto")
else:
    print("  [-]  pagina 'garantia' ya existia, no se sobrescribe")

db.close()
print("\nMigracion completada.")
