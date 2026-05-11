"use client";

import { useEffect } from "react";
import { captureAttributionFromUrl } from "@/lib/attribution";

/**
 * Captura UTMs/gclid del URL actual y los persiste en localStorage al cargar
 * cualquier página del sitio. Si el cliente vuelve más tarde por otra
 * campaña, sobrescribe (last-touch). Si vuelve directo, conserva la atribución
 * previa hasta que expire (30 días).
 *
 * Se monta una sola vez en el layout raíz.
 */
export default function AttributionTracker() {
  useEffect(() => {
    captureAttributionFromUrl();
  }, []);
  return null;
}
