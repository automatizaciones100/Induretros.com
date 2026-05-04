import { cache } from "react";
import type { LegalPage } from "./api/types";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

export type { LegalPage };

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
