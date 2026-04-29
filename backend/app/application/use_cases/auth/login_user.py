from app.domain.repositories.user_repository import IUserRepository
from app.domain.exceptions import InvalidCredentialsError
from app.application.services.password_hasher import IPasswordHasher
from app.application.services.jwt_service import IJwtService
from app.application.dtos.user_dto import LoginCommand, TokenDTO


class LoginUserUseCase:
    def __init__(
        self,
        user_repo: IUserRepository,
        password_hasher: IPasswordHasher,
        jwt_service: IJwtService,
    ):
        self._repo = user_repo
        self._hasher = password_hasher
        self._jwt = jwt_service

    def execute(self, command: LoginCommand) -> TokenDTO:
        user = self._repo.get_by_email(command.email)
        if not user or not self._hasher.verify(command.password, user.hashed_password):
            raise InvalidCredentialsError()

        token = self._jwt.create_token(user_id=user.id, email=user.email, is_admin=user.is_admin)
        return TokenDTO(access_token=token, token_type="bearer")
