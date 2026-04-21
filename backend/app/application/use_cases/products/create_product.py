from app.domain.entities.product import Product
from app.domain.repositories.product_repository import IProductRepository
from app.application.dtos.product_dto import CreateProductCommand


class CreateProductUseCase:
    def __init__(self, product_repo: IProductRepository):
        self._repo = product_repo

    def execute(self, command: CreateProductCommand) -> Product:
        product = Product(
            id=None,
            name=command.name,
            slug=command.slug,
            description=command.description,
            short_description=command.short_description,
            price=command.price,
            regular_price=command.regular_price,
            sale_price=command.sale_price,
            sku=command.sku,
            stock=command.stock,
            in_stock=command.in_stock,
            image_url=command.image_url,
            category_id=command.category_id,
            featured=command.featured,
        )
        return self._repo.create(product)
