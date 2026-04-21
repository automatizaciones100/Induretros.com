from typing import Optional
from sqlalchemy.orm import Session
from app.domain.entities.order import Order, OrderItem
from app.domain.repositories.order_repository import IOrderRepository
from app.infrastructure.database.models.order_model import OrderModel, OrderItemModel


def _order_model_to_entity(model: OrderModel) -> Order:
    return Order(
        id=model.id,
        user_id=model.user_id,
        status=model.status,
        total=model.total,
        customer_name=model.customer_name,
        customer_email=model.customer_email,
        customer_phone=model.customer_phone,
        shipping_address=model.shipping_address,
        notes=model.notes,
        created_at=model.created_at,
        items=[
            OrderItem(
                id=item.id,
                order_id=item.order_id,
                product_id=item.product_id,
                quantity=item.quantity,
                unit_price=item.unit_price,
                subtotal=item.subtotal,
            )
            for item in model.items
        ],
    )


class SQLAlchemyOrderRepository(IOrderRepository):
    def __init__(self, db: Session):
        self._db = db

    def create(self, order: Order) -> Order:
        db_order = OrderModel(
            user_id=order.user_id,
            customer_name=order.customer_name,
            customer_email=order.customer_email,
            customer_phone=order.customer_phone,
            shipping_address=order.shipping_address,
            notes=order.notes,
            total=order.total,
        )
        self._db.add(db_order)
        self._db.flush()

        for item in order.items:
            db_item = OrderItemModel(
                order_id=db_order.id,
                product_id=item.product_id,
                quantity=item.quantity,
                unit_price=item.unit_price,
                subtotal=item.subtotal,
            )
            self._db.add(db_item)

        self._db.commit()
        self._db.refresh(db_order)
        return _order_model_to_entity(db_order)

    def get_by_id(self, order_id: int) -> Optional[Order]:
        model = self._db.query(OrderModel).filter(OrderModel.id == order_id).first()
        return _order_model_to_entity(model) if model else None
