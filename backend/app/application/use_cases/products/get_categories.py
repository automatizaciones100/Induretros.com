from app.domain.entities.product import Category
from app.domain.repositories.product_repository import ICategoryRepository


class GetCategoriesUseCase:
    def __init__(self, category_repo: ICategoryRepository):
        self._repo = category_repo

    def execute(self) -> list[Category]:
        return self._repo.get_all_root()
