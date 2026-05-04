from pydantic import model_validator
from pydantic_settings import BaseSettings


class Settings(BaseSettings):
    database_url: str = "sqlite:///./induretros.db"
    # Sin default: la app falla al arrancar si SECRET_KEY no está definida en el entorno.
    # En producción: genera con `openssl rand -hex 32`
    secret_key: str
    algorithm: str = "HS256"
    access_token_expire_minutes: int = 30
    # En producción debe ser https://www.induretros.com
    frontend_url: str = "http://localhost:3000"
    environment: str = "development"  # "production" deshabilita /docs y /redoc
    # Habilitar docs explícitamente (solo en development). En producción siempre off.
    show_docs: bool = False
    # Cloudflare Turnstile — dejar vacío en dev para omitir verificación
    turnstile_secret_key: str = ""
    # Google Sign-In — Client ID OAuth (no es secreto). Vacío = endpoint deshabilitado.
    google_client_id: str = ""

    @model_validator(mode="after")
    def validate_production_settings(self) -> "Settings":
        if self.environment == "production":
            if self.database_url.startswith("sqlite"):
                raise ValueError(
                    "SQLite no está permitido en producción. "
                    "Configura DATABASE_URL con una URL de PostgreSQL."
                )
            if not self.frontend_url.startswith("https://"):
                raise ValueError(
                    "FRONTEND_URL debe usar HTTPS en producción."
                )
        return self

    class Config:
        env_file = ".env"


settings = Settings()
