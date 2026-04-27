"use client";

import { useEffect } from "react";
import { trackProductView } from "@/lib/analytics";

/**
 * Trackea un product_view cuando se monta. Se incluye dentro de la página
 * de detalle de producto (server component) — solo este sub-árbol es cliente.
 */
export default function ProductViewTracker({ productId, slug }: { productId: number; slug: string }) {
  useEffect(() => {
    trackProductView(productId, slug);
  }, [productId, slug]);

  return null;
}
