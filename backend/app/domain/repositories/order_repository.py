from abc import ABC, abstractmethod
from typing import Optional
from app.domain.entities.order import Order


class IOrderRepository(ABC):
    @abstractmethod
    def create(self, order: Order) -> Order:
        ...

    @abstractmethod
    def get_by_id(self, order_id: int) -> Optional[Order]:
        ...
