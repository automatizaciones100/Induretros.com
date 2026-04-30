import { cache } from "react";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

export interface HomeStat {
  id: number;
  position: number;
  value: string;
  label: string;
  icon?: string | null;
  active: boolean;
}

const FALLBACK: HomeStat[] = [
  { id: 0, position: 1, value: "+9", label: "Años de experiencia", icon: "Clock", active: true },
  { id: 0, position: 2, value: "+1200", label: "Referencias disponibles", icon: "Package", active: true },
  { id: 0, position: 3, value: "+500", label: "Clientes satisfechos", icon: "Users", active: true },
  { id: 0, position: 4, value: "100%", label: "Garantía de calidad", icon: "Award", active: true },
];

/** Fetch cacheado en SSR. Si el backend falla, retorna los 4 stats default. */
export const getHomeStats = cache(async (): Promise<HomeStat[]> => {
  try {
    const res = await fetch(`${API_URL}/api/home-stats`, {
      next: { revalidate: 60 },
    });
    if (!res.ok) return FALLBACK;
    const data = await res.json();
    return Array.isArray(data) && data.length > 0 ? data : FALLBACK;
  } catch {
    return FALLBACK;
  }
});
