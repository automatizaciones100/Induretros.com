"""
Protección contra brute-force por email.

Complementa el rate limiting por IP de slowapi con un contador por dirección
de correo. Tras MAX_ATTEMPTS fallos consecutivos en WINDOW_SECONDS, la cuenta
queda bloqueada durante LOCKOUT_SECONDS.

Implementación en memoria: suficiente para un servidor single-process.
Para multi-proceso/multi-instancia usa Redis con el mismo contrato.
"""
import time
from collections import defaultdict
from threading import Lock

MAX_ATTEMPTS = 10          # fallos antes de bloquear
WINDOW_SECONDS = 300       # ventana de 5 minutos para contar intentos
LOCKOUT_SECONDS = 900      # bloqueo de 15 minutos

_lock = Lock()

# email → {"count": int, "first_attempt": float, "locked_until": float}
_attempts: dict[str, dict] = defaultdict(lambda: {"count": 0, "first_attempt": 0.0, "locked_until": 0.0})


def check_and_record_failure(email: str) -> None:
    """
    Llama DESPUÉS de un fallo de autenticación.
    Lanza ValueError si la cuenta está o queda bloqueada.
    """
    now = time.monotonic()
    email = email.lower()

    with _lock:
        record = _attempts[email]

        # ¿Bloqueado actualmente?
        if record["locked_until"] > now:
            remaining = int(record["locked_until"] - now)
            raise ValueError(f"Cuenta bloqueada temporalmente. Intenta en {remaining} s.")

        # ¿La ventana de conteo expiró? Reset.
        if now - record["first_attempt"] > WINDOW_SECONDS:
            record["count"] = 0
            record["first_attempt"] = now

        record["count"] += 1

        if record["count"] >= MAX_ATTEMPTS:
            record["locked_until"] = now + LOCKOUT_SECONDS
            record["count"] = 0
            record["first_attempt"] = 0.0
            raise ValueError(
                f"Demasiados intentos fallidos. Cuenta bloqueada por {LOCKOUT_SECONDS // 60} minutos."
            )


def reset_failures(email: str) -> None:
    """Llama tras un login exitoso para limpiar el contador."""
    with _lock:
        _attempts.pop(email.lower(), None)
