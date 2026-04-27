from app.domain.entities.product import Product
from app.domain.repositories.product_repository import IProductRepository
from app.domain.exceptions import EntityNotFoundError
from app.application.dtos.product_dto import UpdateProductCommand


class UpdateProductUseCase:
    def __init__(self, repo: IProductRepository):
        self._repo = repo

    def execute(self, product_id: int, command: UpdateProductCommand) -> Product:
        # Solo enviamos los campos explícitamente provistos (no los que quedaron en None por default)
        fields = command.model_dump(exclude_unset=True)
        updated = self._repo.update(product_id, fields)
        if not updated:
            raise EntityNotFoundError("Producto", str(product_id))
        return updated
