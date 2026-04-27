"use client";

import { useEffect, useState, useMemo } from "react";
import Link from "next/link";
import {
  Package,
  ShoppingBag,
  FolderTree,
  AlertTriangle,
  Star,
  TrendingUp,
  Clock,
  CheckCircle2,
  XCircle,
  Loader2,
  ArrowRight,
  Search,
  Megaphone,
  Eye,
  MousePointerClick,
  Users as UsersIcon,
  BarChart3,
  ImageOff,
  FileText,
  DollarSign,
  Percent,
  Trophy,
  CalendarDays,
} from "lucide-react";
import { authFetch } from "@/lib/authFetch";
import ComparisonCard from "@/components/admin/ComparisonCard";
import TimeSeriesChart from "@/components/admin/TimeSeriesChart";

interface Stats {
  products: { total: number; in_stock: number; out_of_stock: number; featured: number };
  categories: { total: number };
  orders: {
    total: number;
    pending: number;
    processing: number;
    completed: number;
    cancelled: number;
    revenue: number;
  };
  users: { total: number };
}

interface MarketingStats {
  seo: {
    score: number;
    complete: number;
    total: number;
    missing: { image: number; description: number; short_description: number; sku: number; price: number };
  };
  marketing: {
    on_sale: number;
    featured_no_stock: number;
    orders_last_7_days: number;
    orders_last_30_days: number;
    revenue_last_30_days: number;
    avg_order_value: number;
    top_products: { id: number; name: string; slug: string; units: number; revenue: number }[];
  };
  traffic: {
    unique_visitors_7_days: number;
    unique_visitors_30_days: number;
    pageviews_30_days: number;
    clicks_30_days: number;
    add_to_cart_30_days: number;
    conversion_rate: number;
    top_viewed_products: { id: number; name: string; slug: string; views: number }[];
  };
}

interface Comparison {
  period_days: number;
  current: { visitors: number; pageviews: number; add_to_cart: number; orders: number; revenue: number };
  previous: { visitors: number; pageviews: number; add_to_cart: number; orders: number; revenue: number };
  delta_percent: { visitors: number; pageviews: number; add_to_cart: number; orders: number; revenue: number };
}

interface TimeSeriesPoint {
  date: string;
  visitors: number;
  pageviews: number;
  orders: number;
  revenue: number;
}

const PERIOD_OPTIONS = [
  { days: 7, label: "7 días" },
  { days: 30, label: "30 días" },
  { days: 90, label: "90 días" },
];

export default function AdminDashboard() {
  const [stats, setStats] = useState<Stats | null>(null);
  const [marketing, setMarketing] = useState<MarketingStats | null>(null);
  const [comparison, setComparison] = useState<Comparison | null>(null);
  const [timeseries, setTimeseries] = useState<TimeSeriesPoint[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [periodDays, setPeriodDays] = useState<number>(30);

  // Cargar stats + marketing una sola vez
  useEffect(() => {
    Promise.all([
      authFetch("/api/admin/stats").then((r) => (r.ok ? r.json() : Promise.reject(`Error ${r.status}`))),
      authFetch("/api/admin/marketing").then((r) => (r.ok ? r.json() : Promise.reject(`Error ${r.status}`))),
    ])
      .then(([s, m]) => {
        setStats(s);
        setMarketing(m);
      })
      .catch((err) =>
        setError(typeof err === "string" ? err : err instanceof Error ? err.message : "Error cargando estadísticas")
      );
  }, []);

  // Recargar comparación + timeseries al cambiar período
  useEffect(() => {
    Promise.all([
      authFetch(`/api/admin/analytics/comparison?period_days=${periodDays}`).then((r) => r.json()),
      authFetch(`/api/admin/analytics/timeseries?days=${periodDays}`).then((r) => r.json()),
    ])
      .then(([c, ts]) => {
        setComparison(c);
        setTimeseries(ts.series || []);
      })
      .catch(() => {});
  }, [periodDays]);

  // Datos para gráfico de pedidos en pesos
  const revenueChart = useMemo(
    () => timeseries.map((p) => ({ ...p, revenue: Math.round(p.revenue) })),
    [timeseries]
  );

  if (error) {
    return (
      <div className="p-8">
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700 font-sans max-w-md">
          {error}
        </div>
      </div>
    );
  }

  if (!stats || !marketing) {
    return (
      <div className="p-8 flex items-center gap-2 text-gray-mid font-sans">
        <Loader2 size={18} className="animate-spin" />
        Cargando dashboard…
      </div>
    );
  }

  const seoColor = marketing.seo.score >= 80 ? "text-green-600" : marketing.seo.score >= 50 ? "text-yellow-600" : "text-red-600";
  const seoBg = marketing.seo.score >= 80 ? "bg-green-500" : marketing.seo.score >= 50 ? "bg-yellow-500" : "bg-red-500";

  return (
    <div className="p-6 lg:p-8 max-w-7xl mx-auto">
      {/* Encabezado + filtro de período */}
      <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
        <div>
          <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">Dashboard</h1>
          <p className="text-sm text-gray-mid font-sans mt-1">
            Resumen del estado de la tienda · comparativo con período anterior
          </p>
        </div>
        <div className="flex items-center gap-1 bg-white border border-gray-200 rounded-lg p-1">
          {PERIOD_OPTIONS.map((opt) => (
            <button
              key={opt.days}
              onClick={() => setPeriodDays(opt.days)}
              className={`px-3 py-1.5 text-xs rounded font-sans font-semibold transition-colors ${
                periodDays === opt.days
                  ? "bg-primary text-white"
                  : "text-gray-mid hover:bg-bg-light"
              }`}
            >
              {opt.label}
            </button>
          ))}
        </div>
      </div>

      {/* ─────── Comparativos vs período anterior ─────── */}
      {comparison && (
        <div className="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-8">
          <ComparisonCard
            label="Ingresos"
            current={comparison.current.revenue}
            previous={comparison.previous.revenue}
            delta={comparison.delta_percent.revenue}
            format="currency"
            icon={TrendingUp}
          />
          <ComparisonCard
            label="Pedidos"
            current={comparison.current.orders}
            previous={comparison.previous.orders}
            delta={comparison.delta_percent.orders}
            icon={ShoppingBag}
          />
          <ComparisonCard
            label="Visitantes"
            current={comparison.current.visitors}
            previous={comparison.previous.visitors}
            delta={comparison.delta_percent.visitors}
            icon={UsersIcon}
          />
          <ComparisonCard
            label="Pageviews"
            current={comparison.current.pageviews}
            previous={comparison.previous.pageviews}
            delta={comparison.delta_percent.pageviews}
            icon={Eye}
          />
          <ComparisonCard
            label="Add to cart"
            current={comparison.current.add_to_cart}
            previous={comparison.previous.add_to_cart}
            delta={comparison.delta_percent.add_to_cart}
            icon={MousePointerClick}
          />
        </div>
      )}

      {/* ─────── Gráficos de tendencia ─────── */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
        <div className="bg-white border border-gray-100 rounded-xl p-6">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-2">
              <BarChart3 size={16} className="text-primary" />
              <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
                Tráfico por día
              </h2>
            </div>
            <span className="text-xs text-gray-light font-sans">{periodDays} días</span>
          </div>
          {timeseries.length > 0 ? (
            <TimeSeriesChart
              data={timeseries}
              metrics={[
                { key: "visitors", label: "Visitantes únicos", color: "#0ea5e9" },
                { key: "pageviews", label: "Pageviews", color: "#8b5cf6" },
              ]}
            />
          ) : (
            <p className="text-sm text-gray-light text-center py-8 font-sans">Sin datos…</p>
          )}
        </div>

        <div className="bg-white border border-gray-100 rounded-xl p-6">
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-2">
              <ShoppingBag size={16} className="text-primary" />
              <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
                Pedidos e ingresos por día
              </h2>
            </div>
            <span className="text-xs text-gray-light font-sans">{periodDays} días</span>
          </div>
          {revenueChart.length > 0 ? (
            <TimeSeriesChart
              data={revenueChart}
              metrics={[
                { key: "orders", label: "Pedidos", color: "#10b981" },
                { key: "revenue", label: "Ingresos ($)", color: "#f59e0b" },
              ]}
            />
          ) : (
            <p className="text-sm text-gray-light text-center py-8 font-sans">Sin datos…</p>
          )}
        </div>
      </div>

      {/* ─────────── Marketing ─────────── */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
        <div className="bg-white border border-gray-100 rounded-xl p-6 lg:col-span-1">
          <div className="flex items-center gap-2 mb-4">
            <Megaphone size={16} className="text-primary" />
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
              Marketing
            </h2>
          </div>
          <div className="space-y-3">
            <Row icon={Percent} label="Productos en oferta" value={marketing.marketing.on_sale} valueClass="text-primary" />
            <Row
              icon={AlertTriangle}
              label="Destacados sin stock"
              value={marketing.marketing.featured_no_stock}
              valueClass={marketing.marketing.featured_no_stock > 0 ? "text-red-600" : "text-gray-mid"}
            />
            <Row icon={CalendarDays} label="Pedidos últimos 7 días" value={marketing.marketing.orders_last_7_days} />
            <Row icon={DollarSign} label="Ticket promedio" value={Math.round(marketing.marketing.avg_order_value)} valueClass="text-dark" />
          </div>
        </div>

        <div className="bg-white border border-gray-100 rounded-xl p-6 lg:col-span-2">
          <div className="flex items-center gap-2 mb-4">
            <Trophy size={16} className="text-primary" />
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
              Productos más vendidos
            </h2>
          </div>
          {marketing.marketing.top_products.length === 0 ? (
            <p className="text-sm text-gray-light font-sans py-4 text-center">
              Sin datos de ventas todavía.
            </p>
          ) : (
            <ol className="space-y-2">
              {marketing.marketing.top_products.map((p, idx) => (
                <li key={p.id} className="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0">
                  <span className="w-6 h-6 rounded-full bg-bg-light flex items-center justify-center text-xs font-semibold text-gray-mid font-sans">
                    {idx + 1}
                  </span>
                  <Link
                    href={`/admin/productos/${p.id}`}
                    className="flex-1 min-w-0 text-sm font-sans text-dark hover:text-primary line-clamp-1"
                  >
                    {p.name}
                  </Link>
                  <span className="text-xs text-gray-light font-sans whitespace-nowrap">{p.units} uds</span>
                  <span className="text-sm font-heading font-semibold text-dark whitespace-nowrap min-w-[100px] text-right">
                    ${p.revenue.toLocaleString("es-CO")}
                  </span>
                </li>
              ))}
            </ol>
          )}
        </div>
      </div>

      {/* ─────────── Productos más vistos ─────────── */}
      <div className="bg-white border border-gray-100 rounded-xl p-6 mb-8">
        <div className="flex items-center gap-2 mb-4">
          <Eye size={16} className="text-primary" />
          <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
            Productos más vistos (30 días)
          </h2>
        </div>
        {marketing.traffic.top_viewed_products.length === 0 ? (
          <p className="text-sm text-gray-light font-sans py-4 text-center bg-bg-light rounded">
            Aún no hay datos. Visita algunas fichas de producto desde la tienda para empezar a recopilar.
          </p>
        ) : (
          <ol className="space-y-2">
            {marketing.traffic.top_viewed_products.map((p, idx) => (
              <li key={p.id} className="flex items-center gap-3 py-2 border-b border-gray-50 last:border-0">
                <span className="w-6 h-6 rounded-full bg-bg-light flex items-center justify-center text-xs font-semibold text-gray-mid font-sans">
                  {idx + 1}
                </span>
                <Link
                  href={`/admin/productos/${p.id}`}
                  className="flex-1 min-w-0 text-sm font-sans text-dark hover:text-primary line-clamp-1"
                >
                  {p.name}
                </Link>
                <span className="flex items-center gap-1 text-xs text-gray-mid font-sans whitespace-nowrap">
                  <Eye size={12} />
                  {p.views.toLocaleString("es-CO")} vistas
                </span>
              </li>
            ))}
          </ol>
        )}
      </div>

      {/* ─────────── Salud SEO ─────────── */}
      <div className="bg-white border border-gray-100 rounded-xl p-6 mb-8">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <Search size={16} className="text-primary" />
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
              Salud SEO del catálogo
            </h2>
          </div>
          <Link
            href="/admin/productos"
            className="text-xs font-sans text-primary hover:underline flex items-center gap-1"
          >
            Completar fichas
            <ArrowRight size={12} />
          </Link>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="md:col-span-1 flex flex-col items-center justify-center bg-bg-light rounded-lg p-6">
            <p className="text-xs text-gray-mid font-sans uppercase tracking-wide mb-2">
              Productos completos
            </p>
            <p className={`font-heading text-5xl font-semibold ${seoColor}`}>
              {marketing.seo.score}%
            </p>
            <p className="text-xs text-gray-light font-sans mt-1">
              {marketing.seo.complete} / {marketing.seo.total} productos
            </p>
            <div className="w-full bg-gray-200 rounded-full h-1.5 mt-4">
              <div
                className={`${seoBg} h-1.5 rounded-full transition-all`}
                style={{ width: `${marketing.seo.score}%` }}
              />
            </div>
          </div>

          <div className="md:col-span-2">
            <p className="text-xs text-gray-mid font-sans mb-3">
              Campos faltantes que afectan el posicionamiento en Google:
            </p>
            <div className="space-y-2">
              <Row icon={ImageOff} label="Sin imagen" value={marketing.seo.missing.image} valueClass={marketing.seo.missing.image > 0 ? "text-red-600" : "text-green-600"} />
              <Row icon={FileText} label="Sin descripción larga" value={marketing.seo.missing.description} valueClass={marketing.seo.missing.description > 0 ? "text-red-600" : "text-green-600"} />
              <Row icon={FileText} label="Sin descripción corta (snippet)" value={marketing.seo.missing.short_description} valueClass={marketing.seo.missing.short_description > 0 ? "text-yellow-600" : "text-green-600"} />
              <Row icon={Package} label="Sin SKU/referencia" value={marketing.seo.missing.sku} valueClass={marketing.seo.missing.sku > 0 ? "text-yellow-600" : "text-green-600"} />
              <Row icon={DollarSign} label="Sin precio" value={marketing.seo.missing.price} valueClass={marketing.seo.missing.price > 0 ? "text-red-600" : "text-green-600"} />
            </div>
          </div>
        </div>
      </div>

      {/* ─────── Estado de pedidos ─────── */}
      <div className="bg-white border border-gray-100 rounded-xl p-6 mb-8">
        <div className="flex items-center justify-between mb-4">
          <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
            Estado de pedidos
          </h2>
          <Link
            href="/admin/pedidos"
            className="text-xs font-sans text-primary hover:underline flex items-center gap-1"
          >
            Ver todos
            <ArrowRight size={12} />
          </Link>
        </div>
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <StatusPill icon={Clock} label="Pendientes" count={stats.orders.pending} color="text-yellow-700 bg-yellow-50" />
          <StatusPill icon={Loader2} label="En proceso" count={stats.orders.processing} color="text-blue-700 bg-blue-50" />
          <StatusPill icon={CheckCircle2} label="Completados" count={stats.orders.completed} color="text-green-700 bg-green-50" />
          <StatusPill icon={XCircle} label="Cancelados" count={stats.orders.cancelled} color="text-gray-600 bg-gray-100" />
        </div>
      </div>

      {/* ─────── Inventario y categorías ─────── */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div className="bg-white border border-gray-100 rounded-xl p-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">Catálogo</h2>
            <Link href="/admin/productos" className="text-xs font-sans text-primary hover:underline flex items-center gap-1">
              Gestionar
              <ArrowRight size={12} />
            </Link>
          </div>
          <div className="space-y-3">
            <Row icon={Package} label="Productos totales" value={stats.products.total} />
            <Row icon={CheckCircle2} label="En stock" value={stats.products.in_stock} valueClass="text-green-600" />
            <Row icon={AlertTriangle} label="Sin stock" value={stats.products.out_of_stock} valueClass={stats.products.out_of_stock > 0 ? "text-red-600" : "text-gray-mid"} />
            <Row icon={Star} label="Destacados" value={stats.products.featured} valueClass="text-primary" />
          </div>
        </div>

        <div className="bg-white border border-gray-100 rounded-xl p-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">Categorías</h2>
            <Link href="/admin/categorias" className="text-xs font-sans text-primary hover:underline flex items-center gap-1">
              Gestionar
              <ArrowRight size={12} />
            </Link>
          </div>
          <div className="space-y-3">
            <Row icon={FolderTree} label="Categorías totales" value={stats.categories.total} />
            <Row icon={ShoppingBag} label="Productos por categoría (promedio)" value={stats.categories.total > 0 ? Math.round(stats.products.total / stats.categories.total) : 0} />
          </div>
          <div className="mt-5 pt-4 border-t border-gray-100">
            <Link href="/admin/imagenes" className="btn-secondary w-full justify-center text-xs">
              Cargar imágenes masivamente
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}

// ────────────── Subcomponentes ──────────────

function StatusPill({
  icon: Icon,
  label,
  count,
  color,
}: {
  icon: React.ComponentType<{ size?: number; className?: string }>;
  label: string;
  count: number;
  color: string;
}) {
  return (
    <div className={`rounded-lg p-3 ${color}`}>
      <div className="flex items-center gap-2 mb-1">
        <Icon size={14} />
        <span className="text-xs font-sans font-semibold uppercase tracking-wide">{label}</span>
      </div>
      <p className="font-heading text-xl font-semibold">{count}</p>
    </div>
  );
}

function Row({
  icon: Icon,
  label,
  value,
  valueClass = "text-dark",
}: {
  icon: React.ComponentType<{ size?: number; className?: string }>;
  label: string;
  value: number;
  valueClass?: string;
}) {
  return (
    <div className="flex items-center justify-between text-sm font-sans">
      <span className="flex items-center gap-2 text-gray-mid">
        <Icon size={15} className="text-gray-light" />
        {label}
      </span>
      <span className={`font-heading font-semibold ${valueClass}`}>{value.toLocaleString("es-CO")}</span>
    </div>
  );
}
