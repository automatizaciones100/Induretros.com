from app.domain.entities.product import Category
from app.domain.repositories.product_repository import ICategoryRepository
from app.domain.exceptions import EntityNotFoundError


class GetCategoryUseCase:
    def __init__(self, category_repo: ICategoryRepository):
        self._repo = category_repo

    def execute(self, slug: str) -> Category:
        category = self._repo.get_by_slug(slug)
        if not category:
            raise EntityNotFoundError("Categoría", slug)
        return category
