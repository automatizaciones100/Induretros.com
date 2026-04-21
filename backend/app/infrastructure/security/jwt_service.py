from datetime import datetime, timedelta, timezone
from jose import jwt
from app.application.services.jwt_service import IJwtService
from app.config import settings


class JoseJwtService(IJwtService):
    def create_token(self, user_id: int, email: str, is_admin: bool = False) -> str:
        expire = datetime.now(timezone.utc) + timedelta(minutes=settings.access_token_expire_minutes)
        payload = {"sub": str(user_id), "email": email, "is_admin": is_admin, "exp": expire}
        return jwt.encode(payload, settings.secret_key, algorithm=settings.algorithm)
