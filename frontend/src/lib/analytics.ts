"use client";

/**
 * Cliente de analytics in-house — anónimo y privacy-friendly.
 *
 * Genera un session_id aleatorio en localStorage la primera vez. No envía IP
 * ni user-agent al servidor; solo el evento + session_id + path.
 *
 * Uso:
 *   trackPageView("/repuestos");
 *   trackProductView(product.id, product.slug);
 *   trackClick("whatsapp_floating");
 *   trackAddToCart(product.id);
 */
const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";
const SESSION_KEY = "induretros-analytics-sid";

function getSessionId(): string {
  if (typeof window === "undefined") return "";
  let id = localStorage.getItem(SESSION_KEY);
  if (!id) {
    id =
      (typeof crypto !== "undefined" && crypto.randomUUID
        ? crypto.randomUUID()
        : Math.random().toString(36).slice(2) + Date.now().toString(36)) as string;
    localStorage.setItem(SESSION_KEY, id);
  }
  return id;
}

interface TrackPayload {
  event_type: "pageview" | "product_view" | "click" | "add_to_cart";
  path?: string;
  target?: string;
  product_id?: number;
}

function track(payload: TrackPayload): void {
  if (typeof window === "undefined") return;
  const session_id = getSessionId();
  if (!session_id) return;

  // sendBeacon es ideal para analytics — no bloquea la navegación
  const body = JSON.stringify({ ...payload, session_id });
  const url = `${API_URL}/api/analytics/event`;

  if (navigator.sendBeacon) {
    const blob = new Blob([body], { type: "application/json" });
    navigator.sendBeacon(url, blob);
  } else {
    fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body,
      keepalive: true,
    }).catch(() => {});
  }
}

export function trackPageView(path: string): void {
  track({ event_type: "pageview", path });
}

export function trackProductView(productId: number, slug: string): void {
  track({ event_type: "product_view", product_id: productId, path: `/producto/${slug}` });
}

export function trackClick(target: string, path?: string): void {
  track({ event_type: "click", target, path });
}

export function trackAddToCart(productId: number): void {
  track({ event_type: "add_to_cart", product_id: productId });
}
