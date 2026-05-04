import type { Metadata } from "next";
import { RefreshCcw } from "lucide-react";
import LegalPageView from "@/components/legal/LegalPageView";

export const metadata: Metadata = {
  title: "Política de devoluciones y cambios",
  description:
    "Cuándo aplica una devolución o cambio, cómo solicitarlo y plazos de reembolso de tu compra en Induretros.",
};

export default function DevolucionesPage() {
  return (
    <LegalPageView
      slug="devoluciones"
      icon={RefreshCcw}
      fallbackTitle="Política de devoluciones y cambios"
      subtitle="Cuándo aplica, cómo solicitarlo y los plazos para que recibas tu reembolso"
      emptyMessage="Aún no hay política de devoluciones publicada."
      cta={{
        title: "¿Necesitas devolver o cambiar un producto?",
        description: "Escríbenos por WhatsApp con la factura y fotos del repuesto.",
        primaryLabel: "Solicitar por WhatsApp",
      }}
    />
  );
}
