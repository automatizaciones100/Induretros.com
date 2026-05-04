import Link from "next/link";
import { ArrowRight, MessageCircle, Phone } from "lucide-react";
import type { LucideIcon } from "lucide-react";
import { getLegalPage } from "@/lib/legalPages";
import { getCachedProducts } from "@/lib/cache";
import { getSiteSettings, whatsappLink } from "@/lib/siteSettings";
import ProductCard from "@/components/products/ProductCard";

interface Props {
  /** Slug en legal_pages, ej. "marca-caterpillar" o "ciudad-medellin". */
  pageSlug: string;
  /** Término de búsqueda para filtrar productos del bloque. Si es null, muestra featured. */
  productsQuery: string | null;
  /** Icono decorativo del header. */
  icon: LucideIcon;
  /** Breadcrumb: nombre de la sección padre (ej. "Marcas", "Ciudades"). */
  parentLabel: string;
  /** Breadcrumb: URL de la sección padre. */
  parentHref: string;
  /** Texto a mostrar como segundo nivel del breadcrumb (ej. "Caterpillar"). */
  currentLabel: string;
  /** Subtítulo bajo el h1. */
  subtitle: string;
  /** Cuántos productos mostrar en el grid (default 8). */
  productsLimit?: number;
}

/**
 * Layout reutilizable de página de aterrizaje SEO. Renderiza:
 *   1. Breadcrumb
 *   2. Header con icono y título dinámico
 *   3. Contenido HTML editable (legal_pages)
 *   4. Grid de productos relacionados
 *   5. CTA final con WhatsApp + teléfono
 */
export default async function LandingPageView({
  pageSlug,
  productsQuery,
  icon: Icon,
  parentLabel,
  parentHref,
  currentLabel,
  subtitle,
  productsLimit = 8,
}: Props) {
  const [page, settings, productsResult] = await Promise.all([
    getLegalPage(pageSlug),
    getSiteSettings(),
    getCachedProducts(
      productsQuery
        ? { search: productsQuery, per_page: productsLimit, page: 1 }
        : { featured: true, per_page: productsLimit, page: 1 },
    ).catch(() => ({ items: [], total: 0, page: 1, pages: 0 })),
  ]);
  const wpp = whatsappLink(settings.whatsapp_number);
  const products = productsResult.items;

  return (
    <div className="container mx-auto py-10 lg:py-12 px-4">
      {/* Breadcrumb */}
      <nav className="text-sm text-gray-light mb-6 font-sans">
        <Link href="/" className="hover:text-primary">Inicio</Link>
        <span className="mx-2">/</span>
        <Link href={parentHref} className="hover:text-primary">{parentLabel}</Link>
        <span className="mx-2">/</span>
        <span className="text-dark">{currentLabel}</span>
      </nav>

      <div className="max-w-4xl mx-auto">
        {/* Header */}
        <div className="text-center mb-10">
          <div className="w-14 h-14 rounded-full bg-primary/10 mx-auto flex items-center justify-center mb-4">
            <Icon size={28} className="text-primary" />
          </div>
          <h1 className="section-title">{page?.title || currentLabel}</h1>
          <p className="section-subtitle">{subtitle}</p>
        </div>

        {/* Contenido editable */}
        {page ? (
          <article
            className="legal-content bg-white border border-gray-100 rounded-xl p-8 md:p-10"
            dangerouslySetInnerHTML={{ __html: page.content }}
          />
        ) : (
          <div className="bg-bg-light rounded-xl p-10 text-center">
            <p className="text-gray-mid font-sans">
              Aún no hay contenido publicado en esta página.
            </p>
          </div>
        )}
      </div>

      {/* Productos relacionados */}
      {products.length > 0 && (
        <div className="max-w-6xl mx-auto mt-12">
          <div className="flex items-end justify-between mb-6">
            <div>
              <h2 className="section-title">Productos relacionados</h2>
              <p className="section-subtitle">
                Algunos de nuestros repuestos más solicitados
              </p>
            </div>
            <Link
              href={productsQuery ? `/repuestos?buscar=${encodeURIComponent(productsQuery)}` : "/repuestos"}
              className="hidden md:flex items-center gap-1.5 text-primary font-semibold text-sm hover:underline"
            >
              Ver todos <ArrowRight size={14} />
            </Link>
          </div>
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-5">
            {products.map((p) => (
              <ProductCard key={p.id} product={p} />
            ))}
          </div>
          <div className="text-center mt-8 md:hidden">
            <Link
              href={productsQuery ? `/repuestos?buscar=${encodeURIComponent(productsQuery)}` : "/repuestos"}
              className="btn-outline"
            >
              Ver todos los productos
              <ArrowRight size={16} />
            </Link>
          </div>
        </div>
      )}

      {/* CTA WhatsApp */}
      <div className="max-w-4xl mx-auto mt-12 bg-dark-2 rounded-xl p-8 text-center text-white">
        <h3 className="font-heading text-xl font-semibold uppercase mb-2">
          ¿Necesitas cotizar un repuesto?
        </h3>
        <p className="text-gray-300 text-sm mb-6">
          Escríbenos por WhatsApp con la referencia o descripción y te
          cotizamos al instante.
        </p>
        <div className="flex flex-col sm:flex-row gap-3 justify-center">
          <a
            href={wpp}
            target="_blank"
            rel="noopener noreferrer"
            className="bg-green-600 hover:bg-green-700 text-white font-semibold rounded px-6 py-3 inline-flex items-center justify-center gap-2 transition-colors"
          >
            <MessageCircle size={18} />
            Cotizar por WhatsApp
          </a>
          {settings.organization_phone && (
            <a
              href={`tel:${settings.organization_phone.replace(/\s/g, "")}`}
              className="bg-transparent border border-gray-600 text-gray-300 hover:bg-white/10 hover:text-white font-semibold rounded px-6 py-3 inline-flex items-center justify-center gap-2 transition-colors"
            >
              <Phone size={16} />
              {settings.organization_phone}
            </a>
          )}
        </div>
      </div>
    </div>
  );
}
