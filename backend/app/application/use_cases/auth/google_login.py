"""
Login (o registro implícito) con Google Sign-In.

Recibe un Google ID token (JWT firmado por Google), lo verifica contra las
claves públicas de Google y la audiencia esperada (nuestro client_id),
y retorna nuestro propio JWT.

Si el email aún no existe en la base de datos, se crea una cuenta de cliente
con contraseña aleatoria (no usable — el usuario nunca la sabrá; siempre
inicia sesión con Google). Si ya existe, se reutiliza.
"""
import secrets
from typing import Optional

from google.oauth2 import id_token
from google.auth.transport import requests as google_requests

from app.domain.entities.user import User
from app.domain.repositories.user_repository import IUserRepository
from app.application.services.password_hasher import IPasswordHasher
from app.application.services.jwt_service import IJwtService
from app.application.dtos.user_dto import TokenDTO


class InvalidGoogleTokenError(Exception):
    """El token de Google no pudo verificarse o tiene una audiencia incorrecta."""
    pass


class GoogleAccountNotVerifiedError(Exception):
    """La cuenta de Google del usuario no tiene email verificado."""
    pass


class GoogleLoginUseCase:
    def __init__(
        self,
        user_repo: IUserRepository,
        password_hasher: IPasswordHasher,
        jwt_service: IJwtService,
        google_client_id: str,
    ):
        self._repo = user_repo
        self._hasher = password_hasher
        self._jwt = jwt_service
        self._google_client_id = google_client_id

    def execute(self, credential: str) -> TokenDTO:
        if not self._google_client_id:
            raise InvalidGoogleTokenError("Google Sign-In no está configurado en este servidor")

        # Verifica firma + audiencia + expiración. Lanza ValueError si algo falla.
        try:
            info = id_token.verify_oauth2_token(
                credential,
                google_requests.Request(),
                self._google_client_id,
                clock_skew_in_seconds=10,
            )
        except ValueError as e:
            raise InvalidGoogleTokenError(f"Token de Google inválido: {e}")

        email: Optional[str] = info.get("email")
        if not email:
            raise InvalidGoogleTokenError("El token de Google no contiene email")

        if not info.get("email_verified", False):
            raise GoogleAccountNotVerifiedError(
                "La cuenta de Google no tiene el email verificado"
            )

        name = info.get("name") or info.get("given_name") or email.split("@")[0]

        # Upsert por email
        user = self._repo.get_by_email(email)
        if user is None:
            # Genera una contraseña aleatoria no usable. El usuario nunca la sabrá:
            # siempre inicia con Google. Esta contraseña queda solo para satisfacer
            # el NOT NULL del modelo y no se expone por ningún flujo.
            random_password = secrets.token_urlsafe(32)
            user = User(
                id=None,
                email=email.lower(),
                name=name[:100],
                hashed_password=self._hasher.hash(random_password),
                is_active=True,
                is_admin=False,
            )
            user = self._repo.create(user)

        if not user.is_active:
            raise InvalidGoogleTokenError("La cuenta está inactiva")

        token = self._jwt.create_token(
            user_id=user.id,
            email=user.email,
            is_admin=user.is_admin,
        )
        return TokenDTO(access_token=token, token_type="bearer")
