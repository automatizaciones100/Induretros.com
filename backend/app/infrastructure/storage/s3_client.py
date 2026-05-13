"""
Wrapper de S3 para imágenes UGC. Si AWS_S3_BUCKET está vacío (dev sin AWS),
las funciones quedan deshabilitadas y el caller debe usar filesystem local.

Credenciales: boto3 las resuelve por orden estándar (env vars → IAM role →
~/.aws/credentials). En App Runner viene por Instance Profile, sin secrets
explícitos en variables de entorno.
"""
from functools import lru_cache
from typing import Optional

from app.config import settings


def is_s3_enabled() -> bool:
    return bool(settings.aws_s3_bucket)


@lru_cache(maxsize=1)
def _client():
    # Import diferido: si boto3 no está instalado (dev sin AWS), el módulo no rompe
    # al cargarse. Sólo falla cuando alguien realmente intenta usar S3.
    import boto3
    return boto3.client("s3", region_name=settings.aws_region)


def upload_bytes(key: str, content: bytes, content_type: str) -> str:
    """Sube un objeto a S3. Retorna la key (no la URL completa)."""
    _client().put_object(
        Bucket=settings.aws_s3_bucket,
        Key=key,
        Body=content,
        ContentType=content_type,
        # CacheControl largo: las imágenes son inmutables (key incluye el SKU/slug).
        # Si se reemplaza una imagen, se sube con la misma key y se invalida CloudFront.
        CacheControl="public, max-age=31536000, immutable",
    )
    return key


def delete_object(key: str) -> None:
    from botocore.exceptions import ClientError
    try:
        _client().delete_object(Bucket=settings.aws_s3_bucket, Key=key)
    except ClientError:
        # No bloquea — un delete fallido (objeto no existe) no debe romper la operación.
        pass


def list_objects(prefix: str = "") -> list[dict]:
    """Lista hasta 1000 objetos bajo un prefijo. Retorna [{key, size, last_modified}]."""
    resp = _client().list_objects_v2(Bucket=settings.aws_s3_bucket, Prefix=prefix)
    return [
        {"key": obj["Key"], "size": obj["Size"], "last_modified": obj["LastModified"]}
        for obj in resp.get("Contents", [])
    ]


def public_url(key: str) -> Optional[str]:
    """URL pública del objeto. Si CDN_BASE_URL está configurado, usa CDN.
    Sin CDN, retorna el endpoint S3 directo (sólo funciona si el bucket es público)."""
    if not key:
        return None
    if settings.cdn_base_url:
        return f"{settings.cdn_base_url.rstrip('/')}/{key.lstrip('/')}"
    return f"https://{settings.aws_s3_bucket}.s3.{settings.aws_region}.amazonaws.com/{key.lstrip('/')}"
