import { cache } from "react";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

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

export const getActiveTestimonials = cache(async (): Promise<Testimonial[]> => {
  try {
    const res = await fetch(`${API_URL}/api/testimonials`, {
      next: { revalidate: 120 },
    });
    if (!res.ok) return [];
    return await res.json();
  } catch {
    return [];
  }
});

/** Devuelve las iniciales del nombre para el avatar fallback. */
export function initialsFromName(name: string): string {
  const parts = name.trim().split(/\s+/);
  if (parts.length === 0) return "?";
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}
