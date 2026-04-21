from fastapi import APIRouter, Depends, HTTPException, Request, status
from app.application.dtos.order_dto import CreateOrderCommand, OrderDTO, OrderItemDTO
from app.application.use_cases.orders.create_order import CreateOrderUseCase
from app.application.use_cases.orders.get_order import GetOrderUseCase
from app.domain.exceptions import EntityNotFoundError, OutOfStockError
from app.presentation.dependencies import create_order_use_case, get_order_use_case, get_current_user
from app.presentation.rate_limiter import limiter
from app.infrastructure.logging.security_logger import log_access_denied

router = APIRouter(prefix="/api/orders", tags=["orders"])


@router.post("", response_model=OrderDTO, status_code=201)
@limiter.limit("10/minute")
def create_order(
    request: Request,
    command: CreateOrderCommand,
    use_case: CreateOrderUseCase = Depends(create_order_use_case),
    current_user: dict = Depends(get_current_user),
):
    user_id = int(current_user.get("sub", 0)) or None
    try:
        order = use_case.execute(command, user_id=user_id)
        return _order_to_dto(order)
    except EntityNotFoundError as e:
        raise HTTPException(status_code=404, detail=str(e))
    except OutOfStockError as e:
        raise HTTPException(status_code=400, detail=str(e))


@router.get("/{order_id}", response_model=OrderDTO)
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
