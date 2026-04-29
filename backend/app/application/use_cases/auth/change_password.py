"""A.8.5 — Autenticación segura | ISO 27001:2022"""
from app.domain.repositories.user_repository import IUserRepository
from app.domain.exceptions import InvalidCredentialsError, EntityNotFoundError
from app.application.services.password_hasher import IPasswordHasher
from app.application.dtos.user_dto import ChangePasswordCommand


class ChangePasswordUseCase:
    def __init__(self, user_repo: IUserRepository, password_hasher: IPasswordHasher):
        self._repo = user_repo
        self._hasher = password_hasher

    def execute(self, user_id: int, command: ChangePasswordCommand) -> None:
        user = self._repo.get_by_id(user_id)
        if not user:
            raise EntityNotFoundError("Usuario", str(user_id))
        if not self._hasher.verify(command.current_password, user.hashed_password):
            raise InvalidCredentialsError()
        new_hash = self._hasher.hash(command.new_password)
        self._repo.update_password(user_id, new_hash)
