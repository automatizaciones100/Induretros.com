import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Mi cuenta",
  description: "Gestiona tu perfil y revisa el historial de tus pedidos en Induretros.",
  robots: { index: false, follow: false },
};

export default function MiCuentaLayout({ children }: { children: React.ReactNode }) {
  return <>{children}</>;
}
