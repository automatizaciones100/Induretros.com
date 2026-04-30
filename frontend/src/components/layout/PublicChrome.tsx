"use client";

import { usePathname } from "next/navigation";
import WhatsAppButton from "@/components/ui/WhatsAppButton";

/**
 * Renderiza el chrome público (banner anuncio + Header + main + Footer +
 * botón flotante de WhatsApp) condicionalmente según la ruta:
 * - Oculto en /admin/* y /login (esas rutas tienen su propio shell)
 * - WhatsApp flotante también oculto en /checkout y /orden/*
 *
 * Header, Footer y AnnouncementBar se reciben pre-renderizados desde el
 * layout raíz como ReactNode (server-side fetch + render).
 */
const HIDDEN_PREFIXES = ["/admin", "/login"];
const NO_FLOATING_WPP_PREFIXES = [...HIDDEN_PREFIXES, "/checkout", "/orden"];

interface Props {
  announcement?: React.ReactNode;
  header: React.ReactNode;
  footer: React.ReactNode;
  whatsappNumber?: string | null;
  children: React.ReactNode;
}

export function ConditionalChrome({ announcement, header, footer, whatsappNumber, children }: Props) {
  const pathname = usePathname();
  const hideAll = HIDDEN_PREFIXES.some((p) => pathname.startsWith(p));
  const hideWpp = NO_FLOATING_WPP_PREFIXES.some((p) => pathname.startsWith(p));

  if (hideAll) {
    return <main className="flex-1">{children}</main>;
  }

  return (
    <>
      {announcement}
      {header}
      <main className="flex-1">{children}</main>
      {footer}
      {!hideWpp && whatsappNumber && <WhatsAppButton number={whatsappNumber} />}
    </>
  );
}
