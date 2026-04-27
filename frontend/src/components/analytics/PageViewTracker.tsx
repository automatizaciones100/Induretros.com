"use client";

import { useEffect } from "react";
import { usePathname } from "next/navigation";
import { trackPageView } from "@/lib/analytics";

// No traquear el panel admin ni rutas internas
const EXCLUDED_PREFIXES = ["/admin", "/_next", "/api"];

/**
 * Registra un pageview cada vez que cambia el pathname.
 * Se monta una sola vez en el layout raíz.
 */
export default function PageViewTracker() {
  const pathname = usePathname();

  useEffect(() => {
    if (!pathname) return;
    if (EXCLUDED_PREFIXES.some((p) => pathname.startsWith(p))) return;
    trackPageView(pathname);
  }, [pathname]);

  return null;
}
