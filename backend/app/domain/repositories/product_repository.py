from abc import ABC, abstractmethod
from typing import Optional
from app.domain.entities.product import Product, Category


class IProductRepository(ABC):
    @abstractmethod
    def get_all(
        self,
        page: int,
        per_page: int,
        category_slug: Optional[str] = None,
        search: Optional[str] = None,
        featured: Optional[bool] = None,
    ) -> tuple[list[Product], int]:
        ...

    @abstractmethod
    def get_by_slug(self, slug: str) -> Optional[Product]:
        ...

    @abstractmethod
    def get_by_id(self, product_id: int) -> Optional[Product]:
        ...

    @abstractmethod
    def create(self, product: Product) -> Product:
        ...

    @abstractmethod
    def decrement_stock(self, product_id: int, quantity: int) -> None:
        """Descuenta `quantity` unidades del stock. Marca in_stock=False si llega a 0."""
        ...


class ICategoryRepository(ABC):
    @abstractmethod
    def get_all_root(self) -> list[Category]:
        ...

    @abstractmethod
    def get_by_slug(self, slug: str) -> Optional[Category]:
        ...

    @abstractmethod
    def create(self, category: Category) -> Category:
        ...
