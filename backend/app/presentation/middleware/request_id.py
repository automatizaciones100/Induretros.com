"""
A.5.28 — Recopilación de evidencias (ISO 27001:2022)

Middleware que genera un UUID único por request y lo propaga:
  1. Como header de respuesta X-Request-ID (trazabilidad cliente-servidor)
  2. Como variable de contexto disponible para todos los loggers del request

Esto permite correlacionar todos los eventos de un mismo request en cualquier
sistema de observabilidad (Loki, Datadog, CloudWatch, etc.).
"""
import uuid
from contextvars import ContextVar
from starlette.middleware.base import BaseHTTPMiddleware
from starlette.requests import Request
from starlette.responses import Response

# Variable de contexto — cada request tiene su propia copia (thread-safe con async)
request_id_var: ContextVar[str] = ContextVar("request_id", default="-")


def get_request_id() -> str:
    """Retorna el request_id del request actual."""
    return request_id_var.get()


class RequestIdMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next) -> Response:
        req_id = request.headers.get("X-Request-ID") or str(uuid.uuid4())
        token = request_id_var.set(req_id)
        try:
            response = await call_next(request)
            response.headers["X-Request-ID"] = req_id
            return response
        finally:
            request_id_var.reset(token)
