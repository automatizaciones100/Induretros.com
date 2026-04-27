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

    @abstractmethod
    def list_paginated(
        self,
        page: int,
        per_page: int,
        status: Optional[str] = None,
        search: Optional[str] = None,
    ) -> tuple[list[Order], int]:
        ...

    @abstractmethod
    def update_status(self, order_id: int, new_status: str) -> Optional[Order]:
        ...
