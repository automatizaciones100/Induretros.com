from app.domain.repositories.product_repository import ICategoryRepository
from app.domain.exceptions import DomainException, EntityNotFoundError


class CategoryHasDependentsError(DomainException):
    def __init__(self):
        super().__init__("La categoría tiene productos o subcategorías. Reasígnalos antes de eliminar.")


class DeleteCategoryUseCase:
    def __init__(self, repo: ICategoryRepository):
        self._repo = repo

    def execute(self, category_id: int) -> None:
        existing = self._repo.get_by_id(category_id)
        if not existing:
            raise EntityNotFoundError("Categoría", str(category_id))

        deleted = self._repo.delete(category_id)
        if not deleted:
            # delete() retorna False si tiene productos o hijos
            raise CategoryHasDependentsError()
