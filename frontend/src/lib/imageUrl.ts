/**
 * Resuelve una URL de imagen al servidor correcto.
 *
 * Las imágenes subidas vía /api/images/upload se guardan con URL relativa
 * (`/static/images/foo.webp`). El backend las sirve en `localhost:8000`,
 * pero el frontend corre en `localhost:3000`, así que sin prefijo Next.js
 * pediría la imagen al frontend → 404.
 *
 * Reglas:
 *   - URL absoluta (http/https) → se devuelve tal cual
 *   - Empieza con "/static/"   → prefija con NEXT_PUBLIC_API_URL (backend)
 *   - Cualquier otra ruta /…   → se devuelve tal cual (frontend/public/)
 *   - vacío/undefined          → undefined
 */
const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

export function resolveImageUrl(url?: string | null): string | undefined {
  if (!url) return undefined;
  if (url.startsWith("http://") || url.startsWith("https://")) return url;
  if (url.startsWith("/static/")) return `${API_URL}${url}`;
  return url;
}
