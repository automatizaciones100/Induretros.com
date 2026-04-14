from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.config import settings
from app.database import Base, engine
from app.routers import products, auth, orders

Base.metadata.create_all(bind=engine)

app = FastAPI(
    title="Induretros API",
    description="API para la tienda de repuestos para excavadoras hidráulicas",
    version="1.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=[settings.frontend_url],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
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
