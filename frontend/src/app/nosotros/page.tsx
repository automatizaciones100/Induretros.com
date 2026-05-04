import type { Metadata } from "next";
import { Building2 } from "lucide-react";
import LegalPageView from "@/components/legal/LegalPageView";

export const metadata: Metadata = {
  title: "Sobre Induretros — Repuestos para maquinaria pesada",
  description:
    "Conoce a Induretros, importadores directos de repuestos para excavadoras hidráulicas y maquinaria pesada con más de 9 años de experiencia en Colombia.",
};

export default function NosotrosPage() {
  return (
    <LegalPageView
      slug="nosotros"
      icon={Building2}
      fallbackTitle="Sobre Induretros"
      subtitle="Importadores directos de repuestos para excavadoras hidráulicas en Colombia"
      emptyMessage="Aún no hay contenido publicado en esta página."
      cta={{
        title: "¿Listo para cotizar tu repuesto?",
        description: "Escríbenos por WhatsApp con la referencia o descripción de la pieza.",
        primaryLabel: "Cotizar por WhatsApp",
      }}
    />
  );
}
