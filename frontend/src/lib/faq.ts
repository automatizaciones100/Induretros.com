import { cache } from "react";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

export interface FaqItem {
  id: number;
  question: string;
  answer: string;
  category?: string | null;
  position: number;
  active: boolean;
}

export const getActiveFaqs = cache(async (): Promise<FaqItem[]> => {
  try {
    const res = await fetch(`${API_URL}/api/faq`, {
      next: { revalidate: 120 },
    });
    if (!res.ok) return [];
    return await res.json();
  } catch {
    return [];
  }
});

/** Agrupa los FAQ por categoría preservando el orden original. */
export function groupByCategory(items: FaqItem[]): Array<{ category: string | null; items: FaqItem[] }> {
  const groups = new Map<string | null, FaqItem[]>();
  for (const item of items) {
    const key = item.category || null;
    if (!groups.has(key)) groups.set(key, []);
    groups.get(key)!.push(item);
  }
  return [...groups.entries()].map(([category, items]) => ({ category, items }));
}
