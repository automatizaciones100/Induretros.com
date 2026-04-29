from typing import Optional
from app.domain.entities.product import Product
from app.domain.repositories.product_repository import IProductRepository
from app.domain.exceptions import EntityNotFoundError


class GetProductUseCase:
    def __init__(self, product_repo: IProductRepository):
        self._repo = product_repo

    def execute(self, slug: str) -> Product:
        product = self._repo.get_by_slug(slug)
        if not product:
            raise EntityNotFoundError("Producto", slug)
        return product
