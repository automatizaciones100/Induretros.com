import type { Metadata } from "next";
import { headers } from "next/headers";
import "./globals.css";
import { ConditionalChrome } from "@/components/layout/PublicChrome";
import Header from "@/components/layout/Header";
import Footer from "@/components/layout/Footer";
import AnnouncementBar from "@/components/layout/AnnouncementBar";
import PageViewTracker from "@/components/analytics/PageViewTracker";
import { getSiteSettings } from "@/lib/siteSettings";
import { getActiveAnnouncements } from "@/lib/announcements";

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
  const [settings, announcements] = await Promise.all([getSiteSettings(), getActiveAnnouncements()]);

  // Pre-renderizamos los server components y los pasamos a ConditionalChrome
  // (cliente) como ReactNode para que decida visibilidad por ruta.
  const announcementNode = announcements.length > 0
    ? <AnnouncementBar announcements={announcements} />
    : null;
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
          announcement={announcementNode}
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
