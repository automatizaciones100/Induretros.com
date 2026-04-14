import { Suspense } from "react";
import { getProducts, getCategories } from "@/lib/api";
import ProductCard from "@/components/products/ProductCard";
import Link from "next/link";
import type { Metadata } from "next";

export const metadata: Metadata = {
  title: "Repuestos para Excavadoras Hidráulicas",
  description: "Catálogo completo de repuestos para maquinaria pesada. Filtros, balineras, partes hidráulicas, eléctricas y más.",
};

interface PageProps {
  searchParams: Promise<{ categoria?: string; buscar?: string; pagina?: string }>;
}

export default async function RepuestosPage({ searchParams }: PageProps) {
  const params = await searchParams;
  const page = Number(params.pagina) || 1;

  const [productsResult, categoriesResult] = await Promise.allSettled([
    getProducts({ page, category: params.categoria, search: params.buscar, per_page: 12 }),
    getCategories(),
  ]);

  const data = productsResult.status === "fulfilled" ? productsResult.value : { items: [], total: 0, page: 1, pages: 1 };
  const cats = categoriesResult.status === "fulfilled" ? categoriesResult.value : [];

  return (
    <div className="container mx-auto py-8">
      {/* Breadcrumb */}
      <nav className="text-sm text-gray-light mb-6 font-sans">
        <Link href="/" className="hover:text-primary">Inicio</Link>
        <span className="mx-2">/</span>
        <span className="text-dark">Repuestos</span>
        {params.categoria && (
          <>
            <span className="mx-2">/</span>
            <span className="text-dark capitalize">{params.categoria.replace(/-/g, " ")}</span>
          </>
        )}
      </nav>

      <div className="flex flex-col lg:flex-row gap-8">
        {/* Sidebar de filtros */}
        <aside className="w-full lg:w-64 flex-shrink-0">
          <div className="bg-white rounded-lg border border-gray-100 p-5 sticky top-24">
            <h3 className="font-heading font-semibold text-dark-2 uppercase text-sm mb-4">
              Categorías
            </h3>
            <ul className="space-y-1">
              <li>
                <Link
                  href="/repuestos"
                  className={`block px-3 py-2 rounded text-sm font-sans transition-colors ${
                    !params.categoria
                      ? "bg-primary text-white font-semibold"
                      : "text-gray-mid hover:text-primary hover:bg-bg-light"
                  }`}
                >
                  Todos los repuestos
                </Link>
              </li>
              {cats.map((cat) => (
                <li key={cat.id}>
                  <Link
                    href={`/repuestos?categoria=${cat.slug}`}
                    className={`block px-3 py-2 rounded text-sm font-sans transition-colors ${
                      params.categoria === cat.slug
                        ? "bg-primary text-white font-semibold"
                        : "text-gray-mid hover:text-primary hover:bg-bg-light"
                    }`}
                  >
                    {cat.name}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        </aside>

        {/* Contenido principal */}
        <div className="flex-1">
          {/* Header de resultados */}
          <div className="flex items-center justify-between mb-6">
            <div>
              <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">
                {params.categoria
                  ? params.categoria.replace(/-/g, " ")
                  : "Todos los repuestos"}
              </h1>
              <p className="text-sm text-gray-light font-sans mt-1">
                {data.total} {data.total === 1 ? "producto encontrado" : "productos encontrados"}
              </p>
            </div>
          </div>

          {/* Grid de productos */}
          {data.items.length > 0 ? (
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
                      ...(params.categoria && { categoria: params.categoria }),
                      ...(params.buscar && { buscar: params.buscar }),
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
          ) : (
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
          )}
        </div>
      </div>
    </div>
  );
}
