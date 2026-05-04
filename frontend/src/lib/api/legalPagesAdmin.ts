/**
 * API client del lado admin para páginas legales (legal_pages).
 * El listado público read-only vive en lib/legalPages.ts; este módulo
 * cubre las operaciones autenticadas que el admin necesita.
 */
import { authFetch } from "@/lib/authFetch";
import type { LegalPage } from "./types";

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

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

/** Lectura pública (sin auth) — usada por el editor admin para precargar
 *  el contenido. La PUT/POST sí requieren auth. */
export async function getLegalPageBySlug(slug: string): Promise<LegalPage | null> {
  const res = await fetch(`${API_URL}/api/legal/${encodeURIComponent(slug)}`);
  if (res.status === 404) return null;
  if (!res.ok) throw new Error(`Error ${res.status}`);
  return res.json();
}

export async function listLegalPagesAdmin(): Promise<LegalPage[]> {
  const res = await authFetch("/api/legal/admin/all");
  if (!res.ok) throw new Error(`Error ${res.status}`);
  return res.json();
}

export async function createLegalPage(payload: {
  slug: string;
  title: string;
  content: string;
}): Promise<LegalPage> {
  const res = await authFetch("/api/legal", {
    method: "POST",
    body: JSON.stringify(payload),
  });
  if (!res.ok) {
    const body = await res.json().catch(() => ({}));
    throw new Error(extractErrorMessage(body, `Error ${res.status}`));
  }
  return res.json();
}

export async function updateLegalPage(
  slug: string,
  payload: { title: string; content: string },
): Promise<LegalPage> {
  const res = await authFetch(`/api/legal/${encodeURIComponent(slug)}`, {
    method: "PUT",
    body: JSON.stringify(payload),
  });
  if (!res.ok) {
    const body = await res.json().catch(() => ({}));
    throw new Error(extractErrorMessage(body, `Error ${res.status}`));
  }
  return res.json();
}

export async function deleteLegalPage(slug: string): Promise<void> {
  const res = await authFetch(`/api/legal/${encodeURIComponent(slug)}`, {
    method: "DELETE",
  });
  if (!res.ok && res.status !== 204) {
    const body = await res.json().catch(() => ({}));
    throw new Error(extractErrorMessage(body, `Error ${res.status}`));
  }
}
