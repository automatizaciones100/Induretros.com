"""
A.8.15 — Registro (logging) | ISO 27001:2022
A.8.11 — Enmascaramiento de datos | ISO 27001:2022
A.5.28 — Recopilación de evidencias | ISO 27001:2022

Logger de eventos de seguridad con:
- JSON estructurado para integración con cualquier SIEM/observabilidad
- Request-ID para correlación entre eventos del mismo request
- Enmascaramiento de PII (emails) en todos los registros
- UTC timestamps para sincronización entre sistemas

Eventos registrados:
  login_failed       — credenciales incorrectas
  login_success      — autenticación exitosa
  token_invalid      — JWT inválido o expirado
  access_denied      — intento de acceso a recurso ajeno (403)
  admin_action       — acción de escritura por administrador
  data_accessed      — acceso a datos sensibles de un usuario
  user_deleted       — cuenta y datos borrados (GDPR)
  password_changed   — contraseña actualizada
  rate_limit_hit     — IP bloqueada por exceso de requests
"""
import logging
import json
import re
from datetime import datetime, timezone

# Importación condicional para evitar dependencia circular en tests
try:
    from app.presentation.middleware.request_id import get_request_id
except ImportError:
    def get_request_id() -> str:
        return "-"


_logger = logging.getLogger("security")

if not _logger.handlers:
    _handler = logging.StreamHandler()
    _handler.setFormatter(logging.Formatter("%(message)s"))
    _logger.addHandler(_handler)
    _logger.setLevel(logging.INFO)
    _logger.propagate = False


def mask_email(email: str) -> str:
    """
    Enmascara un email para logs: 'usuario@dominio.com' → 'us***@dominio.com'
    Cumple A.8.11 — los logs no deben exponer PII completa.
    """
    if not email or "@" not in email:
        return "***"
    local, domain = email.split("@", 1)
    visible = local[:2] if len(local) >= 2 else local[:1]
    return f"{visible}***@{domain}"


def _log(event: str, **kwargs) -> None:
    record = {
        "ts": datetime.now(timezone.utc).isoformat(),
        "request_id": get_request_id(),
        "event": event,
        **kwargs,
    }
    _logger.info(json.dumps(record, ensure_ascii=False))


def log_login_failed(email: str, ip: str) -> None:
    _log("login_failed", email=mask_email(email), ip=ip)


def log_login_success(user_id: int, email: str, ip: str) -> None:
    _log("login_success", user_id=user_id, email=mask_email(email), ip=ip)


def log_token_invalid(ip: str) -> None:
    _log("token_invalid", ip=ip)


def log_access_denied(user_id: int, resource: str, ip: str) -> None:
    _log("access_denied", user_id=user_id, resource=resource, ip=ip)


def log_admin_action(user_id: int, action: str, resource: str, ip: str) -> None:
    _log("admin_action", user_id=user_id, action=action, resource=resource, ip=ip)


def log_data_accessed(user_id: int, resource: str, ip: str) -> None:
    """A.8.15 — Registra acceso a datos sensibles (órdenes, perfil)."""
    _log("data_accessed", user_id=user_id, resource=resource, ip=ip)


def log_user_deleted(user_id: int, ip: str) -> None:
    """A.8.10 / A.5.34 — Registra borrado de cuenta por solicitud del titular."""
    _log("user_deleted", user_id=user_id, ip=ip)


def log_password_changed(user_id: int, ip: str) -> None:
    """A.8.5 — Registra cambio de contraseña."""
    _log("password_changed", user_id=user_id, ip=ip)
