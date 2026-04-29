from typing import Optional
from app.domain.entities.order import Order, OrderItem
from app.domain.repositories.order_repository import IOrderRepository
from app.domain.repositories.product_repository import IProductRepository
from app.domain.exceptions import EntityNotFoundError, OutOfStockError
from app.application.dtos.order_dto import CreateOrderCommand


class CreateOrderUseCase:
    def __init__(self, order_repo: IOrderRepository, product_repo: IProductRepository):
        self._order_repo = order_repo
        self._product_repo = product_repo

    def execute(self, command: CreateOrderCommand, user_id: Optional[int] = None) -> Order:
        items: list[OrderItem] = []
        total = 0.0

        for item_cmd in command.items:
            product = self._product_repo.get_by_id(item_cmd.product_id)
            if not product:
                raise EntityNotFoundError("Producto", str(item_cmd.product_id))
            if not product.in_stock:
                raise OutOfStockError(product.name)
            if product.stock > 0 and product.stock < item_cmd.quantity:
                raise OutOfStockError(f"{product.name} (stock disponible: {product.stock})")

            unit_price = product.sale_price or product.price or 0.0
            subtotal = unit_price * item_cmd.quantity
            total += subtotal

            items.append(
                OrderItem(
                    id=None,
                    order_id=None,
                    product_id=product.id,
                    quantity=item_cmd.quantity,
                    unit_price=unit_price,
                    subtotal=subtotal,
                )
            )

        order = Order(
            id=None,
            user_id=user_id,
            customer_name=command.customer_name,
            customer_email=command.customer_email,
            customer_phone=command.customer_phone,
            shipping_address=command.shipping_address,
            notes=command.notes,
            total=total,
            items=items,
        )
        saved_order = self._order_repo.create(order)

        # Descontar stock de cada producto después de guardar la orden
        for item in items:
            self._product_repo.decrement_stock(item.product_id, item.quantity)

        return saved_order
