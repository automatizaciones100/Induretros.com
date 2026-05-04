"""
Migración one-shot: inserta las páginas legales 'terminos' y 'devoluciones'
con contenido por defecto si no existen. Idempotente.

Uso:
    python migrate_legal_seed_terms_returns.py
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from app.database import engine, SessionLocal, Base
import app.infrastructure.database.models  # noqa: F401
from app.infrastructure.database.models.legal_page_model import LegalPageModel

Base.metadata.create_all(bind=engine)

DEFAULT_TERMS = """
<h2>Acerca de estos terminos</h2>
<p>Bienvenido a Induretros. Al usar este sitio (induretros.com) aceptas
los siguientes terminos y condiciones. Te recomendamos leerlos antes de
realizar un pedido.</p>

<h2>Quienes somos</h2>
<p>Induretros es una empresa colombiana dedicada a la importacion y venta
de repuestos para excavadoras hidraulicas y maquinaria pesada, con
domicilio principal en Medellin, Colombia.</p>

<h2>Pedidos y precios</h2>
<ul>
  <li>Los precios mostrados en el sitio estan en pesos colombianos (COP)
      e incluyen IVA cuando aplica.</li>
  <li>Los pedidos se confirman cuando un asesor te contacta por WhatsApp
      tras recibirlos en el sistema.</li>
  <li>Nos reservamos el derecho de rechazar o cancelar un pedido si hay
      error de stock, error tipografico de precio o sospecha de fraude.</li>
  <li>El precio final del pedido incluye el costo de envio que se
      informa al confirmar.</li>
</ul>

<h2>Pagos</h2>
<p>Por ahora coordinamos los pagos por WhatsApp con el asesor asignado.
Aceptamos transferencia bancaria, Bancolombia a la mano, Daviplata y
consignacion. Toda transaccion queda respaldada por el comprobante
correspondiente.</p>

<h2>Envios</h2>
<p>Realizamos envios a toda Colombia a traves de Servientrega, Envia y
Coordinadora. Los tiempos estimados son de 1 a 3 dias habiles a las
ciudades principales y de 3 a 5 dias habiles a municipios. Estos plazos
dependen de la transportadora y no son responsabilidad directa de
Induretros.</p>

<h2>Garantia y devoluciones</h2>
<p>Todos los repuestos cuentan con garantia de fabrica. Para conocer la
cobertura, exclusiones y proceso, consulta la
<a href="/garantia">politica de garantia</a> y la
<a href="/devoluciones">politica de devoluciones y cambios</a>.</p>

<h2>Propiedad intelectual</h2>
<p>El logo, las imagenes, los textos y el diseño del sitio son propiedad
de Induretros o de sus respectivos licenciantes. No esta permitido su
uso comercial sin autorizacion escrita.</p>

<h2>Limitacion de responsabilidad</h2>
<p>Induretros no se responsabiliza por daños derivados de la instalacion
incorrecta de los repuestos, mal uso de la maquinaria o uso por personal
no calificado. La instalacion debe realizarla un tecnico con experiencia
en el sistema correspondiente.</p>

<h2>Datos personales</h2>
<p>Los datos que nos proporcionas (nombre, telefono, correo, direccion)
se tratan de acuerdo a nuestra
<a href="/privacidad">politica de privacidad</a>, en cumplimiento de la
Ley 1581 de 2012 de Colombia.</p>

<h2>Modificaciones</h2>
<p>Podemos actualizar estos terminos en cualquier momento. Los cambios
se aplican desde su publicacion en esta pagina. La fecha de la ultima
actualizacion aparece al pie del documento.</p>

<h2>Contacto</h2>
<p>Si tienes dudas sobre estos terminos, escribenos por WhatsApp o
desde la pagina <a href="/contacto">de contacto</a>.</p>
""".strip()

DEFAULT_RETURNS = """
<h2>Tu derecho a retracto</h2>
<p>De acuerdo con el Estatuto del Consumidor (Ley 1480 de 2011), tienes
derecho a retractarte de tu compra dentro de los <strong>5 dias habiles</strong>
siguientes a la entrega del pedido, siempre que el producto no haya sido
utilizado.</p>

<h2>Cuando aplica un cambio o devolucion</h2>
<ul>
  <li>El producto llego con un defecto de fabricacion (ver
      <a href="/garantia">politica de garantia</a>).</li>
  <li>Recibiste un repuesto distinto al que pediste.</li>
  <li>El repuesto sufrio daño durante el transporte (debe reportarse en
      las primeras 24 horas tras la entrega).</li>
  <li>Te retractas dentro de los 5 dias habiles, con el producto sin
      uso, en su empaque original.</li>
</ul>

<h2>Cuando NO aplica</h2>
<ul>
  <li>El repuesto fue instalado y posteriormente se desea devolver
      "porque no era lo que necesitaba". En estos casos podemos
      coordinar un cambio si el producto esta en perfecto estado.</li>
  <li>Daños por instalacion incorrecta o uso inadecuado.</li>
  <li>Productos personalizados o pedidos especiales por encargo.</li>
  <li>Repuestos consumibles (filtros, empaques) ya usados.</li>
</ul>

<h2>Como solicitar un cambio o devolucion</h2>
<ol>
  <li>Contactanos por <strong>WhatsApp</strong> dentro del periodo aplicable
      con la factura del pedido.</li>
  <li>Envianos fotos o video del repuesto, su empaque original y el motivo
      del cambio o devolucion.</li>
  <li>Nuestro equipo revisara el caso en un plazo de
      <strong>3 a 5 dias habiles</strong> y te confirmara si procede.</li>
  <li>Si procede, coordinamos la recoleccion del producto. El costo del
      transporte de devolucion lo asume Induretros cuando se trata de un
      defecto de fabricacion o error nuestro; si es retracto del cliente,
      el costo lo asume el cliente.</li>
  <li>Una vez recibido y verificado el producto, procesamos el reembolso
      o el envio del repuesto de cambio en un plazo de
      <strong>5 a 10 dias habiles</strong>.</li>
</ol>

<h2>Forma del reembolso</h2>
<p>El reembolso se realiza por el mismo medio que se uso para el pago
(transferencia bancaria a la cuenta del comprador). El monto incluye
el valor del producto; el costo de envio original solo se reembolsa si
la devolucion se debe a un error de Induretros.</p>

<h2>Contacto</h2>
<p>Para cualquier caso de garantia, cambio o devolucion escribenos por
<a href="https://wa.me/573007192973" target="_blank" rel="noopener noreferrer">WhatsApp</a>
o usa el <a href="/contacto">formulario de contacto</a>.</p>
""".strip()

SEEDS = [
    ("terminos", "Terminos y condiciones", DEFAULT_TERMS),
    ("devoluciones", "Politica de devoluciones y cambios", DEFAULT_RETURNS),
]

db = SessionLocal()
for slug, title, content in SEEDS:
    if db.query(LegalPageModel).filter(LegalPageModel.slug == slug).first() is None:
        db.add(LegalPageModel(slug=slug, title=title, content=content))
        print(f"  [OK] pagina '{slug}' insertada")
    else:
        print(f"  [-]  pagina '{slug}' ya existe, no se sobrescribe")
db.commit()
db.close()
print("\nMigracion completada.")
