import type { Metadata } from "next";
import Link from "next/link";
import { Search, Home, MessageCircle, ArrowLeft } from "lucide-react";
import { getSiteSettings, whatsappLink } from "@/lib/siteSettings";

export const metadata: Metadata = {
  title: "Página no encontrada (404)",
  description: "La página que buscas no existe o fue movida.",
  robots: { index: false, follow: false },
};

export default async function NotFound() {
  const settings = await getSiteSettings();
  const wpp = whatsappLink(settings.whatsapp_number);

  return (
    <div className="container mx-auto py-16 px-4">
      <div className="max-w-2xl mx-auto text-center">
        <p className="font-heading text-7xl md:text-8xl font-semibold text-primary tracking-tight">
          404
        </p>
        <h1 className="font-heading text-2xl md:text-3xl font-semibold text-dark-2 uppercase mt-3 mb-4">
          No encontramos esta página
        </h1>
        <p className="font-sans text-gray-mid text-base mb-8 max-w-md mx-auto">
          Es posible que el enlace esté roto, que el producto haya sido
          retirado del catálogo o que la URL esté mal escrita.
        </p>

        {/* Acciones */}
        <div className="flex flex-col sm:flex-row gap-3 justify-center mb-12">
          <Link href="/" className="btn-primary">
            <Home size={16} />
            Ir al inicio
          </Link>
          <Link href="/repuestos" className="btn-secondary">
            <Search size={16} />
            Ver catálogo
          </Link>
        </div>

        {/* Atajos útiles */}
        <div className="bg-bg-light rounded-xl p-6 text-left">
          <p className="text-xs uppercase tracking-wide text-gray-mid font-sans font-semibold mb-3">
            ¿Buscabas alguno de estos?
          </p>
          <ul className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm font-sans">
            <li>
              <Link href="/repuestos" className="text-primary hover:underline inline-flex items-center gap-1">
                <ArrowLeft size={12} /> Catálogo de repuestos
              </Link>
            </li>
            <li>
              <Link href="/garantia" className="text-primary hover:underline inline-flex items-center gap-1">
                <ArrowLeft size={12} /> Política de garantía
              </Link>
            </li>
            <li>
              <Link href="/faq" className="text-primary hover:underline inline-flex items-center gap-1">
                <ArrowLeft size={12} /> Preguntas frecuentes
              </Link>
            </li>
            <li>
              <Link href="/contacto" className="text-primary hover:underline inline-flex items-center gap-1">
                <ArrowLeft size={12} /> Contacto
              </Link>
            </li>
          </ul>

          <div className="mt-5 pt-5 border-t border-gray-200 text-center">
            <p className="text-xs text-gray-mid font-sans mb-2">
              ¿No encuentras el repuesto que buscas?
            </p>
            <a
              href={wpp}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-1.5 text-sm font-semibold text-green-700 hover:text-green-800"
            >
              <MessageCircle size={14} />
              Pregúntale a un asesor por WhatsApp
            </a>
          </div>
        </div>
      </div>
    </div>
  );
}
