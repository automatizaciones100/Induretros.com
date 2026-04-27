"""
Verificación de Cloudflare Turnstile CAPTCHA.

Si TURNSTILE_SECRET_KEY no está configurada (entorno dev/test), la verificación
se omite automáticamente para no bloquear el desarrollo local.
"""
import httpx
from fastapi import HTTPException, status
from app.config import settings

VERIFY_URL = "https://challenges.cloudflare.com/turnstile/v0/siteverify"


def verify_turnstile(token: str | None, ip: str = "unknown") -> None:
    """
    Verifica el token de Turnstile contra la API de Cloudflare.
    Lanza HTTP 400 si el token es inválido.
    Si TURNSTILE_SECRET_KEY está vacío, la función retorna sin hacer nada.
    """
    secret = settings.turnstile_secret_key
    if not secret:
        # Dev/test: sin clave configurada, se omite la verificación
        return

    if not token:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Se requiere verificación CAPTCHA",
        )

    try:
        resp = httpx.post(
            VERIFY_URL,
            data={"secret": secret, "response": token, "remoteip": ip},
            timeout=5.0,
        )
        result = resp.json()
    except Exception:
        # Si Cloudflare no responde, no bloquear al usuario en producción
        return

    if not result.get("success", False):
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Verificación CAPTCHA fallida. Intenta de nuevo.",
        )
