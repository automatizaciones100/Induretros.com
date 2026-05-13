/**
 * Resuelve una URL de imagen al servidor correcto según el path en BD.
 *
 * Formatos soportados:
 *   - URL absoluta (http/https)     → tal cual
 *   - `/images/foo.webp`            → CDN (prod) o backend (dev sin CDN)
 *   - `/static/images/foo.webp`     → backend (legacy — URLs antiguas en BD)
 *   - Cualquier otra ruta `/…`      → tal cual (asume asset estático de frontend/public)
 *   - vacío/undefined               → undefined
 *
 * En prod las URLs canónicas guardadas en BD son `/images/...` y CloudFront
 * las sirve directamente desde S3 sin pasar por el backend.
 */
const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";
const CDN_URL = process.env.NEXT_PUBLIC_CDN_URL || "";

export function resolveImageUrl(url?: string | null): string | undefined {
  if (!url) return undefined;
  if (url.startsWith("http://") || url.startsWith("https://")) return url;
  if (url.startsWith("/images/")) {
    return CDN_URL ? `${CDN_URL.replace(/\/$/, "")}${url}` : `${API_URL}${url}`;
  }
  if (url.startsWith("/static/")) return `${API_URL}${url}`;
  return url;
}
