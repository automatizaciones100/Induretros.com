from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from app.database import get_db
from app.models.order import Order, OrderItem
from app.models.product import Product
from app.schemas.order import OrderCreate, OrderOut

router = APIRouter(prefix="/api/orders", tags=["orders"])


@router.post("", response_model=OrderOut, status_code=201)
def create_order(order: OrderCreate, db: Session = Depends(get_db)):
    total = 0.0
    items_data = []

    for item in order.items:
        product = db.query(Product).filter(Product.id == item.product_id).first()
        if not product:
            raise HTTPException(status_code=404, detail=f"Producto {item.product_id} no encontrado")
        if not product.in_stock:
            raise HTTPException(status_code=400, detail=f"Producto '{product.name}' sin stock")

        price = product.sale_price or product.price or 0
        subtotal = price * item.quantity
        total += subtotal
        items_data.append({"product": product, "quantity": item.quantity, "price": price, "subtotal": subtotal})

    db_order = Order(
        customer_name=order.customer_name,
        customer_email=order.customer_email,
        customer_phone=order.customer_phone,
        shipping_address=order.shipping_address,
        notes=order.notes,
        total=total,
    )
    db.add(db_order)
    db.flush()

    for item_data in items_data:
        db_item = OrderItem(
            order_id=db_order.id,
            product_id=item_data["product"].id,
            quantity=item_data["quantity"],
            unit_price=item_data["price"],
            subtotal=item_data["subtotal"],
        )
        db.add(db_item)

    db.commit()
    db.refresh(db_order)
    return db_order


@router.get("/{order_id}", response_model=OrderOut)
def get_order(order_id: int, db: Session = Depends(get_db)):
    order = db.query(Order).filter(Order.id == order_id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Pedido no encontrado")
    return order
