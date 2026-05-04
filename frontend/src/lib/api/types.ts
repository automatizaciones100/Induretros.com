/**
 * Tipos compartidos del dominio admin.
 * Mantén estas interfaces en sync con los DTOs del backend
 * (backend/app/presentation/routers/*.py).
 */

export interface Testimonial {
  id: number;
  client_name: string;
  client_company?: string | null;
  comment: string;
  rating: number;
  photo_url?: string | null;
  position: number;
  active: boolean;
}

export interface FaqItem {
  id: number;
  question: string;
  answer: string;
  category?: string | null;
  position: number;
  active: boolean;
}

export interface WhyUsItem {
  id: number;
  position: number;
  title: string;
  description: string;
  icon?: string | null;
  active: boolean;
}

export interface HomeStat {
  id: number;
  position: number;
  value: string;
  label: string;
  icon?: string | null;
  active: boolean;
}

export interface Announcement {
  id: number;
  text: string;
  link_url?: string | null;
  link_text?: string | null;
  theme: "info" | "promo" | "warning" | "success" | "alert" | "dark";
  active: boolean;
  dismissible: boolean;
  expires_at: string | null;
  priority: number;
}

/** Forma genérica de cualquier entidad CRUD admin: id numérico. `active` es opcional pero
 *  estandariza el comportamiento del toggle. */
export interface AdminEntity {
  id: number;
  active?: boolean;
}
