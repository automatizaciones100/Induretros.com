import type { Metadata } from "next";
import Link from "next/link";
import { ShieldCheck, MessageCircle } from "lucide-react";
import { getLegalPage } from "@/lib/legalPages";
import { getSiteSettings, whatsappLink } from "@/lib/siteSettings";

export const metadata: Metadata = {
  title: "Política de garantía",
  description: "Cobertura, exclusiones y proceso de reclamación de la garantía de repuestos para excavadoras hidráulicas Induretros.",
};

export default async function GarantiaPage() {
  const [page, settings] = await Promise.all([
    getLegalPage("garantia"),
    getSiteSettings(),
  ]);
  const wppHref = whatsappLink(settings.whatsapp_number);

  return (
    <div className="container mx-auto py-12">
      <div className="max-w-3xl mx-auto">
        <div className="text-center mb-10">
          <div className="w-14 h-14 rounded-full bg-primary/10 mx-auto flex items-center justify-center mb-4">
            <ShieldCheck size={28} className="text-primary" />
          </div>
          <h1 className="section-title">{page?.title || "Política de garantía"}</h1>
          <p className="section-subtitle">
            Cobertura, exclusiones y cómo reclamar la garantía de tus repuestos
          </p>
        </div>

        {page ? (
          <article
            className="legal-content bg-white border border-gray-100 rounded-xl p-8 md:p-10"
            dangerouslySetInnerHTML={{ __html: page.content }}
          />
        ) : (
          <div className="bg-bg-light rounded-xl p-10 text-center">
            <p className="text-gray-mid font-sans">
              Aún no hay política de garantía publicada.
            </p>
          </div>
        )}

        {page?.updated_at && (
          <p className="text-xs text-gray-light font-sans text-center mt-4">
            Última actualización: {new Date(page.updated_at).toLocaleDateString("es-CO", { year: "numeric", month: "long", day: "numeric" })}
          </p>
        )}

        {/* CTA WhatsApp */}
        <div className="mt-12 bg-dark-2 rounded-xl p-8 text-center text-white">
          <h3 className="font-heading text-xl font-semibold uppercase mb-2">
            ¿Tienes un caso de garantía?
          </h3>
          <p className="text-gray-300 text-sm mb-6">
            Escríbenos por WhatsApp con la factura y fotos del repuesto.
          </p>
          <div className="flex flex-col sm:flex-row gap-3 justify-center">
            <a
              href={wppHref}
              target="_blank"
              rel="noopener noreferrer"
              className="bg-green-600 hover:bg-green-700 text-white font-semibold rounded px-6 py-3 inline-flex items-center justify-center gap-2 transition-colors"
            >
              <MessageCircle size={18} />
              Reclamar por WhatsApp
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
  );
}
