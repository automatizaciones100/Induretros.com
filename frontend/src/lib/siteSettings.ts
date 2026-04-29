/**
 * Helper para leer la configuración pública del sitio (contacto, redes, etc.)
 * desde el endpoint público del backend.
 *
 * Usado por Server Components (Header, Footer, TopBar, layout, contacto).
 * React.cache deduplica las llamadas dentro del mismo request.
 *
 * Para Client Components (WhatsAppButton, AddToCart), recibir los settings
 * como props desde un Server Component padre.
 */
import { cache } from "react";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

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
  // Hero del home
  hero_label?: string | null;
  hero_title?: string | null;
  hero_subtitle?: string | null;
  hero_cta_text?: string | null;
  hero_cta_url?: string | null;
  hero_cta2_text?: string | null;
  hero_cta2_url?: string | null;
  hero_image_url?: string | null;
}

const FALLBACK: SiteSettings = {
  site_title: "Induretros",
  organization_phone: "300 719 2973",
  contact_email: "ventas@induretros.com",
  contact_address: "Centro Empresarial Promisión, Medellín, Colombia",
  contact_business_hours: "Lunes a Viernes: 7:00 am - 5:00 pm",
  whatsapp_number: "573007192973",
};

/**
 * Fetch cacheado en el server. Si el backend no responde, retorna fallback
 * con los datos mínimos hardcoded para que el sitio nunca quede sin datos.
 */
export const getSiteSettings = cache(async (): Promise<SiteSettings> => {
  try {
    const res = await fetch(`${API_URL}/api/admin/site-settings/public`, {
      next: { revalidate: 60 },  // ISR: cachea 60s, refresca después
    });
    if (!res.ok) return FALLBACK;
    return await res.json();
  } catch {
    return FALLBACK;
  }
});

/**
 * Helpers derivados — formatean los datos para uso directo en links.
 */
export function whatsappLink(number?: string | null, message?: string): string {
  const n = (number || FALLBACK.whatsapp_number || "").replace(/\D/g, "");
  const text = message ? `?text=${encodeURIComponent(message)}` : "";
  return `https://wa.me/${n}${text}`;
}

export function telLink(phone?: string | null): string {
  const n = (phone || "").replace(/\s/g, "");
  return `tel:+${n.replace(/\D/g, "")}`;
}

export function mailtoLink(email?: string | null): string {
  return `mailto:${email || FALLBACK.contact_email}`;
}
