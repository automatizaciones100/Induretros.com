from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker, declarative_base
from app.config import settings

_is_sqlite = settings.database_url.startswith("sqlite")

if _is_sqlite:
    # SQLite no soporta múltiples hilos por conexión; pool no aplica
    engine = create_engine(
        settings.database_url,
        connect_args={"check_same_thread": False},
    )
else:
    # PostgreSQL en producción: pool explícito + pool_pre_ping detecta conexiones caídas
    engine = create_engine(
        settings.database_url,
        pool_size=10,
        max_overflow=20,
        pool_pre_ping=True,
    )

SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
Base = declarative_base()


def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()
