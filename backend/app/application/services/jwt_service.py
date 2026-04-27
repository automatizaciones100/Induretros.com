from abc import ABC, abstractmethod


class IJwtService(ABC):
    @abstractmethod
    def create_token(self, user_id: int, email: str, is_admin: bool = False) -> str:
        ...
