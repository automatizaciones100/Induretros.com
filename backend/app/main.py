from pathlib import Path
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles
from slowapi import _rate_limit_exceeded_handler
from slowapi.errors import RateLimitExceeded
from app.config import settings
from app.database import Base, engine, SessionLocal

# Importar todos los modelos para que Base.metadata los registre antes de create_all
import app.infrastructure.database.models  # noqa: F401

from app.presentation.routers import products, auth, orders, users, images, admin, analytics
from app.presentation.rate_limiter import limiter
from app.presentation.middleware.request_id import RequestIdMiddleware

# Directorio de archivos estáticos (imágenes de productos)
_STATIC_DIR = Path(__file__).resolve().parents[2] / "static"
_STATIC_DIR.mkdir(exist_ok=True)
((_STATIC_DIR / "images")).mkdir(exist_ok=True)

Base.metadata.create_all(bind=engine)

_is_production = settings.environment == "production"
# Los docs solo se exponen cuando se solicita explícitamente.
# En producción están SIEMPRE deshabilitados, aunque SHOW_DOCS=true.
_show_docs = settings.show_docs and not _is_production

app = FastAPI(
    title="Induretros API",
    description="API para la tienda de repuestos para excavadoras hidráulicas",
    version="2.0.0",
    docs_url="/docs" if _show_docs else None,
    redoc_url="/redoc" if _show_docs else None,
    openapi_url="/openapi.json" if _show_docs else None,
)

# A.5.28 — Request-ID middleware para trazabilidad (debe ir antes de CORSMiddleware)
app.add_middleware(RequestIdMiddleware)

app.state.limiter = limiter
app.add_exception_handler(RateLimitExceeded, _rate_limit_exceeded_handler)

app.add_middleware(
    CORSMiddleware,
    allow_origins=[settings.frontend_url],
    allow_credentials=True,
    allow_methods=["GET", "POST", "PUT", "DELETE", "OPTIONS"],
    allow_headers=["Content-Type", "Authorization", "X-Request-ID"],
)

app.include_router(products.router)
app.include_router(auth.router)
app.include_router(orders.router)
app.include_router(users.router)
app.include_router(images.router)
app.include_router(admin.router)
app.include_router(analytics.router)

# Servir imágenes subidas como archivos estáticos: GET /static/images/FLT-001.jpg
app.mount("/static", StaticFiles(directory=str(_STATIC_DIR)), name="static")


@app.get("/")
def root():
    return {"message": "Induretros API - Repuestos para excavadoras hidráulicas"}


@app.get("/health")
def health():
    """
    A.8.16 — Monitorización de actividades | ISO 27001:2022
    Verifica conectividad a la base de datos además del estado del proceso.
    Usado por load balancers, orquestadores (k8s) y sistemas de alerta.
    """
    db_ok = False
    try:
        db = SessionLocal()
        db.execute(__import__("sqlalchemy").text("SELECT 1"))
        db.close()
        db_ok = True
    except Exception:
        pass

    status_code = 200 if db_ok else 503
    body = {
        "status": "ok" if db_ok else "degraded",
        "database": "ok" if db_ok else "error",
        "version": "2.0.0",
    }
    from fastapi.responses import JSONResponse
    return JSONResponse(content=body, status_code=status_code)
