import type { Metadata } from "next";
import { headers } from "next/headers";
import Link from "next/link";
import { HelpCircle, MessageCircle } from "lucide-react";
import { getActiveFaqs, groupByCategory } from "@/lib/faq";
import { getSiteSettings, whatsappLink } from "@/lib/siteSettings";
import FaqAccordion from "@/components/faq/FaqAccordion";

export const metadata: Metadata = {
  title: "Preguntas frecuentes",
  description: "Respuestas a las preguntas más comunes sobre pedidos, envíos, pagos y garantía de repuestos para excavadoras hidráulicas.",
};

// JSON-LD FAQPage para que Google muestre rich snippets
function buildJsonLd(items: { question: string; answer: string }[]) {
  return {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: items.map((it) => ({
      "@type": "Question",
      name: it.question,
      acceptedAnswer: {
        "@type": "Answer",
        // Strip HTML tags para JSON-LD (Google los quiere como texto plano)
        text: it.answer.replace(/<[^>]*>/g, ""),
      },
    })),
  };
}

export default async function FaqPage() {
  const [items, settings, headersList] = await Promise.all([
    getActiveFaqs(),
    getSiteSettings(),
    headers(),
  ]);
  const nonce = headersList.get("x-nonce") ?? "";
  const groups = groupByCategory(items);
  const wppHref = whatsappLink(settings.whatsapp_number);

  return (
    <>
      {/* JSON-LD para Google */}
      {items.length > 0 && (
        <script
          type="application/ld+json"
          nonce={nonce}
          dangerouslySetInnerHTML={{ __html: JSON.stringify(buildJsonLd(items)) }}
        />
      )}

      <div className="container mx-auto py-12">
        <div className="max-w-3xl mx-auto">
          {/* Header */}
          <div className="text-center mb-10">
            <div className="w-14 h-14 rounded-full bg-primary/10 mx-auto flex items-center justify-center mb-4">
              <HelpCircle size={28} className="text-primary" />
            </div>
            <h1 className="section-title">Preguntas frecuentes</h1>
            <p className="section-subtitle">
              Resolvemos las dudas más comunes sobre pedidos, envíos y garantía
            </p>
          </div>

          {/* Estado vacío */}
          {items.length === 0 ? (
            <div className="bg-bg-light rounded-xl p-10 text-center">
              <p className="text-gray-mid font-sans">
                Aún no hay preguntas frecuentes publicadas.
              </p>
            </div>
          ) : (
            <div className="space-y-10">
              {groups.map((group) => (
                <section key={group.category || "general"}>
                  {group.category && (
                    <h2 className="font-heading text-base font-semibold uppercase tracking-wide text-primary mb-4">
                      {group.category}
                    </h2>
                  )}
                  <FaqAccordion items={group.items} />
                </section>
              ))}
            </div>
          )}

          {/* CTA final — si no encuentra respuesta, contactar */}
          <div className="mt-12 bg-dark-2 rounded-xl p-8 text-center text-white">
            <h3 className="font-heading text-xl font-semibold uppercase mb-2">
              ¿No encontraste tu respuesta?
            </h3>
            <p className="text-gray-300 text-sm mb-6">
              Escríbenos por WhatsApp y un asesor te responde directamente.
            </p>
            <div className="flex flex-col sm:flex-row gap-3 justify-center">
              <a
                href={wppHref}
                target="_blank"
                rel="noopener noreferrer"
                className="bg-green-600 hover:bg-green-700 text-white font-semibold rounded px-6 py-3 inline-flex items-center justify-center gap-2 transition-colors"
              >
                <MessageCircle size={18} />
                Escribir por WhatsApp
              </a>
              <Link
                href="/contacto"
                className="bg-transparent border border-gray-600 text-gray-300 hover:bg-white/10 hover:text-white font-semibold rounded px-6 py-3 inline-flex items-center justify-center gap-2 transition-colors"
              >
                Formulario de contacto
              </Link>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
