from fastapi import APIRouter, Depends, HTTPException, Request, status
from app.application.dtos.user_dto import RegisterUserCommand, LoginCommand, UserDTO, TokenDTO
from app.application.use_cases.auth.register_user import RegisterUserUseCase
from app.application.use_cases.auth.login_user import LoginUserUseCase
from app.domain.exceptions import DuplicateEmailError, InvalidCredentialsError
from app.presentation.dependencies import register_user_use_case, login_user_use_case
from app.presentation.rate_limiter import limiter
from app.infrastructure.logging.security_logger import log_login_failed, log_login_success
from app.infrastructure.security.turnstile import verify_turnstile
from app.infrastructure.security.login_throttle import check_and_record_failure, reset_failures
from app.config import settings
from jose import jwt as _jwt

router = APIRouter(prefix="/api/auth", tags=["auth"])


@router.post("/register", response_model=UserDTO, status_code=201)
@limiter.limit("3/minute")
def register(
    request: Request,
    command: RegisterUserCommand,
    use_case: RegisterUserUseCase = Depends(register_user_use_case),
):
    ip = request.client.host if request.client else "unknown"
    verify_turnstile(command.cf_turnstile_response, ip)
    try:
        user = use_case.execute(command)
        return UserDTO.model_validate(user, from_attributes=True)
    except DuplicateEmailError as e:
        raise HTTPException(status_code=400, detail=str(e))


@router.post("/login", response_model=TokenDTO)
@limiter.limit("5/minute")
def login(
    request: Request,
    command: LoginCommand,
    use_case: LoginUserUseCase = Depends(login_user_use_case),
):
    ip = request.client.host if request.client else "unknown"
    verify_turnstile(command.cf_turnstile_response, ip)
    try:
        token_dto = use_case.execute(command)
        payload = _jwt.decode(token_dto.access_token, settings.secret_key, algorithms=[settings.algorithm])
        user_id = int(payload.get("sub", 0))
        reset_failures(command.email)
        log_login_success(user_id=user_id, email=command.email, ip=ip)
        return token_dto
    except InvalidCredentialsError as e:
        log_login_failed(email=command.email, ip=ip)
        try:
            check_and_record_failure(command.email)
        except ValueError as lock_err:
            raise HTTPException(status_code=status.HTTP_429_TOO_MANY_REQUESTS, detail=str(lock_err))
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail=str(e))
