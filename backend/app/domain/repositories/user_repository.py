from abc import ABC, abstractmethod
from typing import Optional
from app.domain.entities.user import User


class IUserRepository(ABC):
    @abstractmethod
    def get_by_email(self, email: str) -> Optional[User]:
        ...

    @abstractmethod
    def get_by_id(self, user_id: int) -> Optional[User]:
        ...

    @abstractmethod
    def create(self, user: User) -> User:
        ...

    @abstractmethod
    def update_password(self, user_id: int, hashed_password: str) -> None:
        """A.8.5 — Actualiza la contraseña hasheada del usuario."""
        ...

    @abstractmethod
    def delete(self, user_id: int) -> None:
        """A.8.10 / A.5.34 — Elimina la cuenta y todos sus datos (GDPR right-to-erasure)."""
        ...
