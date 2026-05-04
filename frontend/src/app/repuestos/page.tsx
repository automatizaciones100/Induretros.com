/**
 * Patrones aplicados:
 *
 * 1. Streaming + Suspense
 *    El sidebar con categorías (datos cacheados 5 min) llega al browser al instante.
 *    El grid de productos hace streaming en paralelo — el browser lo pinta cuando llega.
 *    El usuario ve el layout completo con skeleton en lugar de una página en blanco.
 *
 * 2. Parallel Data Fetching
 *    El sidebar y el grid resuelven sus datos de forma independiente sin bloquearse.
 */
import { Suspense } from "react";
import { getCachedCategories } from "@/lib/cache";
import Link from "next/link";
import type { Metadata } from "next";
import ProductGrid from "./ProductGrid";
import ProductGridSkeleton from "./ProductGridSkeleton";

export const metadata: Metadata = {
  title: "Repuestos para Excavadoras Hidráulicas",
  description:
    "Catálogo completo de repuestos para maquinaria pesada. Filtros, balineras, partes hidráulicas, eléctricas y más.",
};

interface PageProps {
  searchParams: Promise<{ categoria?: string; buscar?: string; pagina?: string }>;
}

export default async function RepuestosPage({ searchParams }: PageProps) {
  const params = await searchParams;
  const page = Number(params.pagina) || 1;

  // Las categorías se resuelven rápido (cache 5 min) y permiten renderizar
  // el sidebar inmediatamente mientras el grid hace streaming
  const cats = await getCachedCategories();

  return (
    <div className="container mx-auto py-8">
      {/* Breadcrumb */}
      <nav className="text-sm text-gray-light mb-6 font-sans">
        <Link href="/" className="hover:text-primary">Inicio</Link>
        <span className="mx-2">/</span>
        <Link href="/repuestos" className={params.categoria || params.buscar ? "hover:text-primary" : "text-dark"}>Repuestos</Link>
        {params.categoria && (
          <>
            <span className="mx-2">/</span>
            <span className="text-dark capitalize">
              {params.categoria.replace(/-/g, " ")}
            </span>
          </>
        )}
        {params.buscar && (
          <>
            <span className="mx-2">/</span>
            <span className="text-dark">Búsqueda: &ldquo;{params.buscar}&rdquo;</span>
          </>
        )}
      </nav>

      <div className="flex flex-col lg:flex-row gap-8">
        {/* Sidebar — disponible de inmediato (cache) */}
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

        {/* Grid — hace streaming mientras el sidebar ya está visible */}
        <div className="flex-1">
          <div className="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div>
              <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">
                {params.buscar
                  ? `Resultados para “${params.buscar}”`
                  : params.categoria
                    ? params.categoria.replace(/-/g, " ")
                    : "Todos los repuestos"}
              </h1>
              {params.buscar && params.categoria && (
                <p className="text-xs text-gray-mid font-sans mt-1">
                  En categoría:{" "}
                  <span className="capitalize">{params.categoria.replace(/-/g, " ")}</span>
                </p>
              )}
            </div>
            {params.buscar && (
              <Link
                href="/repuestos"
                className="text-xs font-sans text-primary hover:underline"
              >
                Limpiar búsqueda
              </Link>
            )}
          </div>

          <Suspense fallback={<ProductGridSkeleton />}>
            <ProductGrid
              page={page}
              categoria={params.categoria}
              buscar={params.buscar}
            />
          </Suspense>
        </div>
      </div>
    </div>
  );
}
