"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  User,
  Package,
  Phone,
  Mail,
  MapPin,
  Loader2,
  AlertCircle,
  ArrowRight,
  ShoppingBag,
  LogOut,
  ShieldCheck,
} from "lucide-react";
import { authFetch } from "@/lib/authFetch";
import { useAuthStore } from "@/stores/authStore";

interface UserMe {
  id: number;
  email: string;
  name: string;
  phone: string | null;
  address: string | null;
  is_admin: boolean;
  created_at: string | null;
}

interface OrderSummary {
  id: number;
  status: string;
  total: number;
  customer_name: string;
  items: { id: number; product_id: number; quantity: number }[];
  created_at: string;
}

interface OrdersResponse {
  items: OrderSummary[];
  total: number;
  page: number;
  per_page: number;
  pages: number;
}

const STATUS_STYLES: Record<string, { label: string; class: string }> = {
  pending: { label: "Pendiente", class: "bg-yellow-100 text-yellow-800" },
  confirmed: { label: "Confirmado", class: "bg-blue-100 text-blue-800" },
  shipped: { label: "Enviado", class: "bg-indigo-100 text-indigo-800" },
  delivered: { label: "Entregado", class: "bg-green-100 text-green-800" },
  cancelled: { label: "Cancelado", class: "bg-gray-200 text-gray-700" },
};

export default function MiCuentaPage() {
  const router = useRouter();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const isAdmin = useAuthStore((s) => s.isAdmin);
  const logout = useAuthStore((s) => s.logout);

  const [hydrated, setHydrated] = useState(false);
  const [me, setMe] = useState<UserMe | null>(null);
  const [orders, setOrders] = useState<OrderSummary[]>([]);
  const [totalOrders, setTotalOrders] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => setHydrated(true), []);

  useEffect(() => {
    if (!hydrated) return;
    if (!isAuthenticated()) {
      router.replace("/login?next=/mi-cuenta");
      return;
    }
    let cancelled = false;
    (async () => {
      setLoading(true);
      setError(null);
      try {
        const [meRes, ordersRes] = await Promise.all([
          authFetch("/api/users/me"),
          authFetch("/api/orders/me?per_page=20"),
        ]);
        if (!meRes.ok) throw new Error(`/users/me ${meRes.status}`);
        if (!ordersRes.ok) throw new Error(`/orders/me ${ordersRes.status}`);
        const meData: UserMe = await meRes.json();
        const ordersData: OrdersResponse = await ordersRes.json();
        if (!cancelled) {
          setMe(meData);
          setOrders(ordersData.items);
          setTotalOrders(ordersData.total);
        }
      } catch (err) {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : "Error cargando tus datos");
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [hydrated, isAuthenticated, router]);

  const handleLogout = () => {
    logout();
    router.replace("/");
  };

  const formatDate = (iso: string) =>
    new Date(iso).toLocaleDateString("es-CO", { year: "numeric", month: "short", day: "numeric" });

  const formatMoney = (n: number) => `$${n.toLocaleString("es-CO")}`;

  if (!hydrated || loading) {
    return (
      <div className="container mx-auto py-16 text-center">
        <Loader2 size={24} className="animate-spin text-gray-light mx-auto mb-2" />
        <p className="text-gray-mid font-sans text-sm">Cargando tu cuenta…</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="container mx-auto py-16">
        <div className="max-w-md mx-auto bg-red-50 border border-red-200 rounded-xl p-6 flex items-start gap-3 text-red-700 text-sm font-sans">
          <AlertCircle size={18} className="flex-shrink-0 mt-0.5" />
          <div>
            <p className="font-semibold mb-1">No pudimos cargar tu cuenta</p>
            <p className="text-xs">{error}</p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto py-10 lg:py-12 px-4">
      <div className="max-w-5xl mx-auto">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
          <div>
            <p className="text-xs uppercase tracking-wide text-gray-light font-sans">Mi cuenta</p>
            <h1 className="font-heading text-2xl md:text-3xl font-semibold text-dark-2 uppercase">
              Hola, {me?.name?.split(" ")[0] ?? "cliente"}
            </h1>
            <p className="text-sm text-gray-mid font-sans mt-1">
              Aquí ves el historial de tus pedidos y los datos de tu cuenta.
            </p>
          </div>
          <div className="flex items-center gap-2">
            {isAdmin() && (
              <Link href="/admin" className="btn-secondary text-sm">
                <ShieldCheck size={14} />
                Panel admin
              </Link>
            )}
            <button onClick={handleLogout} className="btn-secondary text-sm">
              <LogOut size={14} />
              Cerrar sesión
            </button>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Sidebar — datos del usuario */}
          <aside className="lg:col-span-1 space-y-6">
            <div className="bg-white border border-gray-100 rounded-xl p-5">
              <div className="flex items-center gap-3 mb-4">
                <div className="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-heading font-semibold uppercase">
                  {me?.name?.[0] ?? "?"}
                </div>
                <div className="min-w-0">
                  <p className="font-sans font-semibold text-dark-2 truncate">{me?.name}</p>
                  {me?.created_at && (
                    <p className="text-xs text-gray-light font-sans">
                      Cliente desde {formatDate(me.created_at)}
                    </p>
                  )}
                </div>
              </div>
              <dl className="space-y-2.5 text-sm font-sans">
                <Field icon={Mail} label="Correo" value={me?.email} />
                <Field icon={Phone} label="Teléfono" value={me?.phone || "Sin teléfono"} muted={!me?.phone} />
                <Field icon={MapPin} label="Dirección" value={me?.address || "Sin dirección"} muted={!me?.address} />
              </dl>
              <p className="text-xs text-gray-light font-sans mt-4 pt-4 border-t border-gray-100">
                Para actualizar tus datos, contáctanos por WhatsApp o desde el formulario de{" "}
                <Link href="/contacto" className="text-primary hover:underline">
                  contacto
                </Link>
                .
              </p>
            </div>

            <div className="bg-bg-light border border-gray-100 rounded-xl p-5 text-sm font-sans">
              <p className="font-semibold text-dark-2 mb-1">Resumen</p>
              <p className="text-gray-mid">
                Pedidos totales:{" "}
                <span className="font-heading font-semibold text-dark-2 text-base">{totalOrders}</span>
              </p>
            </div>
          </aside>

          {/* Historial de pedidos */}
          <section className="lg:col-span-2">
            <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
              <div className="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Package size={16} className="text-primary" />
                  <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
                    Historial de pedidos
                  </h2>
                </div>
                <Link href="/repuestos" className="text-xs text-primary hover:underline font-sans">
                  Seguir comprando →
                </Link>
              </div>

              {orders.length === 0 ? (
                <div className="p-12 text-center">
                  <ShoppingBag size={36} className="text-gray-light mx-auto mb-3" />
                  <p className="font-heading text-base font-semibold text-dark-2 uppercase mb-1">
                    Aún no has hecho pedidos
                  </p>
                  <p className="text-sm text-gray-mid font-sans mb-5 max-w-sm mx-auto">
                    Explora el catálogo y agrega los repuestos que necesitas. Cuando hagas tu primer
                    pedido aparecerá aquí.
                  </p>
                  <Link href="/repuestos" className="btn-primary">
                    Ver catálogo
                    <ArrowRight size={14} />
                  </Link>
                </div>
              ) : (
                <ul className="divide-y divide-gray-100">
                  {orders.map((o) => {
                    const itemCount = o.items.reduce((s, it) => s + it.quantity, 0);
                    const status = STATUS_STYLES[o.status] ?? { label: o.status, class: "bg-gray-100 text-gray-700" };
                    return (
                      <li key={o.id} className="hover:bg-bg-light/40">
                        <Link
                          href={`/orden/${o.id}`}
                          className="block px-5 py-4 flex flex-wrap items-center gap-4"
                        >
                          <div className="flex-1 min-w-0">
                            <div className="flex flex-wrap items-center gap-2 mb-0.5">
                              <span className="font-heading text-sm font-semibold text-dark-2">
                                Pedido #{o.id}
                              </span>
                              <span
                                className={`text-[11px] uppercase px-2 py-0.5 rounded font-semibold ${status.class}`}
                              >
                                {status.label}
                              </span>
                            </div>
                            <p className="text-xs text-gray-mid font-sans">
                              {formatDate(o.created_at)} · {itemCount} {itemCount === 1 ? "producto" : "productos"}
                            </p>
                          </div>
                          <div className="text-right">
                            <p className="font-heading text-base font-semibold text-primary">
                              {formatMoney(o.total)}
                            </p>
                            <p className="text-xs text-gray-light font-sans inline-flex items-center gap-1">
                              Ver detalle <ArrowRight size={11} />
                            </p>
                          </div>
                        </Link>
                      </li>
                    );
                  })}
                </ul>
              )}
            </div>
          </section>
        </div>
      </div>
    </div>
  );
}

function Field({
  icon: Icon,
  label,
  value,
  muted,
}: {
  icon: typeof User;
  label: string;
  value?: string | null;
  muted?: boolean;
}) {
  return (
    <div className="flex items-start gap-2.5">
      <Icon size={14} className="text-gray-light flex-shrink-0 mt-0.5" />
      <div className="min-w-0 flex-1">
        <dt className="text-xs uppercase tracking-wide text-gray-light">{label}</dt>
        <dd className={`truncate ${muted ? "text-gray-light italic" : "text-dark"}`}>{value || "—"}</dd>
      </div>
    </div>
  );
}
