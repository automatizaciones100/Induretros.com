import { getProduct } from "@/lib/api";
import Image from "next/image";
import Link from "next/link";
import { notFound } from "next/navigation";
import { ShoppingCart, Phone, MessageCircle, ChevronRight } from "lucide-react";
import type { Metadata } from "next";

interface PageProps {
  params: Promise<{ slug: string }>;
}

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { slug } = await params;
  try {
    const product = await getProduct(slug);
    return {
      title: product.name,
      description: product.short_description || `Repuesto: ${product.name}. Disponible en Induretros.`,
    };
  } catch {
    return { title: "Producto no encontrado" };
  }
}

export default async function ProductPage({ params }: PageProps) {
  const { slug } = await params;
  let product;
  try {
    product = await getProduct(slug);
  } catch {
    notFound();
  }

  const displayPrice = product.sale_price || product.price;
  const hasDiscount = product.sale_price && product.regular_price && product.sale_price < product.regular_price;

  return (
    <div className="container mx-auto py-8">
      {/* Breadcrumb */}
      <nav className="text-sm text-gray-light mb-8 font-sans flex items-center gap-1 flex-wrap">
        <Link href="/" className="hover:text-primary">Inicio</Link>
        <ChevronRight size={14} />
        <Link href="/repuestos" className="hover:text-primary">Repuestos</Link>
        {product.category && (
          <>
            <ChevronRight size={14} />
            <Link href={`/repuestos?categoria=${product.category.slug}`} className="hover:text-primary">
              {product.category.name}
            </Link>
          </>
        )}
        <ChevronRight size={14} />
        <span className="text-dark line-clamp-1">{product.name}</span>
      </nav>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-10 lg:gap-16">
        {/* Imagen */}
        <div className="bg-bg-light rounded-xl overflow-hidden aspect-square relative">
          {product.image_url ? (
            <Image
              src={product.image_url}
              alt={product.name}
              fill
              className="object-contain p-8"
              sizes="(max-width: 768px) 100vw, 50vw"
              priority
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center text-8xl opacity-20">🔧</div>
          )}
          {hasDiscount && (
            <span className="absolute top-4 left-4 bg-red text-white text-sm font-semibold px-3 py-1 rounded">
              Oferta
            </span>
          )}
        </div>

        {/* Información */}
        <div>
          {product.category && (
            <Link
              href={`/repuestos?categoria=${product.category.slug}`}
              className="text-xs font-semibold text-primary uppercase tracking-widest hover:underline"
            >
              {product.category.name}
            </Link>
          )}

          <h1 className="font-heading text-2xl md:text-3xl font-semibold text-dark-2 mt-2 mb-4 uppercase leading-tight">
            {product.name}
          </h1>

          {product.sku && (
            <p className="text-sm text-gray-light font-sans mb-4">
              Referencia: <span className="font-semibold text-dark">{product.sku}</span>
            </p>
          )}

          {/* Precio */}
          <div className="mb-6">
            {displayPrice ? (
              <div className="flex items-baseline gap-3">
                <span className="font-heading text-3xl font-semibold text-primary">
                  ${displayPrice.toLocaleString("es-CO")}
                </span>
                {hasDiscount && (
                  <span className="text-gray-light text-lg line-through font-sans">
                    ${product.regular_price!.toLocaleString("es-CO")}
                  </span>
                )}
              </div>
            ) : (
              <p className="text-gray-mid font-sans">Precio a consultar</p>
            )}
          </div>

          {/* Stock */}
          <div className="mb-6">
            {product.in_stock ? (
              <span className="inline-flex items-center gap-1.5 text-green text-sm font-semibold font-sans">
                <span className="w-2 h-2 bg-green rounded-full inline-block" />
                En stock
              </span>
            ) : (
              <span className="inline-flex items-center gap-1.5 text-red text-sm font-semibold font-sans">
                <span className="w-2 h-2 bg-red rounded-full inline-block" />
                Sin stock
              </span>
            )}
          </div>

          {/* Descripción corta */}
          {product.short_description && (
            <p className="font-sans text-gray-mid text-sm mb-6 leading-relaxed border-t border-b border-gray-100 py-4">
              {product.short_description}
            </p>
          )}

          {/* Botones de acción */}
          <div className="flex flex-col sm:flex-row gap-3 mb-6">
            <button
              disabled={!product.in_stock}
              className="btn-primary flex-1 justify-center py-3.5 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ShoppingCart size={18} />
              Agregar al carrito
            </button>
            <a
              href={`https://wa.me/573001234567?text=Hola, estoy interesado en: ${encodeURIComponent(product.name)}`}
              target="_blank"
              rel="noopener noreferrer"
              className="btn-secondary flex-1 justify-center py-3.5 bg-green text-white border-green hover:bg-green/90 hover:text-white"
            >
              <MessageCircle size={18} />
              Cotizar por WhatsApp
            </a>
          </div>

          <a href="tel:+576045602662" className="flex items-center gap-2 text-gray-mid hover:text-primary transition-colors text-sm font-sans">
            <Phone size={15} />
            ¿Dudas? Llámanos: (604) 560-2662
          </a>

          {/* Descripción completa */}
          {product.description && (
            <div className="mt-8 pt-6 border-t border-gray-100">
              <h3 className="font-heading font-semibold text-dark-2 uppercase text-sm mb-3">Descripción</h3>
              <div
                className="font-sans text-gray-mid text-sm leading-relaxed prose prose-sm max-w-none"
                dangerouslySetInnerHTML={{ __html: product.description }}
              />
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
