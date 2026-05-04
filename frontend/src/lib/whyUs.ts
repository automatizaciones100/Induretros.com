import { cache } from "react";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

export interface WhyUsItem {
  id: number;
  title: string;
  description: string;
  icon?: string | null;
  position: number;
  active: boolean;
}

export const getActiveWhyUs = cache(async (): Promise<WhyUsItem[]> => {
  try {
    const res = await fetch(`${API_URL}/api/why-us`, {
      next: { revalidate: 120 },
    });
    if (!res.ok) return [];
    return await res.json();
  } catch {
    return [];
  }
});
