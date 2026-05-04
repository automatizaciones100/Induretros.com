import type { Metadata } from "next";
import { FileText } from "lucide-react";
import LegalPageView from "@/components/legal/LegalPageView";

export const metadata: Metadata = {
  title: "Términos y condiciones",
  description:
    "Términos y condiciones de uso del sitio induretros.com y de la compra de repuestos para excavadoras hidráulicas.",
};

export default function TerminosPage() {
  return (
    <LegalPageView
      slug="terminos"
      icon={FileText}
      fallbackTitle="Términos y condiciones"
      subtitle="Las reglas que aplican al usar nuestro sitio y al comprar nuestros repuestos"
      emptyMessage="Aún no hay términos y condiciones publicados."
    />
  );
}
