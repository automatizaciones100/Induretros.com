import type { Metadata } from "next";
import { headers } from "next/headers";
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

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  // El middleware inyecta el nonce en x-nonce para que Next.js lo aplique
  // a sus propios scripts de hidratación (eliminando la necesidad de 'unsafe-inline')
  const nonce = (await headers()).get("x-nonce") ?? "";

  return (
    <html lang="es" suppressHydrationWarning>
      <head>
        {/* Next.js usa este meta-nonce para firmar sus scripts inline de hidratación */}
        {nonce && <meta property="csp-nonce" content={nonce} />}
        {/* Cloudflare Turnstile — se carga con el nonce para pasar CSP */}
        <script
          src="https://challenges.cloudflare.com/turnstile/v0/api.js"
          async
          defer
          {...(nonce ? { nonce } : {})}
        />
      </head>
      <body className="min-h-screen flex flex-col">
        <Header />
        <main className="flex-1">{children}</main>
        <Footer />
        <WhatsAppButton />
      </body>
    </html>
  );
}
