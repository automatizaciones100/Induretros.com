/**
 * Atribución de marketing — captura UTMs y gclid del landing y los persiste
 * para que sigan disponibles cuando el cliente convierta (clic a WhatsApp,
 * crear pedido) minutos u horas después.
 *
 * Reglas de atribución:
 *   - Last-touch: si el cliente vuelve por una nueva campaña, sobrescribe
 *     la atribución previa (modelo más útil para Google Ads optimization).
 *   - Direct/orgánico no sobrescribe: si el cliente ya tenía atribución de
 *     una campaña paga y vuelve directo, mantiene la atribución del último
 *     touch que SÍ tenía UTMs/gclid.
 *   - TTL: 30 días desde la captura. Industria estándar para conversiones
 *     de campañas pagas.
 */

const STORAGE_KEY = "induretros-attribution";
const TTL_MS = 30 * 24 * 60 * 60 * 1000; // 30 días

/** Campos relevantes para reportar la fuente del cliente. */
export interface Attribution {
  utm_source?: string | null;
  utm_medium?: string | null;
  utm_campaign?: string | null;
  utm_term?: string | null;
  utm_content?: string | null;
  /** Google Click ID (campañas Google Ads). */
  gclid?: string | null;
  /** URL del primer landing dentro de esta atribución. */
  landing_page?: string | null;
  /** Unix ms en que se capturó. */
  captured_at: number;
}

const ATTRIBUTION_KEYS = [
  "utm_source",
  "utm_medium",
  "utm_campaign",
  "utm_term",
  "utm_content",
  "gclid",
] as const;

/**
 * Lee los parámetros UTM/gclid de un querystring y devuelve un objeto con sólo
 * las claves presentes y no vacías. Si no hay ninguno, retorna null.
 */
function readUtmFromQuery(search: string): Partial<Attribution> | null {
  if (!search) return null;
  const params = new URLSearchParams(search.startsWith("?") ? search.slice(1) : search);
  const found: Partial<Attribution> = {};
  let any = false;
  for (const key of ATTRIBUTION_KEYS) {
    const value = params.get(key);
    if (value && value.trim()) {
      // Trunca por si vienen valores ridículos. El backend también valida longitud.
      found[key] = value.slice(0, 255);
      any = true;
    }
  }
  return any ? found : null;
}

/** Lee la atribución guardada. Si expiró, la borra y retorna null. */
export function getAttribution(): Attribution | null {
  if (typeof window === "undefined") return null;
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw) as Attribution;
    if (!parsed.captured_at || Date.now() - parsed.captured_at > TTL_MS) {
      window.localStorage.removeItem(STORAGE_KEY);
      return null;
    }
    return parsed;
  } catch {
    return null;
  }
}

/**
 * Captura UTMs/gclid del URL actual si están presentes y los persiste.
 * Llama a esta función al montar el layout raíz.
 *
 * Idempotente y barata: si no hay UTMs nuevos, no toca nada.
 */
export function captureAttributionFromUrl(): void {
  if (typeof window === "undefined") return;
  const fresh = readUtmFromQuery(window.location.search);
  if (!fresh) return;
  const next: Attribution = {
    ...fresh,
    landing_page: window.location.pathname + window.location.search,
    captured_at: Date.now(),
  };
  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
  } catch {
    // localStorage puede fallar en private mode. Falla silenciosa — no bloqueamos UX.
  }
}

/**
 * Devuelve los campos de atribución listos para enviar al backend (sin
 * `captured_at`, que es interno). Filtra nulls/undefined.
 */
export function getAttributionPayload(): Record<string, string> {
  const attr = getAttribution();
  if (!attr) return {};
  const out: Record<string, string> = {};
  for (const key of ATTRIBUTION_KEYS) {
    const value = attr[key];
    if (value) out[key] = value;
  }
  if (attr.landing_page) out.landing_page = attr.landing_page;
  return out;
}

/**
 * Etiqueta legible para humanos del origen del cliente. Útil para añadir al
 * mensaje de WhatsApp o mostrar en admin.
 *
 * Formato: "google / cpc / filtros-komatsu" o "(orgánico/directo)" si no hay
 * atribución.
 */
export function formatAttributionTag(attr: Attribution | null = getAttribution()): string {
  if (!attr) return "(orgánico/directo)";
  const parts = [attr.utm_source, attr.utm_medium, attr.utm_campaign]
    .filter((p): p is string => Boolean(p && p.trim()));
  if (parts.length === 0 && attr.gclid) return `google-ads (gclid)`;
  if (parts.length === 0) return "(orgánico/directo)";
  return parts.join(" / ");
}
