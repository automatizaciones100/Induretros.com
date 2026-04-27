from app.domain.entities.order import Order
from app.domain.repositories.order_repository import IOrderRepository
from app.domain.exceptions import EntityNotFoundError


class GetOrderUseCase:
    def __init__(self, order_repo: IOrderRepository):
        self._repo = order_repo

    def execute(self, order_id: int) -> Order:
        order = self._repo.get_by_id(order_id)
        if not order:
            raise EntityNotFoundError("Pedido", str(order_id))
        return order
