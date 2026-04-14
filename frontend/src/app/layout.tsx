import type { Metadata } from "next";
import "./globals.css";
import Header from "@/components/layout/Header";
import Footer from "@/components/layout/Footer";
import WhatsAppButton from "@/components/ui/WhatsAppButton";

export const metadata: Metadata = {
  title: {
    default: "Induretros - Repuestos para Excavadoras Hidráulicas",
    template: "%s | Induretros",
  },
  description:
    "Importadores directos de repuestos para excavadoras hidráulicas. Más de 9 años de experiencia. Medellín, Colombia.",
  keywords: ["repuestos excavadoras", "hidráulica", "maquinaria pesada", "Colombia", "Medellín"],
  openGraph: {
    siteName: "Induretros",
    locale: "es_CO",
    type: "website",
  },
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="es">
      <body className="min-h-screen flex flex-col">
        <Header />
        <main className="flex-1">{children}</main>
        <Footer />
        <WhatsAppButton />
      </body>
    </html>
  );
}
