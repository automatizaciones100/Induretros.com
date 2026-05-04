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

export interface Category {
  id: number;
  name: string;
  slug: string;
  description?: string | null;
  image_url?: string | null;
  parent_id?: number | null;
}

export interface SiteSettings {
  site_title?: string | null;
  title_template?: string | null;
  default_description?: string | null;
  default_keywords?: string | null;
  default_og_image?: string | null;
  twitter_handle?: string | null;
  organization_name?: string | null;
  organization_phone?: string | null;
  contact_email?: string | null;
  contact_address?: string | null;
  contact_business_hours?: string | null;
  whatsapp_number?: string | null;
  facebook_url?: string | null;
  instagram_url?: string | null;
  youtube_url?: string | null;
  tiktok_url?: string | null;
  linkedin_url?: string | null;
  hero_label?: string | null;
  hero_title?: string | null;
  hero_subtitle?: string | null;
  hero_cta_text?: string | null;
  hero_cta_url?: string | null;
  hero_cta2_text?: string | null;
  hero_cta2_url?: string | null;
  hero_image_url?: string | null;
}

export interface LegalPage {
  id: number;
  slug: string;
  title: string;
  content: string;
  updated_at: string | null;
}

export interface UserMe {
  id: number;
  email: string;
  name: string;
  phone: string | null;
  address: string | null;
  is_admin: boolean;
  created_at: string | null;
}

export interface OrderItem {
  id: number;
  product_id: number;
  quantity: number;
  unit_price: number;
  subtotal: number;
}

export interface OrderSummary {
  id: number;
  status: string;
  total: number;
  customer_name: string;
  items: OrderItem[];
  created_at: string;
}

export interface OrderDetail extends OrderSummary {
  customer_email: string;
  customer_phone?: string | null;
  shipping_address?: string | null;
  notes?: string | null;
}

export interface OrdersPage {
  items: OrderSummary[];
  total: number;
  page: number;
  per_page: number;
  pages: number;
}

export type ChangeAction = "create" | "update" | "delete" | "restore";

export interface ChangeLogEntry {
  id: number;
  entity_type: string;
  entity_id: string;
  entity_label: string;
  action: ChangeAction;
  before: Record<string, unknown> | null;
  after: Record<string, unknown> | null;
  user_id: number | null;
  user_email: string | null;
  ip: string | null;
  restored: boolean;
  restored_at: string | null;
  created_at: string | null;
  restorable: boolean;
}

export interface ChangeLogPage {
  total: number;
  limit: number;
  offset: number;
  items: ChangeLogEntry[];
}

/** Forma genérica de cualquier entidad CRUD admin: id numérico. `active` es opcional pero
 *  estandariza el comportamiento del toggle. */
export interface AdminEntity {
  id: number;
  active?: boolean;
}
