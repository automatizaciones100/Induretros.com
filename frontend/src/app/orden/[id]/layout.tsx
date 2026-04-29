import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Pedido confirmado",
  description: "Detalle de tu pedido en Induretros.",
  robots: { index: false, follow: false },
};

export default function OrdenLayout({ children }: { children: React.ReactNode }) {
  return <>{children}</>;
}
