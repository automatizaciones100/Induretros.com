"""
Migración one-shot: crea la tabla faq_items e inserta preguntas
de ejemplo si está vacía. Idempotente.

Uso:
    python migrate_faq.py
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))

from app.database import engine, SessionLocal, Base
import app.infrastructure.database.models  # noqa: F401
from app.infrastructure.database.models.faq_model import FaqItemModel

Base.metadata.create_all(bind=engine)
print("  [OK] tabla faq_items creada o verificada")

db = SessionLocal()
existing = db.query(FaqItemModel).count()
if existing == 0:
    SAMPLES = [
        {
            "category": "Pedidos",
            "position": 1,
            "question": "Como hago un pedido?",
            "answer": "Selecciona los productos que necesitas y agregalos al carrito. Cuando termines, ve al carrito y haz clic en 'Continuar al pago'. Llena tus datos y se abrira WhatsApp con el detalle del pedido para que un asesor coordine el pago y la entrega contigo.",
        },
        {
            "category": "Pedidos",
            "position": 2,
            "question": "Necesito crear una cuenta para comprar?",
            "answer": "No es obligatorio. Puedes hacer pedidos como invitado. Solo te pediremos tu nombre, correo y telefono para coordinar la entrega.",
        },
        {
            "category": "Pagos",
            "position": 3,
            "question": "Que metodos de pago aceptan?",
            "answer": "Por ahora coordinamos los pagos directamente por WhatsApp. Aceptamos transferencia bancaria, Bancolombia a la mano, Daviplata y consignacion. Pronto integraremos pagos en linea con Wompi y PSE.",
        },
        {
            "category": "Envios",
            "position": 4,
            "question": "Hacen envios a toda Colombia?",
            "answer": "Si, enviamos a todas las ciudades del pais a traves de Servientrega, Envia y Coordinadora. El tiempo de entrega estandar es de 1 a 3 dias habiles dependiendo del destino.",
        },
        {
            "category": "Envios",
            "position": 5,
            "question": "Cual es el costo del envio?",
            "answer": "El costo del envio depende del peso del producto y la ciudad de destino. Te lo informamos al confirmar el pedido por WhatsApp antes de procesarlo.",
        },
        {
            "category": "Productos",
            "position": 6,
            "question": "Tienen garantia los repuestos?",
            "answer": "Si, todos nuestros repuestos cuentan con garantia de fabrica. La duracion depende del tipo de producto y va desde 30 dias hasta 1 ano. Consulta los detalles en cada producto o pregunta a nuestro asesor.",
        },
        {
            "category": "Productos",
            "position": 7,
            "question": "Si no encuentro el repuesto que necesito, que hago?",
            "answer": "Contactanos por WhatsApp o llamanos al telefono de contacto. Tenemos acceso a un catalogo mucho mas amplio del que vemos en linea y podemos cotizarte cualquier repuesto que necesites.",
        },
    ]
    for s in SAMPLES:
        db.add(FaqItemModel(**s, active=True))
    db.commit()
    print(f"  [OK] {len(SAMPLES)} preguntas frecuentes de ejemplo insertadas")
else:
    print(f"  [-]  tabla ya tenia {existing} preguntas, no se insertan defaults")

db.close()
print("\nMigracion completada.")
