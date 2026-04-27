import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Contáctanos",
  description: "Comunícate con Induretros para cotizar repuestos para excavadoras hidráulicas. Medellín, Colombia.",
};

export default function ContactoLayout({ children }: { children: React.ReactNode }) {
  return <>{children}</>;
}
