import type { Metadata } from "next";
import { headers } from "next/headers";
import "./globals.css";
import { ConditionalChrome } from "@/components/layout/PublicChrome";
import Header from "@/components/layout/Header";
import Footer from "@/components/layout/Footer";
import PageViewTracker from "@/components/analytics/PageViewTracker";
import { getSiteSettings } from "@/lib/siteSettings";

export async function generateMetadata(): Promise<Metadata> {
  const s = await getSiteSettings();
  const title = s.site_title || "Induretros - Repuestos para Excavadoras Hidráulicas";
  const template = s.title_template || "%s | Induretros";
  return {
    title: { default: title, template },
    description: s.default_description || "Importadores directos de repuestos para excavadoras hidráulicas. Más de 9 años de experiencia. Medellín, Colombia.",
    keywords: (s.default_keywords || "repuestos excavadoras, hidráulica, maquinaria pesada, Colombia, Medellín").split(",").map((k) => k.trim()),
    openGraph: {
      siteName: s.site_title || "Induretros",
      locale: "es_CO",
      type: "website",
      ...(s.default_og_image ? { images: [{ url: s.default_og_image }] } : {}),
    },
    ...(s.twitter_handle ? { twitter: { card: "summary_large_image", site: `@${s.twitter_handle}` } } : {}),
  };
}

export default async function RootLayout({ children }: { children: React.ReactNode }) {
  const nonce = (await headers()).get("x-nonce") ?? "";
  const settings = await getSiteSettings();

  // Header y Footer son async server components; los pre-renderizamos aquí
  // y se los pasamos a ConditionalChrome (cliente) como ReactNode.
  const headerNode = <Header />;
  const footerNode = <Footer />;

  return (
    <html lang="es" suppressHydrationWarning>
      <head>
        {nonce && <meta property="csp-nonce" content={nonce} />}
        <script
          src="https://challenges.cloudflare.com/turnstile/v0/api.js"
          async
          defer
          {...(nonce ? { nonce } : {})}
        />
      </head>
      <body className="min-h-screen flex flex-col">
        <PageViewTracker />
        <ConditionalChrome
          header={headerNode}
          footer={footerNode}
          whatsappNumber={settings.whatsapp_number}
        >
          {children}
        </ConditionalChrome>
      </body>
    </html>
  );
}
