/**
 * API client para la configuración global del sitio (singleton).
 * GET y PUT contra /api/admin/site-settings.
 */
import { authFetch } from "@/lib/authFetch";
import type { SiteSettings } from "./types";

interface ValidationDetail { msg: string; loc: string[] }

function extractErrorMessage(body: unknown, fallback: string): string {
  if (typeof body === "object" && body !== null && "detail" in body) {
    const detail = (body as { detail: unknown }).detail;
    if (Array.isArray(detail)) {
      return (detail as ValidationDetail[])
        .map((e) => `${e.loc.slice(-1)[0]}: ${e.msg}`)
        .join(" · ");
    }
    if (typeof detail === "string") return detail;
  }
  return fallback;
}

export async function loadSiteSettings(): Promise<SiteSettings> {
  const res = await authFetch("/api/admin/site-settings");
  if (!res.ok) throw new Error(`Error ${res.status}`);
  return res.json();
}

export async function saveSiteSettings(payload: Partial<SiteSettings>): Promise<SiteSettings> {
  const res = await authFetch("/api/admin/site-settings", {
    method: "PUT",
    body: JSON.stringify(payload),
  });
  if (!res.ok) {
    const body = await res.json().catch(() => ({}));
    throw new Error(extractErrorMessage(body, `Error ${res.status}`));
  }
  return res.json();
}
