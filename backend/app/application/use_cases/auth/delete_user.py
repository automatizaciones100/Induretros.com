"""
A.8.10 — Eliminación de información | ISO 27001:2022
A.5.34 — Privacidad y protección de PII | ISO 27001:2022

Implementa el derecho al olvido (GDPR Art. 17).
"""
from app.domain.repositories.user_repository import IUserRepository
from app.domain.exceptions import InvalidCredentialsError, EntityNotFoundError
from app.application.services.password_hasher import IPasswordHasher
from app.application.dtos.user_dto import DeleteAccountCommand


class DeleteUserUseCase:
    def __init__(self, user_repo: IUserRepository, password_hasher: IPasswordHasher):
        self._repo = user_repo
        self._hasher = password_hasher

    def execute(self, user_id: int, command: DeleteAccountCommand) -> None:
        user = self._repo.get_by_id(user_id)
        if not user:
            raise EntityNotFoundError("Usuario", str(user_id))
        # Requiere confirmación con contraseña para evitar borrados accidentales/maliciosos
        if not self._hasher.verify(command.password, user.hashed_password):
            raise InvalidCredentialsError()
        self._repo.delete(user_id)
