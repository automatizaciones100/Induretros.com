"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import Link from "next/link";
import {
  ArrowLeft,
  Loader2,
  AlertCircle,
  User,
  Mail,
  Phone,
  MapPin,
  FileText,
  CheckCircle2,
  XCircle,
  Clock,
  Save,
} from "lucide-react";
import { getOrderAdmin, updateOrderStatus } from "@/lib/api/orders";

type Status = "pending" | "processing" | "completed" | "cancelled";

interface Item {
  id: number;
  product_id: number;
  quantity: number;
  unit_price: number;
  subtotal: number;
}

interface OrderDetail {
  id: number;
  status: Status;
  total: number;
  customer_name: string;
  customer_email: string;
  customer_phone?: string;
  shipping_address?: string;
  notes?: string;
  items_count: number;
  items: Item[];
  created_at: string;
}

const STATUS_LABELS: Record<Status, string> = {
  pending: "Pendiente",
  processing: "En proceso",
  completed: "Completado",
  cancelled: "Cancelado",
};

const STATUS_COLORS: Record<Status, string> = {
  pending: "bg-yellow-100 text-yellow-700 border-yellow-300",
  processing: "bg-blue-100 text-blue-700 border-blue-300",
  completed: "bg-green-100 text-green-700 border-green-300",
  cancelled: "bg-gray-100 text-gray-600 border-gray-300",
};

export default function AdminPedidoDetailPage() {
  const { id } = useParams<{ id: string }>();
  const [order, setOrder] = useState<OrderDetail | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [newStatus, setNewStatus] = useState<Status | null>(null);
  const [saving, setSaving] = useState(false);

  const fetchOrder = async () => {
    setError(null);
    try {
      const data = await getOrderAdmin(Number(id));
      setOrder(data as unknown as OrderDetail);
      setNewStatus(data.status as Status);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error cargando pedido");
    }
  };

  useEffect(() => {
    fetchOrder();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id]);

  const saveStatus = async () => {
    if (!newStatus || !order || newStatus === order.status) return;
    setSaving(true);
    try {
      const updated = await updateOrderStatus(Number(id), newStatus);
      setOrder(updated as unknown as OrderDetail);
    } catch (err) {
      alert(err instanceof Error ? err.message : "Error al guardar");
    } finally {
      setSaving(false);
    }
  };

  if (error) {
    return (
      <div className="p-8 max-w-4xl mx-auto">
        <Link
          href="/admin/pedidos"
          className="inline-flex items-center gap-1.5 text-sm text-gray-mid hover:text-primary mb-4 font-sans"
        >
          <ArrowLeft size={14} />
          Volver al listado
        </Link>
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700 font-sans flex items-start gap-2">
          <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
          {error}
        </div>
      </div>
    );
  }

  if (!order) {
    return (
      <div className="p-8 flex items-center gap-2 text-gray-mid font-sans">
        <Loader2 size={18} className="animate-spin" />
        Cargando pedido…
      </div>
    );
  }

  const formattedDate = new Date(order.created_at).toLocaleDateString("es-CO", {
    day: "2-digit",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });

  const dirty = newStatus !== order.status;

  return (
    <div className="p-6 lg:p-8 max-w-5xl mx-auto">
      <Link
        href="/admin/pedidos"
        className="inline-flex items-center gap-1.5 text-sm text-gray-mid hover:text-primary mb-4 font-sans"
      >
        <ArrowLeft size={14} />
        Volver al listado
      </Link>

      {/* Encabezado */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
          <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">
            Pedido #{order.id}
          </h1>
          <p className="text-sm text-gray-mid font-sans mt-1">
            Creado el {formattedDate}
          </p>
        </div>
        <span
          className={`inline-flex items-center gap-1.5 text-xs px-3 py-1.5 rounded border font-semibold ${STATUS_COLORS[order.status]}`}
        >
          {iconForStatus(order.status)}
          {STATUS_LABELS[order.status]}
        </span>
      </div>

      {/* Cambiar estado */}
      <section className="bg-white border border-gray-100 rounded-xl p-6 mb-6">
        <h2 className="font-heading text-base font-semibold text-dark-2 uppercase mb-4">
          Cambiar estado
        </h2>
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-4">
          {(["pending", "processing", "completed", "cancelled"] as Status[]).map((s) => (
            <button
              key={s}
              onClick={() => setNewStatus(s)}
              className={`px-3 py-2.5 rounded-lg text-xs font-sans font-semibold transition-colors flex items-center justify-center gap-1.5 border ${
                newStatus === s
                  ? STATUS_COLORS[s] + " border-current"
                  : "bg-white border-gray-200 text-gray-mid hover:bg-bg-light"
              }`}
            >
              {iconForStatus(s)}
              {STATUS_LABELS[s]}
            </button>
          ))}
        </div>
        <button
          onClick={saveStatus}
          disabled={!dirty || saving}
          className="btn-primary disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {saving ? <Loader2 size={14} className="animate-spin" /> : <Save size={14} />}
          {dirty ? "Guardar cambio" : "Sin cambios"}
        </button>
      </section>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Items */}
        <div className="lg:col-span-2 space-y-6">
          <section className="bg-white border border-gray-100 rounded-xl overflow-hidden">
            <div className="px-6 py-4 border-b border-gray-100">
              <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
                Productos ({order.items.length})
              </h2>
            </div>
            <table className="w-full text-sm font-sans">
              <thead className="bg-bg-light text-xs uppercase tracking-wide text-gray-mid">
                <tr>
                  <th className="text-left px-6 py-3">Producto</th>
                  <th className="text-center px-4 py-3">Cant.</th>
                  <th className="text-right px-4 py-3">Precio</th>
                  <th className="text-right px-6 py-3">Subtotal</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {order.items.map((it) => (
                  <tr key={it.id}>
                    <td className="px-6 py-3 text-dark">Producto #{it.product_id}</td>
                    <td className="px-4 py-3 text-center text-gray-mid">{it.quantity}</td>
                    <td className="px-4 py-3 text-right text-gray-mid">
                      ${it.unit_price.toLocaleString("es-CO")}
                    </td>
                    <td className="px-6 py-3 text-right font-semibold text-dark">
                      ${it.subtotal.toLocaleString("es-CO")}
                    </td>
                  </tr>
                ))}
              </tbody>
              <tfoot>
                <tr className="bg-bg-light">
                  <td colSpan={3} className="px-6 py-4 text-right font-heading uppercase text-sm font-semibold">
                    Total
                  </td>
                  <td className="px-6 py-4 text-right font-heading text-xl font-semibold text-primary">
                    ${order.total.toLocaleString("es-CO")}
                  </td>
                </tr>
              </tfoot>
            </table>
          </section>

          {order.notes && (
            <section className="bg-white border border-gray-100 rounded-xl p-6">
              <h2 className="font-heading text-sm font-semibold text-dark-2 uppercase mb-3 flex items-center gap-2">
                <FileText size={14} />
                Notas del cliente
              </h2>
              <p className="text-sm text-gray-mid font-sans whitespace-pre-line">
                {order.notes}
              </p>
            </section>
          )}
        </div>

        {/* Cliente */}
        <aside className="lg:col-span-1">
          <section className="bg-white border border-gray-100 rounded-xl p-6 space-y-4">
            <h2 className="font-heading text-sm font-semibold text-dark-2 uppercase">
              Cliente
            </h2>
            <Field icon={User} label="Nombre" value={order.customer_name} />
            <Field
              icon={Mail}
              label="Email"
              value={
                <a
                  href={`mailto:${order.customer_email}`}
                  className="text-primary hover:underline break-all"
                >
                  {order.customer_email}
                </a>
              }
            />
            {order.customer_phone && (
              <Field
                icon={Phone}
                label="Teléfono"
                value={
                  <a
                    href={`tel:${order.customer_phone}`}
                    className="text-primary hover:underline"
                  >
                    {order.customer_phone}
                  </a>
                }
              />
            )}
            {order.shipping_address && (
              <Field
                icon={MapPin}
                label="Dirección"
                value={<span className="whitespace-pre-line">{order.shipping_address}</span>}
              />
            )}
          </section>
        </aside>
      </div>
    </div>
  );
}

function Field({
  icon: Icon,
  label,
  value,
}: {
  icon: React.ComponentType<{ size?: number; className?: string }>;
  label: string;
  value: React.ReactNode;
}) {
  return (
    <div>
      <div className="flex items-center gap-1.5 text-xs text-gray-light uppercase tracking-wide font-sans mb-1">
        <Icon size={12} />
        {label}
      </div>
      <div className="text-sm text-dark font-sans">{value}</div>
    </div>
  );
}

function iconForStatus(s: Status) {
  const props = { size: 13 };
  if (s === "pending") return <Clock {...props} />;
  if (s === "processing") return <Loader2 {...props} className="animate-spin" />;
  if (s === "completed") return <CheckCircle2 {...props} />;
  return <XCircle {...props} />;
}
