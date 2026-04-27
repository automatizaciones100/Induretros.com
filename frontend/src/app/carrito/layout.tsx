import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Carrito de compras",
  description: "Revisa los repuestos seleccionados antes de continuar al pago.",
  robots: { index: false, follow: false }, // contenido personal, no indexar
};

export default function CarritoLayout({ children }: { children: React.ReactNode }) {
  return <>{children}</>;
}
