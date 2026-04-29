from app.domain.repositories.product_repository import IProductRepository
from app.domain.exceptions import EntityNotFoundError


class DeleteProductUseCase:
    def __init__(self, repo: IProductRepository):
        self._repo = repo

    def execute(self, product_id: int) -> None:
        existed = self._repo.delete(product_id)
        if not existed:
            raise EntityNotFoundError("Producto", str(product_id))
