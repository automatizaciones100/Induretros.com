# syntax=docker/dockerfile:1.7
# Backend FastAPI para AWS App Runner.
# Build context = backend/  (configurar en App Runner: Source directory = backend)

FROM python:3.13-slim

# Variables de entorno para Python: no .pyc + stdout sin buffering (mejor logs)
ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1 \
    PIP_DISABLE_PIP_VERSION_CHECK=1

WORKDIR /app

# Capa de dependencias separada: si requirements.txt no cambia, el cache la reusa.
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Código de la app
COPY app ./app

# Usuario no-root (best practice). UID 1000 para evitar colisiones con root del host.
RUN useradd --create-home --uid 1000 appuser \
    && chown -R appuser:appuser /app
USER appuser

# App Runner inyecta PORT (default 8080). Localmente cae a 8000.
ENV PORT=8000
EXPOSE 8000

# App Runner usa su propio health check (configurable a /health desde la consola),
# pero este HEALTHCHECK sirve si corres el container con `docker run` o ECS.
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD python -c "import urllib.request,sys; sys.exit(0 if urllib.request.urlopen(f'http://localhost:{__import__(\"os\").environ.get(\"PORT\",\"8000\")}/health').getcode()==200 else 1)"

# 2 workers es suficiente para App Runner 0.25vCPU/0.5GB. Subir si escalas vertical.
CMD ["sh", "-c", "uvicorn app.main:app --host 0.0.0.0 --port ${PORT} --workers 2 --proxy-headers --forwarded-allow-ips=*"]
