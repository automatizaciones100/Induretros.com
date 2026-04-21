from app.domain.entities.product import Category
from app.domain.repositories.product_repository import ICategoryRepository
from app.application.dtos.product_dto import CreateCategoryCommand


class CreateCategoryUseCase:
    def __init__(self, category_repo: ICategoryRepository):
        self._repo = category_repo

    def execute(self, command: CreateCategoryCommand) -> Category:
        category = Category(
            id=None,
            name=command.name,
            slug=command.slug,
            description=command.description,
            image_url=command.image_url,
            parent_id=command.parent_id,
        )
        return self._repo.create(category)
