/**
 * Factoría de clientes API para los CRUD del panel admin.
 *
 * Cada entidad expone la misma forma:
 *   listAll()           → GET    {endpoint}/admin/all
 *   create(payload)     → POST   {endpoint}
 *   update(id, payload) → PUT    {endpoint}/{id}
 *   remove(id)          → DELETE {endpoint}/{id}
 *
 * Centraliza también el manejo de errores Pydantic (detail puede ser string
 * o array de errores de validación).
 */
import { authFetch } from "@/lib/authFetch";
import type {
  Testimonial,
  FaqItem,
  WhyUsItem,
  HomeStat,
  Announcement,
  Category,
} from "./types";

interface ValidationDetail {
  msg: string;
  loc: string[];
}

/** Convierte el cuerpo de error de FastAPI/Pydantic en un mensaje legible. */
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

async function readJsonOrEmpty(res: Response): Promise<unknown> {
  try {
    return await res.json();
  } catch {
    return {};
  }
}

export interface AdminCrudApi<T extends { id: number }> {
  listAll: () => Promise<T[]>;
  create: (payload: object) => Promise<T>;
  update: (id: number, payload: object) => Promise<T>;
  remove: (id: number) => Promise<void>;
}

/** Construye un cliente CRUD para un endpoint que cumple la convención
 *  `{endpoint}` + `/admin/all` + `/{id}` para PUT/DELETE. */
export function makeAdminCrudApi<T extends { id: number }>(
  endpoint: string,
): AdminCrudApi<T> {
  return {
    async listAll() {
      const res = await authFetch(`${endpoint}/admin/all`);
      if (!res.ok) throw new Error(`Error ${res.status}`);
      return res.json();
    },

    async create(payload) {
      const res = await authFetch(endpoint, {
        method: "POST",
        body: JSON.stringify(payload),
      });
      if (!res.ok) {
        const body = await readJsonOrEmpty(res);
        throw new Error(extractErrorMessage(body, `Error ${res.status}`));
      }
      return res.json();
    },

    async update(id, payload) {
      const res = await authFetch(`${endpoint}/${id}`, {
        method: "PUT",
        body: JSON.stringify(payload),
      });
      if (!res.ok) {
        const body = await readJsonOrEmpty(res);
        throw new Error(extractErrorMessage(body, `Error ${res.status}`));
      }
      return res.json();
    },

    async remove(id) {
      const res = await authFetch(`${endpoint}/${id}`, { method: "DELETE" });
      if (!res.ok && res.status !== 204) {
        const body = await readJsonOrEmpty(res);
        throw new Error(extractErrorMessage(body, `Error ${res.status}`));
      }
    },
  };
}

// ─────────── Instancias preconfiguradas ───────────

export const testimonialsApi = makeAdminCrudApi<Testimonial>("/api/testimonials");
export const faqApi = makeAdminCrudApi<FaqItem>("/api/faq");
export const whyUsApi = makeAdminCrudApi<WhyUsItem>("/api/why-us");
export const homeStatsApi = makeAdminCrudApi<HomeStat>("/api/home-stats");
export const announcementsApi = makeAdminCrudApi<Announcement>("/api/announcements");
export const categoriesApi = makeAdminCrudApi<Category>("/api/products/categories");
