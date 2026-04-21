"""
Logger de eventos de seguridad.

Emite líneas JSON estructuradas para que cualquier sistema de observabilidad
(Datadog, CloudWatch, Loki, etc.) pueda indexarlas y alertar sobre ellas.

Eventos registrados:
- login_failed: credenciales incorrectas
- login_success: autenticación exitosa
- token_invalid: JWT inválido o expirado
- access_denied: intento de acceso a recurso ajeno (403)
- rate_limit_hit: IP bloqueada por exceso de requests
"""
import logging
import json
from datetime import datetime, timezone


_logger = logging.getLogger("security")

if not _logger.handlers:
    _handler = logging.StreamHandler()
    _handler.setFormatter(logging.Formatter("%(message)s"))
    _logger.addHandler(_handler)
    _logger.setLevel(logging.INFO)
    _logger.propagate = False


def _log(event: str, **kwargs) -> None:
    record = {
        "ts": datetime.now(timezone.utc).isoformat(),
        "event": event,
        **kwargs,
    }
    _logger.info(json.dumps(record))


def log_login_failed(email: str, ip: str) -> None:
    _log("login_failed", email=email, ip=ip)


def log_login_success(user_id: int, email: str, ip: str) -> None:
    _log("login_success", user_id=user_id, email=email, ip=ip)


def log_token_invalid(ip: str) -> None:
    _log("token_invalid", ip=ip)


def log_access_denied(user_id: int, resource: str, ip: str) -> None:
    _log("access_denied", user_id=user_id, resource=resource, ip=ip)
