import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Confirmar pedido",
  description: "Completa tus datos para finalizar tu pedido en Induretros.",
  robots: { index: false, follow: false },
};

export default function CheckoutLayout({ children }: { children: React.ReactNode }) {
  return <>{children}</>;
}
