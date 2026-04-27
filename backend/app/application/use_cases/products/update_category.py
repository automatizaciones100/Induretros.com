from app.domain.entities.product import Category
from app.domain.repositories.product_repository import ICategoryRepository
from app.domain.exceptions import EntityNotFoundError
from app.application.dtos.product_dto import UpdateCategoryCommand


class UpdateCategoryUseCase:
    def __init__(self, repo: ICategoryRepository):
        self._repo = repo

    def execute(self, category_id: int, command: UpdateCategoryCommand) -> Category:
        fields = command.model_dump(exclude_unset=True)
        updated = self._repo.update(category_id, fields)
        if not updated:
            raise EntityNotFoundError("Categoría", str(category_id))
        return updated
