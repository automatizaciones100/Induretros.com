"use client";

import { useEffect, useState, useMemo } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import {
  Search,
  Loader2,
  AlertCircle,
  ShoppingBag,
  Eye,
  Clock,
  CheckCircle2,
  XCircle,
  RefreshCcw,
} from "lucide-react";
import { authFetch } from "@/lib/authFetch";

type Status = "pending" | "processing" | "completed" | "cancelled";

interface OrderRow {
  id: number;
  status: Status;
  total: number;
  customer_name: string;
  customer_email: string;
  customer_phone?: string;
  items_count: number;
  created_at: string;
}

interface OrderListResponse {
  items: OrderRow[];
  total: number;
  page: number;
  pages: number;
}

const STATUS_LABELS: Record<Status, string> = {
  pending: "Pendientes",
  processing: "En proceso",
  completed: "Completados",
  cancelled: "Cancelados",
};

const STATUS_COLORS: Record<Status, string> = {
  pending: "bg-yellow-100 text-yellow-700",
  processing: "bg-blue-100 text-blue-700",
  completed: "bg-green-100 text-green-700",
  cancelled: "bg-gray-100 text-gray-600",
};

export default function AdminPedidosPage() {
  const searchParams = useSearchParams();

  const [data, setData] = useState<OrderListResponse | null>(null);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [searchInput, setSearchInput] = useState("");
  const [statusFilter, setStatusFilter] = useState<Status | "">(
    (searchParams.get("status") as Status) || ""
  );
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const fetchOrders = useMemo(
    () => async () => {
      setLoading(true);
      setError(null);

      const params = new URLSearchParams({
        page: String(page),
        per_page: "20",
      });
      if (statusFilter) params.set("status", statusFilter);
      if (search.trim()) params.set("search", search.trim());

      try {
        const res = await authFetch(`/api/admin/orders?${params.toString()}`);
        if (!res.ok) throw new Error(`Error ${res.status}`);
        const json = await res.json();
        setData(json);
      } catch (err) {
        setError(err instanceof Error ? err.message : "Error cargando pedidos");
      } finally {
        setLoading(false);
      }
    },
    [page, search, statusFilter]
  );

  useEffect(() => {
    fetchOrders();
  }, [fetchOrders]);

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
            Pedidos
          </h1>
          <p className="text-sm text-gray-mid font-sans mt-1">
            {data ? `${data.total} pedidos en total` : "Cargando…"}
          </p>
        </div>
        <button
          onClick={() => fetchOrders()}
          className="btn-secondary"
          aria-label="Refrescar"
        >
          <RefreshCcw size={14} />
          Refrescar
        </button>
      </div>

      {/* Filtros por estado */}
      <div className="flex flex-wrap gap-2 mb-4">
        <FilterPill
          label="Todos"
          active={statusFilter === ""}
          onClick={() => {
            setStatusFilter("");
            setPage(1);
          }}
        />
        {(["pending", "processing", "completed", "cancelled"] as Status[]).map((s) => (
          <FilterPill
            key={s}
            label={STATUS_LABELS[s]}
            active={statusFilter === s}
            onClick={() => {
              setStatusFilter(s);
              setPage(1);
            }}
          />
        ))}
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
            placeholder="Buscar por nombre o email…"
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
            <p className="text-gray-mid font-sans text-sm">Cargando pedidos…</p>
          </div>
        ) : error ? (
          <div className="p-6 flex items-start gap-2 text-red-700 bg-red-50 text-sm font-sans">
            <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
            {error}
          </div>
        ) : !data || data.items.length === 0 ? (
          <div className="p-12 text-center">
            <ShoppingBag size={32} className="text-gray-light mx-auto mb-3" />
            <p className="text-gray-mid font-sans">
              {search || statusFilter ? "Sin resultados con estos filtros." : "No hay pedidos todavía."}
            </p>
          </div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full text-sm font-sans">
                <thead className="bg-bg-light text-xs uppercase tracking-wide text-gray-mid">
                  <tr>
                    <th className="text-left px-4 py-3">Pedido</th>
                    <th className="text-left px-4 py-3">Cliente</th>
                    <th className="text-left px-4 py-3 hidden lg:table-cell">Fecha</th>
                    <th className="text-center px-4 py-3 hidden sm:table-cell">Items</th>
                    <th className="text-right px-4 py-3">Total</th>
                    <th className="text-center px-4 py-3">Estado</th>
                    <th className="text-right px-4 py-3 w-16">Ver</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {data.items.map((o) => (
                    <tr key={o.id} className="hover:bg-bg-light/50 transition-colors">
                      <td className="px-4 py-3">
                        <p className="font-heading font-semibold text-dark">#{o.id}</p>
                      </td>
                      <td className="px-4 py-3">
                        <p className="font-medium text-dark line-clamp-1">{o.customer_name}</p>
                        <p className="text-xs text-gray-light line-clamp-1">{o.customer_email}</p>
                      </td>
                      <td className="px-4 py-3 hidden lg:table-cell text-gray-mid text-xs">
                        {new Date(o.created_at).toLocaleDateString("es-CO", {
                          day: "2-digit",
                          month: "short",
                          year: "numeric",
                          hour: "2-digit",
                          minute: "2-digit",
                        })}
                      </td>
                      <td className="px-4 py-3 text-center hidden sm:table-cell text-gray-mid">
                        {o.items_count}
                      </td>
                      <td className="px-4 py-3 text-right whitespace-nowrap font-semibold text-dark">
                        ${o.total.toLocaleString("es-CO")}
                      </td>
                      <td className="px-4 py-3 text-center">
                        <span
                          className={`inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded font-semibold ${STATUS_COLORS[o.status]}`}
                        >
                          {iconForStatus(o.status)}
                          {STATUS_LABELS[o.status].replace("s", "")}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-right">
                        <Link
                          href={`/admin/pedidos/${o.id}`}
                          className="p-1.5 text-gray-mid hover:text-primary hover:bg-primary/5 rounded transition-colors inline-block"
                          title="Ver detalle"
                        >
                          <Eye size={16} />
                        </Link>
                      </td>
                    </tr>
                  ))}
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

function FilterPill({
  label,
  active,
  onClick,
}: {
  label: string;
  active: boolean;
  onClick: () => void;
}) {
  return (
    <button
      onClick={onClick}
      className={`px-3 py-1.5 text-xs rounded-full font-sans font-semibold transition-colors ${
        active
          ? "bg-primary text-white"
          : "bg-white border border-gray-200 text-gray-mid hover:bg-bg-light"
      }`}
    >
      {label}
    </button>
  );
}

function iconForStatus(s: Status) {
  const props = { size: 11 };
  if (s === "pending") return <Clock {...props} />;
  if (s === "processing") return <Loader2 {...props} className="animate-spin" />;
  if (s === "completed") return <CheckCircle2 {...props} />;
  return <XCircle {...props} />;
}
