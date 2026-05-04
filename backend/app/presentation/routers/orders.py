from typing import Optional
from fastapi import APIRouter, Depends, HTTPException, Query, Request, status
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from jose import jwt, JWTError
from sqlalchemy.orm import Session
from app.config import settings
from app.database import get_db
from app.application.dtos.order_dto import CreateOrderCommand, OrderDTO, OrderItemDTO
from app.application.use_cases.orders.create_order import CreateOrderUseCase
from app.application.use_cases.orders.get_order import GetOrderUseCase
from app.domain.exceptions import EntityNotFoundError, OutOfStockError
from app.infrastructure.database.models.order_model import OrderModel
from app.presentation.dependencies import create_order_use_case, get_order_use_case, get_current_user
from app.presentation.rate_limiter import limiter
from app.infrastructure.logging.security_logger import log_access_denied, log_data_accessed

router = APIRouter(prefix="/api/orders", tags=["orders"])

# Auth opcional: si viene Bearer token, lo decodifica; si no, retorna None.
_optional_bearer = HTTPBearer(auto_error=False)


def get_optional_user(
    credentials: Optional[HTTPAuthorizationCredentials] = Depends(_optional_bearer),
) -> Optional[dict]:
    """Retorna el payload del JWT si viene en el header, o None si es anónimo."""
    if not credentials:
        return None
    try:
        return jwt.decode(credentials.credentials, settings.secret_key, algorithms=[settings.algorithm])
    except (JWTError, Exception):
        return None


@router.post("", response_model=OrderDTO, status_code=201)
@limiter.limit("10/minute")
def create_order(
    request: Request,
    command: CreateOrderCommand,
    use_case: CreateOrderUseCase = Depends(create_order_use_case),
    current_user: Optional[dict] = Depends(get_optional_user),
):
    """
    Crea una orden. Auth es opcional:
      - Con token: la orden queda vinculada al user_id (aparece en su historial)
      - Sin token: orden anónima — el cliente accede al detalle solo en la
        respuesta inmediata o desde la página de confirmación inmediata
    """
    user_id = int(current_user.get("sub", 0)) if current_user else None
    user_id = user_id or None  # 0 → None
    try:
        order = use_case.execute(command, user_id=user_id)
        return _order_to_dto(order)
    except EntityNotFoundError as e:
        raise HTTPException(status_code=404, detail=str(e))
    except OutOfStockError as e:
        raise HTTPException(status_code=400, detail=str(e))


@router.get("/me", response_model=dict)
@limiter.limit("60/minute")
def list_my_orders(
    request: Request,
    page: int = Query(1, ge=1),
    per_page: int = Query(20, ge=1, le=100),
    db: Session = Depends(get_db),
    current_user: dict = Depends(get_current_user),
):
    """
    Lista los pedidos del usuario autenticado, paginados, ordenados de más
    reciente a más antiguo. Solo el dueño accede a sus propios pedidos.
    """
    user_id = int(current_user.get("sub", 0))
    if user_id <= 0:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="No autenticado")

    query = (
        db.query(OrderModel)
        .filter(OrderModel.user_id == user_id)
        .order_by(OrderModel.created_at.desc())
    )
    total = query.count()
    orders = query.offset((page - 1) * per_page).limit(per_page).all()

    ip = request.client.host if request.client else "unknown"
    log_data_accessed(user_id=user_id, resource="orders:me", ip=ip)

    return {
        "items": [_order_to_dto(o).model_dump(mode="json") for o in orders],
        "total": total,
        "page": page,
        "per_page": per_page,
        "pages": (total + per_page - 1) // per_page if total else 0,
    }


@router.get("/{order_id}", response_model=OrderDTO)
@limiter.limit("10/minute")
def retrieve_order(
    order_id: int,
    request: Request,
    use_case: GetOrderUseCase = Depends(get_order_use_case),
    current_user: dict = Depends(get_current_user),
):
    try:
        order = use_case.execute(order_id)
    except EntityNotFoundError as e:
        raise HTTPException(status_code=404, detail=str(e))

    # Solo el dueño de la orden o un admin puede verla
    user_id = int(current_user.get("sub", -1))
    is_admin = current_user.get("is_admin", False)
    if not is_admin and order.user_id != user_id:
        ip = request.client.host if request.client else "unknown"
        log_access_denied(user_id=user_id, resource=f"order:{order_id}", ip=ip)
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Acceso denegado")

    ip = request.client.host if request.client else "unknown"
    log_data_accessed(user_id=user_id, resource=f"order:{order_id}", ip=ip)
    return _order_to_dto(order)


def _order_to_dto(order) -> OrderDTO:
    return OrderDTO(
        id=order.id,
        status=order.status,
        total=order.total,
        customer_name=order.customer_name,
        customer_email=order.customer_email,
        customer_phone=order.customer_phone,
        shipping_address=order.shipping_address,
        notes=order.notes,
        created_at=order.created_at,
        items=[
            OrderItemDTO(
                id=item.id,
                product_id=item.product_id,
                quantity=item.quantity,
                unit_price=item.unit_price,
                subtotal=item.subtotal,
            )
            for item in order.items
        ],
    )
