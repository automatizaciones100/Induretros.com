import { cache } from "react";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

export interface LegalPage {
  id: number;
  slug: string;
  title: string;
  content: string;
  updated_at: string | null;
}

export const getLegalPage = cache(async (slug: string): Promise<LegalPage | null> => {
  try {
    const res = await fetch(`${API_URL}/api/legal/${encodeURIComponent(slug)}`, {
      next: { revalidate: 300 },
    });
    if (!res.ok) return null;
    return await res.json();
  } catch {
    return null;
  }
});
