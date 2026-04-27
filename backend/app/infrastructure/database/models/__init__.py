# Importar todos los modelos para que SQLAlchemy los registre en Base.metadata
from app.infrastructure.database.models.product_model import ProductModel, CategoryModel
from app.infrastructure.database.models.user_model import UserModel
from app.infrastructure.database.models.order_model import OrderModel, OrderItemModel
from app.infrastructure.database.models.analytics_model import AnalyticsEventModel

__all__ = [
    "ProductModel",
    "CategoryModel",
    "UserModel",
    "OrderModel",
    "OrderItemModel",
    "AnalyticsEventModel",
]
