"use client";

import { usePathname } from "next/navigation";
import Header from "./Header";
import Footer from "./Footer";
import WhatsAppButton from "@/components/ui/WhatsAppButton";

/**
 * Wrapper que oculta header, footer y botón de WhatsApp en rutas privadas
 * (panel admin, login). Esas rutas tienen su propio shell.
 */
const HIDDEN_PREFIXES = ["/admin", "/login"];

export function PublicHeader() {
  const pathname = usePathname();
  if (HIDDEN_PREFIXES.some((p) => pathname.startsWith(p))) return null;
  return <Header />;
}

export function PublicFooter() {
  const pathname = usePathname();
  if (HIDDEN_PREFIXES.some((p) => pathname.startsWith(p))) return null;
  return <Footer />;
}

export function PublicWhatsAppButton() {
  const pathname = usePathname();
  if (HIDDEN_PREFIXES.some((p) => pathname.startsWith(p))) return null;
  return <WhatsAppButton />;
}
