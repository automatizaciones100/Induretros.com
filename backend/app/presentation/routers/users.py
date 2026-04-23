"""
A.8.5  — Autenticación segura (cambio de contraseña) | ISO 27001:2022
A.8.10 — Eliminación de información (derecho al olvido) | ISO 27001:2022
A.5.34 — Privacidad y protección de PII | ISO 27001:2022
"""
from fastapi import APIRouter, Depends, HTTPException, Request, status
from app.application.dtos.user_dto import ChangePasswordCommand, DeleteAccountCommand
from app.application.use_cases.auth.change_password import ChangePasswordUseCase
from app.application.use_cases.auth.delete_user import DeleteUserUseCase
from app.domain.exceptions import InvalidCredentialsError, EntityNotFoundError
from app.presentation.dependencies import (
    change_password_use_case,
    delete_user_use_case,
    get_current_user,
)
from app.presentation.rate_limiter import limiter
from app.infrastructure.logging.security_logger import log_user_deleted, log_password_changed

router = APIRouter(prefix="/api/users", tags=["users"])


@router.post("/me/change-password", status_code=204)
@limiter.limit("3/minute")
def change_password(
    request: Request,
    command: ChangePasswordCommand,
    use_case: ChangePasswordUseCase = Depends(change_password_use_case),
    current_user: dict = Depends(get_current_user),
):
    """
    Cambia la contraseña del usuario autenticado.
    Requiere la contraseña actual para autorizar el cambio (A.8.5).
    """
    user_id = int(current_user.get("sub", 0))
    ip = request.client.host if request.client else "unknown"
    try:
        use_case.execute(user_id, command)
        log_password_changed(user_id=user_id, ip=ip)
    except InvalidCredentialsError:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Contraseña actual incorrecta",
        )
    except EntityNotFoundError:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Usuario no encontrado")


@router.delete("/me", status_code=204)
@limiter.limit("2/minute")
def delete_account(
    request: Request,
    command: DeleteAccountCommand,
    use_case: DeleteUserUseCase = Depends(delete_user_use_case),
    current_user: dict = Depends(get_current_user),
):
    """
    Elimina la cuenta del usuario y sus datos personales.
    Implementa el derecho al olvido (GDPR Art. 17 / A.8.10).
    Requiere confirmación con contraseña para evitar eliminaciones accidentales.
    """
    user_id = int(current_user.get("sub", 0))
    ip = request.client.host if request.client else "unknown"
    try:
        use_case.execute(user_id, command)
        log_user_deleted(user_id=user_id, ip=ip)
    except InvalidCredentialsError:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Contraseña incorrecta",
        )
    except EntityNotFoundError:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Usuario no encontrado")
