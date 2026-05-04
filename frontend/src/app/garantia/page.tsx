import type { Metadata } from "next";
import { ShieldCheck } from "lucide-react";
import LegalPageView from "@/components/legal/LegalPageView";

export const metadata: Metadata = {
  title: "Política de garantía",
  description:
    "Cobertura, exclusiones y proceso de reclamación de la garantía de repuestos para excavadoras hidráulicas Induretros.",
};

export default function GarantiaPage() {
  return (
    <LegalPageView
      slug="garantia"
      icon={ShieldCheck}
      fallbackTitle="Política de garantía"
      subtitle="Cobertura, exclusiones y cómo reclamar la garantía de tus repuestos"
      emptyMessage="Aún no hay política de garantía publicada."
      cta={{
        title: "¿Tienes un caso de garantía?",
        description: "Escríbenos por WhatsApp con la factura y fotos del repuesto.",
        primaryLabel: "Reclamar por WhatsApp",
      }}
    />
  );
}
