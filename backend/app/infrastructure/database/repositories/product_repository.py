from typing import Optional
from sqlalchemy import or_
from sqlalchemy.orm import Session, selectinload
from app.domain.entities.product import Product, Category
from app.domain.repositories.product_repository import IProductRepository, ICategoryRepository
from app.infrastructure.database.models.product_model import ProductModel, CategoryModel


def _category_model_to_entity(model: CategoryModel) -> Category:
    return Category(
        id=model.id,
        name=model.name,
        slug=model.slug,
        description=model.description,
        image_url=model.image_url,
        parent_id=model.parent_id,
        display_order=model.display_order or 0,
        created_at=model.created_at,
        children=[
            _category_model_to_entity(c)
            for c in sorted(model.children, key=lambda c: ((c.display_order or 0), c.name))
        ],
    )


def _product_model_to_entity(model: ProductModel) -> Product:
    return Product(
        id=model.id,
        name=model.name,
        slug=model.slug,
        description=model.description,
        short_description=model.short_description,
        price=model.price,
        regular_price=model.regular_price,
        sale_price=model.sale_price,
        sku=model.sku,
        stock=model.stock,
        in_stock=model.in_stock,
        image_url=model.image_url,
        category_id=model.category_id,
        featured=model.featured,
        meta_title=model.meta_title,
        meta_description=model.meta_description,
        created_at=model.created_at,
        updated_at=model.updated_at,
        category=_category_model_to_entity(model.category) if model.category else None,
    )


class SQLAlchemyProductRepository(IProductRepository):
    def __init__(self, db: Session):
        self._db = db

    def get_all(
        self,
        page: int,
        per_page: int,
        category_slug: Optional[str] = None,
        search: Optional[str] = None,
        featured: Optional[bool] = None,
    ) -> tuple[list[Product], int]:
        query = self._db.query(ProductModel)

        if category_slug:
            cat = self._db.query(CategoryModel).filter(CategoryModel.slug == category_slug).first()
            if cat:
                query = query.filter(ProductModel.category_id == cat.id)

        if search:
            # Solo name y sku tienen índice B-tree — description (hasta 50k chars) sin índice
            # haría full table scan. En producción con pg_trgm se puede reincorporar.
            query = query.filter(
                or_(
                    ProductModel.name.ilike(f"%{search}%"),
                    ProductModel.sku.ilike(f"%{search}%"),
                )
            )

        if featured is not None:
            query = query.filter(ProductModel.featured == featured)

        total = query.count()
        models = query.offset((page - 1) * per_page).limit(per_page).all()
        return [_product_model_to_entity(m) for m in models], total

    def get_by_slug(self, slug: str) -> Optional[Product]:
        model = self._db.query(ProductModel).filter(ProductModel.slug == slug).first()
        return _product_model_to_entity(model) if model else None

    def get_by_id(self, product_id: int) -> Optional[Product]:
        model = self._db.query(ProductModel).filter(ProductModel.id == product_id).first()
        return _product_model_to_entity(model) if model else None

    def get_by_sku(self, sku: str) -> Optional[Product]:
        model = self._db.query(ProductModel).filter(ProductModel.sku == sku).first()
        return _product_model_to_entity(model) if model else None

    def update_image_url(self, product_id: int, image_url: str) -> None:
        self._db.query(ProductModel).filter(ProductModel.id == product_id).update(
            {"image_url": image_url}
        )
        self._db.commit()

    def decrement_stock(self, product_id: int, quantity: int) -> None:
        model = self._db.query(ProductModel).filter(ProductModel.id == product_id).first()
        if model:
            model.stock = max(0, model.stock - quantity)
            if model.stock == 0:
                model.in_stock = False
            self._db.commit()

    def create(self, product: Product) -> Product:
        model = ProductModel(
            name=product.name,
            slug=product.slug,
            description=product.description,
            short_description=product.short_description,
            price=product.price,
            regular_price=product.regular_price,
            sale_price=product.sale_price,
            sku=product.sku,
            stock=product.stock,
            in_stock=product.in_stock,
            image_url=product.image_url,
            category_id=product.category_id,
            featured=product.featured,
            meta_title=product.meta_title,
            meta_description=product.meta_description,
        )
        self._db.add(model)
        self._db.commit()
        self._db.refresh(model)
        return _product_model_to_entity(model)

    def update(self, product_id: int, fields: dict) -> Optional[Product]:
        model = self._db.query(ProductModel).filter(ProductModel.id == product_id).first()
        if not model:
            return None
        for key, value in fields.items():
            if hasattr(model, key):
                setattr(model, key, value)
        self._db.commit()
        self._db.refresh(model)
        return _product_model_to_entity(model)

    def delete(self, product_id: int) -> bool:
        model = self._db.query(ProductModel).filter(ProductModel.id == product_id).first()
        if not model:
            return False
        self._db.delete(model)
        self._db.commit()
        return True


class SQLAlchemyCategoryRepository(ICategoryRepository):
    def __init__(self, db: Session):
        self._db = db

    def get_all_root(self) -> list[Category]:
        # selectinload carga todos los hijos en 1 query adicional (evita N+1)
        models = (
            self._db.query(CategoryModel)
            .filter(CategoryModel.parent_id == None)
            .options(selectinload(CategoryModel.children))
            .order_by(CategoryModel.display_order, CategoryModel.name)
            .all()
        )
        return [_category_model_to_entity(m) for m in models]

    def get_all(self) -> list[Category]:
        models = (
            self._db.query(CategoryModel)
            .order_by(CategoryModel.display_order, CategoryModel.name)
            .all()
        )
        return [_category_model_to_entity(m) for m in models]

    def get_by_id(self, category_id: int) -> Optional[Category]:
        model = self._db.query(CategoryModel).filter(CategoryModel.id == category_id).first()
        return _category_model_to_entity(model) if model else None

    def get_by_slug(self, slug: str) -> Optional[Category]:
        model = (
            self._db.query(CategoryModel)
            .filter(CategoryModel.slug == slug)
            .options(selectinload(CategoryModel.children))
            .first()
        )
        return _category_model_to_entity(model) if model else None

    def create(self, category: Category) -> Category:
        model = CategoryModel(
            name=category.name,
            slug=category.slug,
            description=category.description,
            image_url=category.image_url,
            parent_id=category.parent_id,
            display_order=category.display_order or 0,
        )
        self._db.add(model)
        self._db.commit()
        self._db.refresh(model)
        return _category_model_to_entity(model)

    def update(self, category_id: int, fields: dict) -> Optional[Category]:
        model = self._db.query(CategoryModel).filter(CategoryModel.id == category_id).first()
        if not model:
            return None
        for key, value in fields.items():
            if hasattr(model, key):
                setattr(model, key, value)
        self._db.commit()
        self._db.refresh(model)
        return _category_model_to_entity(model)

    def delete(self, category_id: int) -> bool:
        model = self._db.query(CategoryModel).filter(CategoryModel.id == category_id).first()
        if not model:
            return False
        # Verificar que no tenga productos asociados ni subcategorías
        if model.products or model.children:
            return False
        self._db.delete(model)
        self._db.commit()
        return True
