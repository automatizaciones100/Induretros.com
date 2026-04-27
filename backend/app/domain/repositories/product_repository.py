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

    @abstractmethod
    def get_by_sku(self, sku: str) -> Optional[Product]:
        ...

    @abstractmethod
    def update_image_url(self, product_id: int, image_url: str) -> None:
        """Actualiza la URL de imagen de un producto."""
        ...

    @abstractmethod
    def update(self, product_id: int, fields: dict) -> Optional[Product]:
        """Actualiza los campos indicados y retorna el producto actualizado."""
        ...

    @abstractmethod
    def delete(self, product_id: int) -> bool:
        """Elimina un producto. Retorna True si existía."""
        ...


class ICategoryRepository(ABC):
    @abstractmethod
    def get_all_root(self) -> list[Category]:
        ...

    @abstractmethod
    def get_all(self) -> list[Category]:
        """Lista todas las categorías (raíz + hijas) en plano para administración."""
        ...

    @abstractmethod
    def get_by_id(self, category_id: int) -> Optional[Category]:
        ...

    @abstractmethod
    def get_by_slug(self, slug: str) -> Optional[Category]:
        ...

    @abstractmethod
    def create(self, category: Category) -> Category:
        ...

    @abstractmethod
    def update(self, category_id: int, fields: dict) -> Optional[Category]:
        ...

    @abstractmethod
    def delete(self, category_id: int) -> bool:
        """Retorna False si tiene productos asociados o subcategorías."""
        ...
