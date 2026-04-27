"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import {
  Package,
  ShoppingBag,
  FolderTree,
  Users,
  AlertTriangle,
  Star,
  TrendingUp,
  Clock,
  CheckCircle2,
  XCircle,
  Loader2,
  ArrowRight,
} from "lucide-react";
import { authFetch } from "@/lib/authFetch";

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

export default function AdminDashboard() {
  const [stats, setStats] = useState<Stats | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    authFetch("/api/admin/stats")
      .then(async (res) => {
        if (!res.ok) throw new Error(`Error ${res.status}`);
        return res.json();
      })
      .then(setStats)
      .catch((err) =>
        setError(err instanceof Error ? err.message : "Error cargando estadísticas")
      );
  }, []);

  if (error) {
    return (
      <div className="p-8">
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700 font-sans max-w-md">
          {error}
        </div>
      </div>
    );
  }

  if (!stats) {
    return (
      <div className="p-8 flex items-center gap-2 text-gray-mid font-sans">
        <Loader2 size={18} className="animate-spin" />
        Cargando dashboard…
      </div>
    );
  }

  return (
    <div className="p-6 lg:p-8 max-w-7xl mx-auto">
      {/* Encabezado */}
      <div className="mb-8">
        <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">
          Dashboard
        </h1>
        <p className="text-sm text-gray-mid font-sans mt-1">
          Resumen del estado de la tienda
        </p>
      </div>

      {/* KPIs principales — Ingresos y pedidos pendientes */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <KpiCard
          title="Ingresos totales"
          value={`$${stats.orders.revenue.toLocaleString("es-CO")}`}
          icon={TrendingUp}
          tone="primary"
          subtitle={`${stats.orders.total} pedidos`}
        />
        <KpiCard
          title="Pedidos pendientes"
          value={stats.orders.pending}
          icon={Clock}
          tone={stats.orders.pending > 0 ? "warning" : "muted"}
          href="/admin/pedidos?status=pending"
          subtitle="Esperando confirmación"
        />
        <KpiCard
          title="Sin stock"
          value={stats.products.out_of_stock}
          icon={AlertTriangle}
          tone={stats.products.out_of_stock > 0 ? "danger" : "muted"}
          href="/admin/productos?stock=out"
          subtitle="Reabastecer pronto"
        />
        <KpiCard
          title="Usuarios"
          value={stats.users.total}
          icon={Users}
          tone="muted"
          subtitle="Cuentas registradas"
        />
      </div>

      {/* Estado de pedidos */}
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

      {/* Inventario */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div className="bg-white border border-gray-100 rounded-xl p-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
              Catálogo
            </h2>
            <Link
              href="/admin/productos"
              className="text-xs font-sans text-primary hover:underline flex items-center gap-1"
            >
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
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
              Categorías
            </h2>
            <Link
              href="/admin/categorias"
              className="text-xs font-sans text-primary hover:underline flex items-center gap-1"
            >
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

interface KpiCardProps {
  title: string;
  value: string | number;
  icon: React.ComponentType<{ size?: number; className?: string }>;
  tone: "primary" | "warning" | "danger" | "muted";
  subtitle?: string;
  href?: string;
}

function KpiCard({ title, value, icon: Icon, tone, subtitle, href }: KpiCardProps) {
  const toneClass = {
    primary: "bg-primary text-white",
    warning: "bg-yellow-500 text-white",
    danger: "bg-red-500 text-white",
    muted: "bg-gray-100 text-gray-700",
  }[tone];

  const card = (
    <div className="bg-white border border-gray-100 rounded-xl p-5 h-full hover:shadow-sm transition-shadow">
      <div className="flex items-start justify-between mb-3">
        <div className={`w-9 h-9 rounded-lg flex items-center justify-center ${toneClass}`}>
          <Icon size={18} />
        </div>
      </div>
      <p className="text-xs text-gray-mid font-sans uppercase tracking-wide mb-1">
        {title}
      </p>
      <p className="font-heading text-2xl font-semibold text-dark-2">{value}</p>
      {subtitle && (
        <p className="text-xs text-gray-light font-sans mt-1">{subtitle}</p>
      )}
    </div>
  );

  return href ? <Link href={href}>{card}</Link> : card;
}

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
        <span className="text-xs font-sans font-semibold uppercase tracking-wide">
          {label}
        </span>
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
      <span className={`font-heading font-semibold ${valueClass}`}>
        {value.toLocaleString("es-CO")}
      </span>
    </div>
  );
}
