from app.domain.entities.user import User
from app.domain.repositories.user_repository import IUserRepository
from app.domain.exceptions import DuplicateEmailError
from app.application.services.password_hasher import IPasswordHasher
from app.application.dtos.user_dto import RegisterUserCommand


class RegisterUserUseCase:
    def __init__(self, user_repo: IUserRepository, password_hasher: IPasswordHasher):
        self._repo = user_repo
        self._hasher = password_hasher

    def execute(self, command: RegisterUserCommand) -> User:
        if self._repo.get_by_email(command.email):
            raise DuplicateEmailError(command.email)

        user = User(
            id=None,
            email=command.email,
            name=command.name,
            hashed_password=self._hasher.hash(command.password),
            phone=command.phone,
        )
        return self._repo.create(user)
