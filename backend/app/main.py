from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from slowapi import _rate_limit_exceeded_handler
from slowapi.errors import RateLimitExceeded
from app.config import settings
from app.database import Base, engine

# Importar todos los modelos para que Base.metadata los registre antes de create_all
import app.infrastructure.database.models  # noqa: F401

from app.presentation.routers import products, auth, orders
from app.presentation.rate_limiter import limiter

Base.metadata.create_all(bind=engine)

_is_production = settings.environment == "production"

app = FastAPI(
    title="Induretros API",
    description="API para la tienda de repuestos para excavadoras hidráulicas",
    version="2.0.0",
    # En producción los endpoints de documentación quedan deshabilitados
    docs_url=None if _is_production else "/docs",
    redoc_url=None if _is_production else "/redoc",
    openapi_url=None if _is_production else "/openapi.json",
)

app.state.limiter = limiter
app.add_exception_handler(RateLimitExceeded, _rate_limit_exceeded_handler)

app.add_middleware(
    CORSMiddleware,
    allow_origins=[settings.frontend_url],
    allow_credentials=True,
    allow_methods=["GET", "POST", "OPTIONS"],
    allow_headers=["Content-Type", "Authorization"],
)

app.include_router(products.router)
app.include_router(auth.router)
app.include_router(orders.router)


@app.get("/")
def root():
    return {"message": "Induretros API - Repuestos para excavadoras hidráulicas"}


@app.get("/health")
def health():
    return {"status": "ok"}
