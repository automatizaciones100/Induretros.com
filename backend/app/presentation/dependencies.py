"""
Wiring de inyección de dependencias (DI).

Cada función retorna la implementación concreta de una interfaz de dominio.
Los routers dependen SOLO de estas funciones, nunca de las implementaciones directamente.
"""
from fastapi import Depends, HTTPException, Request, status
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from sqlalchemy.orm import Session
from jose import jwt, JWTError
from app.database import get_db
from app.config import settings
from app.infrastructure.logging.security_logger import log_token_invalid

# Interfaces
from app.domain.repositories.product_repository import IProductRepository, ICategoryRepository
from app.domain.repositories.user_repository import IUserRepository
from app.domain.repositories.order_repository import IOrderRepository
from app.application.services.password_hasher import IPasswordHasher
from app.application.services.jwt_service import IJwtService

# Implementaciones concretas
from app.infrastructure.database.repositories.product_repository import (
    SQLAlchemyProductRepository,
    SQLAlchemyCategoryRepository,
)
from app.infrastructure.database.repositories.user_repository import SQLAlchemyUserRepository
from app.infrastructure.database.repositories.order_repository import SQLAlchemyOrderRepository
from app.infrastructure.security.password_hasher import BcryptPasswordHasher
from app.infrastructure.security.jwt_service import JoseJwtService

# Use Cases
from app.application.use_cases.products.get_products import GetProductsUseCase
from app.application.use_cases.products.get_product import GetProductUseCase
from app.application.use_cases.products.create_product import CreateProductUseCase
from app.application.use_cases.products.update_product import UpdateProductUseCase
from app.application.use_cases.products.delete_product import DeleteProductUseCase
from app.application.use_cases.products.get_categories import GetCategoriesUseCase
from app.application.use_cases.products.get_category import GetCategoryUseCase
from app.application.use_cases.products.create_category import CreateCategoryUseCase
from app.application.use_cases.products.update_category import UpdateCategoryUseCase
from app.application.use_cases.products.delete_category import DeleteCategoryUseCase
from app.application.use_cases.auth.register_user import RegisterUserUseCase
from app.application.use_cases.auth.login_user import LoginUserUseCase
from app.application.use_cases.orders.create_order import CreateOrderUseCase
from app.application.use_cases.orders.get_order import GetOrderUseCase
from app.application.use_cases.auth.change_password import ChangePasswordUseCase
from app.application.use_cases.auth.delete_user import DeleteUserUseCase


# --- Repositorios ---

def get_product_repository(db: Session = Depends(get_db)) -> IProductRepository:
    return SQLAlchemyProductRepository(db)


def get_category_repository(db: Session = Depends(get_db)) -> ICategoryRepository:
    return SQLAlchemyCategoryRepository(db)


def get_user_repository(db: Session = Depends(get_db)) -> IUserRepository:
    return SQLAlchemyUserRepository(db)


def get_order_repository(db: Session = Depends(get_db)) -> IOrderRepository:
    return SQLAlchemyOrderRepository(db)


# --- Servicios de seguridad ---

def get_password_hasher() -> IPasswordHasher:
    return BcryptPasswordHasher()


def get_jwt_service() -> IJwtService:
    return JoseJwtService()


# --- Use Cases ---

def get_products_use_case(
    repo: IProductRepository = Depends(get_product_repository),
) -> GetProductsUseCase:
    return GetProductsUseCase(repo)


def get_product_use_case(
    repo: IProductRepository = Depends(get_product_repository),
) -> GetProductUseCase:
    return GetProductUseCase(repo)


def create_product_use_case(
    repo: IProductRepository = Depends(get_product_repository),
) -> CreateProductUseCase:
    return CreateProductUseCase(repo)


def update_product_use_case(
    repo: IProductRepository = Depends(get_product_repository),
) -> UpdateProductUseCase:
    return UpdateProductUseCase(repo)


def delete_product_use_case(
    repo: IProductRepository = Depends(get_product_repository),
) -> DeleteProductUseCase:
    return DeleteProductUseCase(repo)


def get_categories_use_case(
    repo: ICategoryRepository = Depends(get_category_repository),
) -> GetCategoriesUseCase:
    return GetCategoriesUseCase(repo)


def get_category_use_case(
    repo: ICategoryRepository = Depends(get_category_repository),
) -> GetCategoryUseCase:
    return GetCategoryUseCase(repo)


def create_category_use_case(
    repo: ICategoryRepository = Depends(get_category_repository),
) -> CreateCategoryUseCase:
    return CreateCategoryUseCase(repo)


def update_category_use_case(
    repo: ICategoryRepository = Depends(get_category_repository),
) -> UpdateCategoryUseCase:
    return UpdateCategoryUseCase(repo)


def delete_category_use_case(
    repo: ICategoryRepository = Depends(get_category_repository),
) -> DeleteCategoryUseCase:
    return DeleteCategoryUseCase(repo)


def register_user_use_case(
    repo: IUserRepository = Depends(get_user_repository),
    hasher: IPasswordHasher = Depends(get_password_hasher),
) -> RegisterUserUseCase:
    return RegisterUserUseCase(repo, hasher)


def login_user_use_case(
    repo: IUserRepository = Depends(get_user_repository),
    hasher: IPasswordHasher = Depends(get_password_hasher),
    jwt: IJwtService = Depends(get_jwt_service),
) -> LoginUserUseCase:
    return LoginUserUseCase(repo, hasher, jwt)


def create_order_use_case(
    order_repo: IOrderRepository = Depends(get_order_repository),
    product_repo: IProductRepository = Depends(get_product_repository),
) -> CreateOrderUseCase:
    return CreateOrderUseCase(order_repo, product_repo)


def get_order_use_case(
    repo: IOrderRepository = Depends(get_order_repository),
) -> GetOrderUseCase:
    return GetOrderUseCase(repo)


def change_password_use_case(
    repo: IUserRepository = Depends(get_user_repository),
    hasher: IPasswordHasher = Depends(get_password_hasher),
) -> ChangePasswordUseCase:
    return ChangePasswordUseCase(repo, hasher)


def delete_user_use_case(
    repo: IUserRepository = Depends(get_user_repository),
    hasher: IPasswordHasher = Depends(get_password_hasher),
) -> DeleteUserUseCase:
    return DeleteUserUseCase(repo, hasher)


# --- Autenticación ---

_bearer_scheme = HTTPBearer()


def get_current_user(
    request: Request,
    credentials: HTTPAuthorizationCredentials = Depends(_bearer_scheme),
) -> dict:
    """
    Valida el JWT del header Authorization: Bearer <token>.
    Retorna el payload decodificado; lanza 401 si el token es inválido o expiró.
    """
    token = credentials.credentials
    try:
        payload = jwt.decode(token, settings.secret_key, algorithms=[settings.algorithm])
        return payload
    except (JWTError, Exception):
        ip = request.client.host if request.client else "unknown"
        log_token_invalid(ip=ip)
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Token inválido o expirado",
            headers={"WWW-Authenticate": "Bearer"},
        )


def get_current_admin(current_user: dict = Depends(get_current_user)) -> dict:
    """
    Requiere que el usuario autenticado tenga is_admin=True.
    Lanza 403 si es un usuario normal.
    """
    if not current_user.get("is_admin", False):
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Se requieren permisos de administrador",
        )
    return current_user
