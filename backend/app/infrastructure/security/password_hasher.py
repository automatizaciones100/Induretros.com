from passlib.context import CryptContext
from app.application.services.password_hasher import IPasswordHasher

_pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")


class BcryptPasswordHasher(IPasswordHasher):
    def hash(self, password: str) -> str:
        return _pwd_context.hash(password)

    def verify(self, plain: str, hashed: str) -> bool:
        return _pwd_context.verify(plain, hashed)
