"""
Endpoints internos para el panel de administración.
Requieren JWT con is_admin=True.
"""
from fastapi import APIRouter, Depends, Request
from sqlalchemy.orm import Session
from sqlalchemy import func

from app.database import get_db
from app.infrastructure.database.models.product_model import ProductModel, CategoryModel
from app.infrastructure.database.models.order_model import OrderModel
from app.infrastructure.database.models.user_model import UserModel
from app.domain.entities.order import OrderStatus
from app.presentation.dependencies import get_current_admin
from app.presentation.rate_limiter import limiter

router = APIRouter(prefix="/api/admin", tags=["admin"])


@router.get("/stats")
@limiter.limit("60/minute")
def get_stats(
    request: Request,
    db: Session = Depends(get_db),
    _admin: dict = Depends(get_current_admin),
):
    """KPIs agregados para el dashboard admin."""
    # Productos
    total_products = db.query(func.count(ProductModel.id)).scalar() or 0
    in_stock = db.query(func.count(ProductModel.id)).filter(ProductModel.in_stock == True).scalar() or 0  # noqa: E712
    out_of_stock = total_products - in_stock
    featured = db.query(func.count(ProductModel.id)).filter(ProductModel.featured == True).scalar() or 0  # noqa: E712

    # Categorías
    total_categories = db.query(func.count(CategoryModel.id)).scalar() or 0

    # Pedidos por estado
    orders_by_status = dict(
        db.query(OrderModel.status, func.count(OrderModel.id))
        .group_by(OrderModel.status)
        .all()
    )
    pending_orders = orders_by_status.get(OrderStatus.pending, 0)
    processing_orders = orders_by_status.get(OrderStatus.processing, 0)
    completed_orders = orders_by_status.get(OrderStatus.completed, 0)
    cancelled_orders = orders_by_status.get(OrderStatus.cancelled, 0)
    total_orders = pending_orders + processing_orders + completed_orders + cancelled_orders

    # Ingresos (suma de total de órdenes no canceladas)
    revenue = (
        db.query(func.coalesce(func.sum(OrderModel.total), 0))
        .filter(OrderModel.status != OrderStatus.cancelled)
        .scalar()
        or 0
    )

    # Usuarios
    total_users = db.query(func.count(UserModel.id)).scalar() or 0

    return {
        "products": {
            "total": total_products,
            "in_stock": in_stock,
            "out_of_stock": out_of_stock,
            "featured": featured,
        },
        "categories": {
            "total": total_categories,
        },
        "orders": {
            "total": total_orders,
            "pending": pending_orders,
            "processing": processing_orders,
            "completed": completed_orders,
            "cancelled": cancelled_orders,
            "revenue": float(revenue),
        },
        "users": {
            "total": total_users,
        },
    }
