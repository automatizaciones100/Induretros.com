"""
Endpoints internos para el panel de administración.
Requieren JWT con is_admin=True.
"""
from fastapi import APIRouter, Depends, Request
from typing import Optional, Literal
from datetime import datetime, timedelta, timezone
from fastapi import HTTPException, status as http_status
from pydantic import BaseModel
from sqlalchemy.orm import Session
from sqlalchemy import func, and_

from app.database import get_db
from app.infrastructure.database.models.product_model import ProductModel, CategoryModel
from app.infrastructure.database.models.order_model import OrderModel, OrderItemModel
from app.infrastructure.database.models.user_model import UserModel
from app.infrastructure.database.models.analytics_model import AnalyticsEventModel
from app.domain.entities.order import OrderStatus
from app.infrastructure.database.repositories.order_repository import SQLAlchemyOrderRepository
from app.infrastructure.logging.security_logger import log_admin_action
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


@router.get("/marketing")
@limiter.limit("60/minute")
def get_marketing_stats(
    request: Request,
    db: Session = Depends(get_db),
    _admin: dict = Depends(get_current_admin),
):
    """
    KPIs de marketing y salud SEO del catálogo.
    """
    total_products = db.query(func.count(ProductModel.id)).scalar() or 0

    # ─── Salud SEO del catálogo ───
    no_image = db.query(func.count(ProductModel.id)).filter(
        (ProductModel.image_url.is_(None)) | (ProductModel.image_url == "")
    ).scalar() or 0

    no_description = db.query(func.count(ProductModel.id)).filter(
        (ProductModel.description.is_(None)) | (ProductModel.description == "")
    ).scalar() or 0

    no_short_description = db.query(func.count(ProductModel.id)).filter(
        (ProductModel.short_description.is_(None)) | (ProductModel.short_description == "")
    ).scalar() or 0

    no_sku = db.query(func.count(ProductModel.id)).filter(
        (ProductModel.sku.is_(None)) | (ProductModel.sku == "")
    ).scalar() or 0

    no_price = db.query(func.count(ProductModel.id)).filter(
        (ProductModel.price.is_(None)) | (ProductModel.price <= 0)
    ).scalar() or 0

    # Producto "completo" = tiene imagen, descripción larga, SKU y precio > 0
    complete = db.query(func.count(ProductModel.id)).filter(
        and_(
            ProductModel.image_url.isnot(None),
            ProductModel.image_url != "",
            ProductModel.description.isnot(None),
            ProductModel.description != "",
            ProductModel.sku.isnot(None),
            ProductModel.sku != "",
            ProductModel.price.isnot(None),
            ProductModel.price > 0,
        )
    ).scalar() or 0

    seo_score = round((complete / total_products) * 100) if total_products else 0

    # ─── Marketing: productos en oferta y destacados ───
    on_sale = db.query(func.count(ProductModel.id)).filter(
        and_(
            ProductModel.sale_price.isnot(None),
            ProductModel.regular_price.isnot(None),
            ProductModel.sale_price < ProductModel.regular_price,
        )
    ).scalar() or 0

    featured_no_stock = db.query(func.count(ProductModel.id)).filter(
        and_(ProductModel.featured == True, ProductModel.in_stock == False)  # noqa: E712
    ).scalar() or 0

    # ─── Pedidos por ventana temporal (últimos 7 / 30 días) ───
    now = datetime.now(timezone.utc)
    last_7 = now - timedelta(days=7)
    last_30 = now - timedelta(days=30)

    orders_7d = db.query(func.count(OrderModel.id)).filter(
        and_(OrderModel.created_at >= last_7, OrderModel.status != OrderStatus.cancelled)
    ).scalar() or 0

    orders_30d = db.query(func.count(OrderModel.id)).filter(
        and_(OrderModel.created_at >= last_30, OrderModel.status != OrderStatus.cancelled)
    ).scalar() or 0

    revenue_30d = db.query(func.coalesce(func.sum(OrderModel.total), 0)).filter(
        and_(OrderModel.created_at >= last_30, OrderModel.status != OrderStatus.cancelled)
    ).scalar() or 0

    # ─── Ticket promedio ───
    avg_order = db.query(func.coalesce(func.avg(OrderModel.total), 0)).filter(
        OrderModel.status != OrderStatus.cancelled
    ).scalar() or 0

    # ─── Top 5 productos más vendidos ───
    top_products_query = (
        db.query(
            ProductModel.id,
            ProductModel.name,
            ProductModel.slug,
            func.sum(OrderItemModel.quantity).label("units"),
            func.sum(OrderItemModel.subtotal).label("revenue"),
        )
        .join(OrderItemModel, OrderItemModel.product_id == ProductModel.id)
        .join(OrderModel, OrderModel.id == OrderItemModel.order_id)
        .filter(OrderModel.status != OrderStatus.cancelled)
        .group_by(ProductModel.id, ProductModel.name, ProductModel.slug)
        .order_by(func.sum(OrderItemModel.quantity).desc())
        .limit(5)
        .all()
    )

    top_products = [
        {"id": p.id, "name": p.name, "slug": p.slug, "units": int(p.units), "revenue": float(p.revenue)}
        for p in top_products_query
    ]

    # ─── Analítica de tráfico (in-house) ───
    now = datetime.now(timezone.utc)
    last_30 = now - timedelta(days=30)
    last_7 = now - timedelta(days=7)

    # Visitantes únicos (session_id distintos)
    unique_visitors_30d = db.query(func.count(func.distinct(AnalyticsEventModel.session_id))).filter(
        AnalyticsEventModel.created_at >= last_30
    ).scalar() or 0

    unique_visitors_7d = db.query(func.count(func.distinct(AnalyticsEventModel.session_id))).filter(
        AnalyticsEventModel.created_at >= last_7
    ).scalar() or 0

    # Pageviews y clicks
    pageviews_30d = db.query(func.count(AnalyticsEventModel.id)).filter(
        and_(AnalyticsEventModel.event_type == "pageview", AnalyticsEventModel.created_at >= last_30)
    ).scalar() or 0

    clicks_30d = db.query(func.count(AnalyticsEventModel.id)).filter(
        and_(AnalyticsEventModel.event_type == "click", AnalyticsEventModel.created_at >= last_30)
    ).scalar() or 0

    add_to_cart_30d = db.query(func.count(AnalyticsEventModel.id)).filter(
        and_(AnalyticsEventModel.event_type == "add_to_cart", AnalyticsEventModel.created_at >= last_30)
    ).scalar() or 0

    # Top 5 productos más vistos (últimos 30 días)
    top_viewed_query = (
        db.query(
            ProductModel.id,
            ProductModel.name,
            ProductModel.slug,
            func.count(AnalyticsEventModel.id).label("views"),
        )
        .join(AnalyticsEventModel, AnalyticsEventModel.product_id == ProductModel.id)
        .filter(
            and_(
                AnalyticsEventModel.event_type == "product_view",
                AnalyticsEventModel.created_at >= last_30,
            )
        )
        .group_by(ProductModel.id, ProductModel.name, ProductModel.slug)
        .order_by(func.count(AnalyticsEventModel.id).desc())
        .limit(5)
        .all()
    )
    top_viewed = [
        {"id": p.id, "name": p.name, "slug": p.slug, "views": int(p.views)}
        for p in top_viewed_query
    ]

    # Tasa de conversión: pedidos / visitantes únicos
    conversion_rate = (
        round((orders_30d / unique_visitors_30d) * 100, 2) if unique_visitors_30d else 0.0
    )

    return {
        "seo": {
            "score": seo_score,
            "complete": complete,
            "total": total_products,
            "missing": {
                "image": no_image,
                "description": no_description,
                "short_description": no_short_description,
                "sku": no_sku,
                "price": no_price,
            },
        },
        "marketing": {
            "on_sale": on_sale,
            "featured_no_stock": featured_no_stock,
            "orders_last_7_days": orders_7d,
            "orders_last_30_days": orders_30d,
            "revenue_last_30_days": float(revenue_30d),
            "avg_order_value": float(avg_order),
            "top_products": top_products,
        },
        "traffic": {
            "unique_visitors_7_days": unique_visitors_7d,
            "unique_visitors_30_days": unique_visitors_30d,
            "pageviews_30_days": pageviews_30d,
            "clicks_30_days": clicks_30d,
            "add_to_cart_30_days": add_to_cart_30d,
            "conversion_rate": conversion_rate,
            "top_viewed_products": top_viewed,
        },
    }


# ───────────────────────── ÓRDENES ─────────────────────────

class UpdateStatusBody(BaseModel):
    status: Literal["pending", "processing", "completed", "cancelled"]


def _order_to_dict(order) -> dict:
    return {
        "id": order.id,
        "status": order.status.value if hasattr(order.status, "value") else str(order.status),
        "total": order.total,
        "customer_name": order.customer_name,
        "customer_email": order.customer_email,
        "customer_phone": order.customer_phone,
        "shipping_address": order.shipping_address,
        "notes": order.notes,
        "created_at": order.created_at.isoformat() if order.created_at else None,
        "items_count": len(order.items),
        "items": [
            {
                "id": i.id,
                "product_id": i.product_id,
                "quantity": i.quantity,
                "unit_price": i.unit_price,
                "subtotal": i.subtotal,
            }
            for i in order.items
        ],
    }


@router.get("/orders")
@limiter.limit("60/minute")
def list_orders(
    request: Request,
    page: int = 1,
    per_page: int = 20,
    status: Optional[str] = None,
    search: Optional[str] = None,
    db: Session = Depends(get_db),
    _admin: dict = Depends(get_current_admin),
):
    """Lista todas las órdenes con filtros y paginación."""
    if status not in (None, "pending", "processing", "completed", "cancelled"):
        status = None
    page = max(1, page)
    per_page = min(max(1, per_page), 100)

    repo = SQLAlchemyOrderRepository(db)
    items, total = repo.list_paginated(page=page, per_page=per_page, status=status, search=search)
    pages = (total + per_page - 1) // per_page if total else 1

    return {
        "items": [_order_to_dict(o) for o in items],
        "total": total,
        "page": page,
        "pages": pages,
    }


@router.get("/orders/{order_id}")
@limiter.limit("60/minute")
def get_order(
    request: Request,
    order_id: int,
    db: Session = Depends(get_db),
    _admin: dict = Depends(get_current_admin),
):
    repo = SQLAlchemyOrderRepository(db)
    order = repo.get_by_id(order_id)
    if not order:
        raise HTTPException(status_code=404, detail="Pedido no encontrado")
    return _order_to_dict(order)


@router.patch("/orders/{order_id}/status")
@limiter.limit("30/minute")
def update_order_status(
    request: Request,
    order_id: int,
    body: UpdateStatusBody,
    db: Session = Depends(get_db),
    admin: dict = Depends(get_current_admin),
):
    repo = SQLAlchemyOrderRepository(db)
    updated = repo.update_status(order_id, body.status)
    if not updated:
        raise HTTPException(status_code=404, detail="Pedido no encontrado")

    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action=f"order_status:{body.status}",
        resource=f"order:{order_id}",
        ip=ip,
    )
    return _order_to_dict(updated)
