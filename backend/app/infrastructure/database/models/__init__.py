# Importar todos los modelos para que SQLAlchemy los registre en Base.metadata
from app.infrastructure.database.models.product_model import ProductModel, CategoryModel
from app.infrastructure.database.models.user_model import UserModel
from app.infrastructure.database.models.order_model import OrderModel, OrderItemModel
from app.infrastructure.database.models.analytics_model import AnalyticsEventModel
from app.infrastructure.database.models.site_settings_model import SiteSettingsModel
from app.infrastructure.database.models.home_stat_model import HomeStatModel
from app.infrastructure.database.models.announcement_model import AnnouncementModel
from app.infrastructure.database.models.testimonial_model import TestimonialModel
from app.infrastructure.database.models.faq_model import FaqItemModel
from app.infrastructure.database.models.why_us_model import WhyUsItemModel

__all__ = [
    "ProductModel",
    "CategoryModel",
    "UserModel",
    "OrderModel",
    "OrderItemModel",
    "AnalyticsEventModel",
    "SiteSettingsModel",
    "HomeStatModel",
    "AnnouncementModel",
    "TestimonialModel",
    "FaqItemModel",
    "WhyUsItemModel",
]
