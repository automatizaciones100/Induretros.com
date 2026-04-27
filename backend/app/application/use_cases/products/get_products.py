from app.domain.entities.product import Product
from app.domain.repositories.product_repository import IProductRepository
from app.application.dtos.product_dto import GetProductsQuery


class GetProductsUseCase:
    def __init__(self, product_repo: IProductRepository):
        self._repo = product_repo

    def execute(self, query: GetProductsQuery) -> tuple[list[Product], int]:
        return self._repo.get_all(
            page=query.page,
            per_page=query.per_page,
            category_slug=query.category,
            search=query.search,
            featured=query.featured,
        )
