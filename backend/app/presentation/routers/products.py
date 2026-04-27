from fastapi import APIRouter, Depends, HTTPException, Query, Request
from typing import Optional
from app.presentation.rate_limiter import limiter
from app.application.dtos.product_dto import (
    ProductDTO,
    ProductSummaryDTO,
    ProductListDTO,
    CategoryDTO,
    CreateProductCommand,
    UpdateProductCommand,
    CreateCategoryCommand,
    GetProductsQuery,
)
from app.application.use_cases.products.get_products import GetProductsUseCase
from app.application.use_cases.products.get_product import GetProductUseCase
from app.application.use_cases.products.create_product import CreateProductUseCase
from app.application.use_cases.products.update_product import UpdateProductUseCase
from app.application.use_cases.products.delete_product import DeleteProductUseCase
from app.application.use_cases.products.get_categories import GetCategoriesUseCase
from app.application.use_cases.products.get_category import GetCategoryUseCase
from app.application.use_cases.products.create_category import CreateCategoryUseCase
from app.domain.exceptions import EntityNotFoundError
from app.presentation.dependencies import (
    get_products_use_case,
    get_product_use_case,
    create_product_use_case,
    update_product_use_case,
    delete_product_use_case,
    get_categories_use_case,
    get_category_use_case,
    create_category_use_case,
    get_current_admin,
)
from app.infrastructure.logging.security_logger import log_admin_action

router = APIRouter(prefix="/api/products", tags=["products"])


@router.get("/categories", response_model=list[CategoryDTO])
def list_categories(
    use_case: GetCategoriesUseCase = Depends(get_categories_use_case),
):
    categories = use_case.execute()
    return [CategoryDTO.model_validate(c, from_attributes=True) for c in categories]


@router.get("/categories/{slug}", response_model=CategoryDTO)
def retrieve_category(
    slug: str,
    use_case: GetCategoryUseCase = Depends(get_category_use_case),
):
    try:
        category = use_case.execute(slug)
        return CategoryDTO.model_validate(category, from_attributes=True)
    except EntityNotFoundError as e:
        raise HTTPException(status_code=404, detail=str(e))


@router.get("", response_model=ProductListDTO)
@limiter.limit("60/minute")
def list_products(
    request: Request,
    page: int = Query(1, ge=1),
    per_page: int = Query(12, ge=1, le=100),
    category: Optional[str] = Query(None, max_length=100),
    search: Optional[str] = Query(None, max_length=100),
    featured: Optional[bool] = None,
    use_case: GetProductsUseCase = Depends(get_products_use_case),
):
    query = GetProductsQuery(
        page=page,
        per_page=per_page,
        category=category,
        search=search,
        featured=featured,
    )
    items, total = use_case.execute(query)
    pages = (total + per_page - 1) // per_page
    return ProductListDTO(
        items=[ProductSummaryDTO.model_validate(p, from_attributes=True) for p in items],
        total=total,
        page=page,
        pages=pages,
    )


@router.get("/{slug}", response_model=ProductDTO)
def retrieve_product(
    slug: str,
    use_case: GetProductUseCase = Depends(get_product_use_case),
):
    try:
        product = use_case.execute(slug)
        return ProductDTO.model_validate(product, from_attributes=True)
    except EntityNotFoundError as e:
        raise HTTPException(status_code=404, detail=str(e))


@router.post("", response_model=ProductDTO, status_code=201)
def create_product(
    request: Request,
    command: CreateProductCommand,
    use_case: CreateProductUseCase = Depends(create_product_use_case),
    admin: dict = Depends(get_current_admin),
):
    product = use_case.execute(command)
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="create_product",
        resource=f"product:{product.slug}",
        ip=ip,
    )
    return ProductDTO.model_validate(product, from_attributes=True)


@router.put("/{product_id}", response_model=ProductDTO)
def update_product(
    product_id: int,
    command: UpdateProductCommand,
    request: Request,
    use_case: UpdateProductUseCase = Depends(update_product_use_case),
    admin: dict = Depends(get_current_admin),
):
    try:
        product = use_case.execute(product_id, command)
    except EntityNotFoundError as e:
        raise HTTPException(status_code=404, detail=str(e))
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="update_product",
        resource=f"product:{product.slug}",
        ip=ip,
    )
    return ProductDTO.model_validate(product, from_attributes=True)


@router.delete("/{product_id}", status_code=204)
def delete_product(
    product_id: int,
    request: Request,
    use_case: DeleteProductUseCase = Depends(delete_product_use_case),
    admin: dict = Depends(get_current_admin),
):
    try:
        use_case.execute(product_id)
    except EntityNotFoundError as e:
        raise HTTPException(status_code=404, detail=str(e))
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="delete_product",
        resource=f"product:{product_id}",
        ip=ip,
    )


@router.post("/categories", response_model=CategoryDTO, status_code=201)
def create_category(
    request: Request,
    command: CreateCategoryCommand,
    use_case: CreateCategoryUseCase = Depends(create_category_use_case),
    admin: dict = Depends(get_current_admin),
):
    category = use_case.execute(command)
    ip = request.client.host if request.client else "unknown"
    log_admin_action(
        user_id=int(admin.get("sub", 0)),
        action="create_category",
        resource=f"category:{category.slug}",
        ip=ip,
    )
    return CategoryDTO.model_validate(category, from_attributes=True)
