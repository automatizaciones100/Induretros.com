"use client";

import { usePathname } from "next/navigation";
import WhatsAppButton from "@/components/ui/WhatsAppButton";

/**
 * Renderiza el chrome público (Header arriba, Footer abajo, botón flotante de
 * WhatsApp) condicionalmente según la ruta actual:
 * - Oculto en /admin/* y /login (esas rutas tienen su propio shell)
 * - WhatsApp flotante también oculto en /checkout y /orden/* (tienen sus
 *   propios CTAs hacia WhatsApp con el detalle del pedido)
 *
 * Header y Footer son server components, se reciben como ReactNode ya pre-renderizados
 * desde el layout raíz. Esta capa solo decide visibilidad.
 */
const HIDDEN_PREFIXES = ["/admin", "/login"];
const NO_FLOATING_WPP_PREFIXES = [...HIDDEN_PREFIXES, "/checkout", "/orden"];

interface Props {
  header: React.ReactNode;
  footer: React.ReactNode;
  whatsappNumber?: string | null;
  children: React.ReactNode;
}

export function ConditionalChrome({ header, footer, whatsappNumber, children }: Props) {
  const pathname = usePathname();
  const hideAll = HIDDEN_PREFIXES.some((p) => pathname.startsWith(p));
  const hideWpp = NO_FLOATING_WPP_PREFIXES.some((p) => pathname.startsWith(p));

  if (hideAll) {
    // En admin / login el contenido se rendirea solo, sin chrome público.
    return <main className="flex-1">{children}</main>;
  }

  return (
    <>
      {header}
      <main className="flex-1">{children}</main>
      {footer}
      {!hideWpp && whatsappNumber && <WhatsAppButton number={whatsappNumber} />}
    </>
  );
}
