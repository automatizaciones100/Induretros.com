/**
 * Componente aislado para el grid de productos.
 * Al ser un Server Component separado, Next.js puede hacer streaming:
 * el shell de la página (sidebar, breadcrumb) llega al browser
 * inmediatamente mientras este componente resuelve sus datos.
 */
import { getCachedProducts, getCachedCategories } from "@/lib/cache";
import ProductCard from "@/components/products/ProductCard";
import Link from "next/link";

interface ProductGridProps {
  page: number;
  categoria?: string;
  buscar?: string;
}

export default async function ProductGrid({ page, categoria, buscar }: ProductGridProps) {
  const data = await getCachedProducts({
    page,
    category: categoria,
    search: buscar,
    per_page: 12,
  });

  if (data.items.length === 0) {
    return (
      <div className="text-center py-20">
        <div className="text-6xl mb-4">🔍</div>
        <h3 className="font-heading text-xl font-semibold text-dark-2 mb-2">
          No encontramos productos
        </h3>
        <p className="font-sans text-gray-mid mb-6">
          Intenta con otra categoría o contáctanos directamente.
        </p>
        <Link href="/contacto" className="btn-primary">
          Contactar asesor
        </Link>
      </div>
    );
  }

  return (
    <>
      <div className="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
        {data.items.map((product) => (
          <ProductCard key={product.id} product={product} />
        ))}
      </div>

      {/* Paginación */}
      {data.pages > 1 && (
        <div className="flex justify-center gap-2 mt-10">
          {Array.from({ length: data.pages }, (_, i) => i + 1).map((p) => {
            const href = new URLSearchParams({
              ...(categoria && { categoria }),
              ...(buscar && { buscar }),
              pagina: String(p),
            }).toString();
            return (
              <Link
                key={p}
                href={`/repuestos?${href}`}
                className={`w-9 h-9 rounded flex items-center justify-center text-sm font-semibold font-sans transition-colors ${
                  p === page
                    ? "bg-primary text-white"
                    : "bg-white border border-gray-200 text-dark-2 hover:border-primary hover:text-primary"
                }`}
              >
                {p}
              </Link>
            );
          })}
        </div>
      )}
    </>
  );
}
