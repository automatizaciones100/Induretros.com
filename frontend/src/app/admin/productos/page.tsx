"use client";

import { useEffect, useState, useMemo } from "react";
import Link from "next/link";
import Image from "next/image";
import {
  Plus,
  Search,
  Edit3,
  Trash2,
  Star,
  Package,
  Loader2,
  AlertCircle,
} from "lucide-react";
import { authFetch } from "@/lib/authFetch";
import { resolveImageUrl } from "@/lib/imageUrl";

interface Product {
  id: number;
  name: string;
  slug: string;
  sku?: string;
  price?: number;
  sale_price?: number;
  stock: number;
  in_stock: boolean;
  featured: boolean;
  image_url?: string;
  category?: { id: number; name: string; slug: string };
}

interface ProductList {
  items: Product[];
  total: number;
  page: number;
  pages: number;
}

export default function AdminProductosPage() {
  const [data, setData] = useState<ProductList | null>(null);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [searchInput, setSearchInput] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [deletingId, setDeletingId] = useState<number | null>(null);

  const PER_PAGE = 20;

  const fetchProducts = useMemo(
    () => async () => {
      setLoading(true);
      setError(null);
      const params = new URLSearchParams({
        page: String(page),
        per_page: String(PER_PAGE),
      });
      if (search.trim()) params.set("search", search.trim());

      try {
        const res = await authFetch(`/api/products?${params.toString()}`);
        if (!res.ok) throw new Error(`Error ${res.status}`);
        const json = await res.json();
        setData(json);
      } catch (err) {
        setError(err instanceof Error ? err.message : "Error cargando productos");
      } finally {
        setLoading(false);
      }
    },
    [page, search]
  );

  useEffect(() => {
    fetchProducts();
  }, [fetchProducts]);

  const handleDelete = async (id: number, name: string) => {
    if (!confirm(`¿Eliminar el producto "${name}"? Esta acción no se puede deshacer.`)) return;

    setDeletingId(id);
    try {
      const res = await authFetch(`/api/products/${id}`, { method: "DELETE" });
      if (!res.ok && res.status !== 204) {
        throw new Error(`Error ${res.status}`);
      }
      // refetch
      fetchProducts();
    } catch (err) {
      alert(err instanceof Error ? err.message : "Error al eliminar");
    } finally {
      setDeletingId(null);
    }
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setPage(1);
    setSearch(searchInput);
  };

  return (
    <div className="p-6 lg:p-8 max-w-7xl mx-auto">
      {/* Encabezado */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
          <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">
            Productos
          </h1>
          <p className="text-sm text-gray-mid font-sans mt-1">
            {data ? `${data.total} productos en total` : "Cargando…"}
          </p>
        </div>
        <Link href="/admin/productos/nuevo" className="btn-primary">
          <Plus size={16} />
          Nuevo producto
        </Link>
      </div>

      {/* Buscador */}
      <form onSubmit={handleSearch} className="mb-5 flex gap-2 max-w-md">
        <div className="relative flex-1">
          <Search
            size={16}
            className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none"
          />
          <input
            type="search"
            value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
            placeholder="Buscar por nombre o SKU…"
            maxLength={100}
            className="input-field pl-10"
          />
        </div>
        <button type="submit" className="btn-secondary">
          Buscar
        </button>
        {search && (
          <button
            type="button"
            onClick={() => {
              setSearch("");
              setSearchInput("");
              setPage(1);
            }}
            className="text-xs text-gray-mid hover:text-primary self-center px-2"
          >
            Limpiar
          </button>
        )}
      </form>

      {/* Tabla */}
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        {loading && !data ? (
          <div className="p-12 text-center">
            <Loader2 size={24} className="animate-spin text-gray-light mx-auto mb-3" />
            <p className="text-gray-mid font-sans text-sm">Cargando productos…</p>
          </div>
        ) : error ? (
          <div className="p-6 flex items-start gap-2 text-red-700 bg-red-50 text-sm font-sans">
            <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
            {error}
          </div>
        ) : !data || data.items.length === 0 ? (
          <div className="p-12 text-center">
            <Package size={32} className="text-gray-light mx-auto mb-3" />
            <p className="text-gray-mid font-sans">
              {search ? "Sin resultados para esta búsqueda." : "No hay productos."}
            </p>
          </div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full text-sm font-sans">
                <thead className="bg-bg-light text-xs uppercase tracking-wide text-gray-mid">
                  <tr>
                    <th className="text-left px-4 py-3">Producto</th>
                    <th className="text-left px-4 py-3 hidden md:table-cell">SKU</th>
                    <th className="text-left px-4 py-3 hidden lg:table-cell">Categoría</th>
                    <th className="text-right px-4 py-3">Precio</th>
                    <th className="text-center px-4 py-3 hidden sm:table-cell">Stock</th>
                    <th className="text-center px-4 py-3">Estado</th>
                    <th className="text-right px-4 py-3 w-20">Acciones</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {data.items.map((p) => {
                    const displayPrice = p.sale_price ?? p.price;
                    return (
                      <tr key={p.id} className="hover:bg-bg-light/50 transition-colors">
                        <td className="px-4 py-3">
                          <div className="flex items-center gap-3">
                            <div className="w-10 h-10 bg-bg-light rounded relative flex-shrink-0 overflow-hidden">
                              {p.image_url ? (
                                <Image
                                  src={resolveImageUrl(p.image_url)!}
                                  alt={p.name}
                                  fill
                                  className="object-contain p-1"
                                  sizes="40px"
                                />
                              ) : (
                                <div className="w-full h-full flex items-center justify-center text-gray-light">
                                  <Package size={14} />
                                </div>
                              )}
                            </div>
                            <div className="min-w-0">
                              <p className="font-medium text-dark line-clamp-1">{p.name}</p>
                              <div className="flex items-center gap-2 text-xs text-gray-light mt-0.5">
                                <span>/{p.slug}</span>
                                {p.featured && (
                                  <span className="text-primary flex items-center gap-0.5">
                                    <Star size={11} fill="currentColor" />
                                    Destacado
                                  </span>
                                )}
                              </div>
                            </div>
                          </div>
                        </td>
                        <td className="px-4 py-3 hidden md:table-cell text-gray-mid">
                          {p.sku || <span className="text-gray-light italic">—</span>}
                        </td>
                        <td className="px-4 py-3 hidden lg:table-cell">
                          {p.category ? (
                            <span className="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">
                              {p.category.name}
                            </span>
                          ) : (
                            <span className="text-gray-light italic">Sin categoría</span>
                          )}
                        </td>
                        <td className="px-4 py-3 text-right whitespace-nowrap">
                          {displayPrice ? (
                            <span className="font-semibold text-dark">
                              ${displayPrice.toLocaleString("es-CO")}
                            </span>
                          ) : (
                            <span className="text-gray-light italic text-xs">Sin precio</span>
                          )}
                        </td>
                        <td className="px-4 py-3 text-center hidden sm:table-cell text-gray-mid">
                          {p.stock}
                        </td>
                        <td className="px-4 py-3 text-center">
                          {p.in_stock ? (
                            <span className="inline-block text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded font-semibold">
                              Disponible
                            </span>
                          ) : (
                            <span className="inline-block text-xs bg-red-100 text-red-700 px-2 py-0.5 rounded font-semibold">
                              Agotado
                            </span>
                          )}
                        </td>
                        <td className="px-4 py-3">
                          <div className="flex items-center gap-1 justify-end">
                            <Link
                              href={`/admin/productos/${p.id}`}
                              className="p-1.5 text-gray-mid hover:text-primary hover:bg-primary/5 rounded transition-colors"
                              title="Editar"
                            >
                              <Edit3 size={15} />
                            </Link>
                            <button
                              onClick={() => handleDelete(p.id, p.name)}
                              disabled={deletingId === p.id}
                              className="p-1.5 text-gray-mid hover:text-red-600 hover:bg-red-50 rounded transition-colors disabled:opacity-40"
                              title="Eliminar"
                            >
                              {deletingId === p.id ? (
                                <Loader2 size={15} className="animate-spin" />
                              ) : (
                                <Trash2 size={15} />
                              )}
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>

            {/* Paginación */}
            {data.pages > 1 && (
              <div className="border-t border-gray-100 px-4 py-3 flex items-center justify-between">
                <p className="text-xs text-gray-mid font-sans">
                  Página {data.page} de {data.pages}
                </p>
                <div className="flex gap-1">
                  <button
                    onClick={() => setPage((p) => Math.max(1, p - 1))}
                    disabled={page <= 1}
                    className="px-3 py-1.5 text-xs border border-gray-200 rounded hover:bg-bg-light disabled:opacity-40 disabled:cursor-not-allowed font-sans"
                  >
                    ← Anterior
                  </button>
                  <button
                    onClick={() => setPage((p) => p + 1)}
                    disabled={page >= data.pages}
                    className="px-3 py-1.5 text-xs border border-gray-200 rounded hover:bg-bg-light disabled:opacity-40 disabled:cursor-not-allowed font-sans"
                  >
                    Siguiente →
                  </button>
                </div>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
}
