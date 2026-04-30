import { cache } from "react";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

export type AnnouncementTheme = "info" | "promo" | "warning" | "success" | "alert" | "dark";

export interface Announcement {
  id: number;
  text: string;
  link_url?: string | null;
  link_text?: string | null;
  theme: AnnouncementTheme;
  active: boolean;
  dismissible: boolean;
  expires_at?: string | null;
  priority: number;
}

/** Fetch SSR-cacheado de los anuncios activos no expirados. */
export const getActiveAnnouncements = cache(async (): Promise<Announcement[]> => {
  try {
    const res = await fetch(`${API_URL}/api/announcements`, {
      next: { revalidate: 30 },
    });
    if (!res.ok) return [];
    return await res.json();
  } catch {
    return [];
  }
});
