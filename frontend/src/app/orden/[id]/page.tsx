"use client";

import { useEffect, useState } from "react";
import { useParams, useSearchParams } from "next/navigation";
import Link from "next/link";
import Image from "next/image";
import { CheckCircle2, Phone, MessageCircle, Printer, Home, Search, Loader2 } from "lucide-react";
import { buildOrderWhatsAppUrl, WHATSAPP_NUMBER } from "@/lib/whatsapp";
import { resolveImageUrl } from "@/lib/imageUrl";
import { authFetch } from "@/lib/authFetch";
import { useAuthStore } from "@/stores/authStore";

/** Enmascara el email para evitar fuga de PII si la página se imprime/comparte. */
function maskEmail(email?: string): string {
  if (!email || !email.includes("@")) return email || "";
  const [local, domain] = email.split("@");
  const visible = local.length >= 2 ? local.slice(0, 2) : local.slice(0, 1);
  return `${visible}***@${domain}`;
}

interface OrderItem {
  id: number;
  product_id: number;
  quantity: number;
  unit_price: number;
  subtotal: number;
  name?: string;
  slug?: string;
  sku?: string;
  image_url?: string;
}

interface Order {
  id: number;
  status: string;
  total: number;
  customer_name: string;
  customer_email: string;
  customer_phone?: string;
  shipping_address?: string;
  notes?: string;
  items: OrderItem[];
  created_at: string;
}

export default function OrdenPage() {
  const params = useParams<{ id: string }>();
  const searchParams = useSearchParams();
  const id = params.id;
  const isFresh = searchParams.get("fresh") === "1";

  const [order, setOrder] = useState<Order | null>(null);
  const [hydrated, setHydrated] = useState(false);
  const [fetching, setFetching] = useState(false);
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  useEffect(() => {
    setHydrated(true);
    // 1) Intentar sessionStorage (caso checkout reciente)
    const raw = sessionStorage.getItem(`order:${id}`);
    if (raw) {
      try {
        setOrder(JSON.parse(raw));
        return;
      } catch {
        // ignore parse error y caer al fallback
      }
    }
    // 2) Si hay sesión, intentar la API — el backend valida que el pedido sea del usuario o admin
    if (isAuthenticated()) {
      setFetching(true);
      authFetch(`/api/orders/${id}`)
        .then((r) => (r.ok ? r.json() : null))
        .then((data) => {
          if (data) setOrder(data);
        })
        .catch(() => {
          /* silencioso — mostrará el estado vacío */
        })
        .finally(() => setFetching(false));
    }
  }, [id, isAuthenticated]);

  if (!hydrated || fetching) {
    return (
      <div className="container mx-auto py-16 text-center">
        <Loader2 size={20} className="animate-spin text-gray-light mx-auto mb-2" />
        <p className="text-gray-mid font-sans text-sm">Cargando pedido…</p>
      </div>
    );
  }

  // ─── Acceso directo sin sessionStorage ───
  if (!order) {
    return (
      <div className="container mx-auto py-16">
        <div className="max-w-md mx-auto text-center">
          <div className="w-20 h-20 rounded-full bg-bg-light mx-auto flex items-center justify-center mb-6">
            <Search size={32} className="text-gray-light" />
          </div>
          <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase mb-3">
            Pedido #{id}
          </h1>
          <p className="text-gray-mid font-sans mb-2">
            No tenemos los detalles de este pedido en esta sesión.
          </p>
          <p className="text-sm text-gray-light font-sans mb-8">
            Si acabas de realizar un pedido y no ves los detalles, contáctanos por WhatsApp con tu número de orden.
          </p>
          <div className="flex flex-col sm:flex-row gap-3 justify-center">
            <a
              href={`https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(`Hola, consulto por mi pedido #${id}`)}`}
              target="_blank"
              rel="noopener noreferrer"
              className="btn-primary justify-center"
            >
              <MessageCircle size={16} />
              Contactar por WhatsApp
            </a>
            <Link href="/" className="btn-secondary justify-center">
              <Home size={16} />
              Volver al inicio
            </Link>
          </div>
        </div>
      </div>
    );
  }

  // ─── URL de WhatsApp con detalle completo del pedido ───
  const whatsappUrl = buildOrderWhatsAppUrl({
    id: order.id,
    customer_name: order.customer_name,
    customer_email: order.customer_email,
    customer_phone: order.customer_phone,
    shipping_address: order.shipping_address,
    notes: order.notes,
    total: order.total,
    items: order.items.map((it) => ({
      product_id: it.product_id,
      name: it.name || `Producto #${it.product_id}`,
      sku: it.sku,
      unit_price: it.unit_price,
      quantity: it.quantity,
    })),
  });

  const formattedDate = new Date(order.created_at).toLocaleDateString("es-CO", {
    day: "2-digit",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });

  return (
    <div className="container mx-auto py-6 sm:py-12 print:py-4 pb-24 sm:pb-12">
      {/* ───── CTA principal: WhatsApp ───── */}
      {isFresh ? (
        <div className="max-w-3xl mx-auto bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 sm:p-8 mb-6 text-white shadow-md print:hidden">
          <div className="flex items-start gap-3 mb-4">
            <CheckCircle2 size={28} className="flex-shrink-0 mt-1" />
            <div>
              <h1 className="font-heading text-xl sm:text-2xl font-semibold uppercase mb-1">
                ¡Pedido registrado!
              </h1>
              <p className="text-sm text-white/90 font-sans">
                Pedido <strong>#{order.id}</strong> guardado. Para finalizar y
                coordinar el pago, envíanos el detalle por WhatsApp.
              </p>
            </div>
          </div>
          <a
            href={whatsappUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="block w-full bg-white text-green-700 font-heading font-semibold uppercase text-center py-4 rounded-lg hover:bg-green-50 transition-colors text-base sm:text-lg flex items-center justify-center gap-2"
          >
            <MessageCircle size={20} />
            Continuar a WhatsApp
          </a>
          <p className="text-xs text-white/80 text-center mt-3 font-sans">
            Se abrirá WhatsApp con todos los datos pre-llenados
          </p>
        </div>
      ) : (
        <div className="max-w-3xl mx-auto bg-green-50 border border-green-200 rounded-xl p-6 mb-8 flex items-start gap-4 print:hidden">
          <div className="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
            <CheckCircle2 size={28} className="text-white" />
          </div>
          <div>
            <h1 className="font-heading text-xl font-semibold text-dark-2 uppercase mb-1">
              Pedido #{order.id}
            </h1>
            <p className="text-sm text-gray-mid font-sans">
              Tu pedido está registrado. Si quieres reenviar el detalle por
              WhatsApp, usa el botón verde abajo.
            </p>
          </div>
        </div>
      )}

      {/* ───── Encabezado print-friendly ───── */}
      <div className="hidden print:block mb-6 border-b pb-4">
        <h1 className="text-2xl font-bold">Induretros — Pedido #{order.id}</h1>
        <p className="text-sm">{formattedDate}</p>
      </div>

      <div className="max-w-3xl mx-auto bg-white border border-gray-100 rounded-xl overflow-hidden">
        {/* Encabezado del pedido */}
        <div className="px-6 py-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <p className="text-xs text-gray-light font-sans uppercase tracking-wide">
              Pedido
            </p>
            <p className="font-heading text-2xl font-semibold text-dark-2">
              #{order.id}
            </p>
          </div>
          <div className="text-left sm:text-right">
            <p className="text-xs text-gray-light font-sans uppercase tracking-wide">
              Estado
            </p>
            <span className="inline-block bg-yellow-100 text-yellow-800 text-xs font-semibold px-2.5 py-1 rounded uppercase font-sans">
              {order.status === "pending" ? "Pendiente de pago" : order.status}
            </span>
          </div>
          <div className="text-left sm:text-right">
            <p className="text-xs text-gray-light font-sans uppercase tracking-wide">
              Fecha
            </p>
            <p className="text-sm font-sans text-dark">{formattedDate}</p>
          </div>
        </div>

        {/* Items */}
        <div className="px-6 py-5 border-b border-gray-100">
          <h2 className="font-heading text-sm font-semibold text-dark-2 uppercase mb-4">
            Productos ({order.items.length})
          </h2>
          <ul className="space-y-3">
            {order.items.map((item) => (
              <li key={item.id} className="flex gap-3 items-start">
                <div className="bg-bg-light rounded w-16 h-16 relative flex-shrink-0 border border-gray-100">
                  {item.image_url ? (
                    <Image
                      src={resolveImageUrl(item.image_url)!}
                      alt={item.name || "Producto"}
                      fill
                      className="object-contain p-1.5"
                      sizes="64px"
                    />
                  ) : (
                    <div className="w-full h-full flex items-center justify-center text-2xl opacity-30">
                      🔧
                    </div>
                  )}
                </div>
                <div className="flex-1 min-w-0">
                  {item.slug ? (
                    <Link
                      href={`/producto/${item.slug}`}
                      className="font-sans font-medium text-dark text-sm hover:text-primary line-clamp-2 leading-snug"
                    >
                      {item.name || `Producto #${item.product_id}`}
                    </Link>
                  ) : (
                    <p className="font-sans font-medium text-dark text-sm line-clamp-2 leading-snug">
                      {item.name || `Producto #${item.product_id}`}
                    </p>
                  )}
                  {item.sku && (
                    <p className="text-xs text-gray-light font-sans mt-0.5">
                      Ref: <span className="font-medium text-gray-mid">{item.sku}</span>
                    </p>
                  )}
                  <p className="text-xs text-gray-mid font-sans mt-1">
                    {item.quantity} × ${item.unit_price.toLocaleString("es-CO")}
                  </p>
                </div>
                <p className="font-heading font-semibold text-dark whitespace-nowrap">
                  ${item.subtotal.toLocaleString("es-CO")}
                </p>
              </li>
            ))}
          </ul>
        </div>

        {/* Total */}
        <div className="px-6 py-4 bg-bg-light flex justify-between items-baseline">
          <span className="font-heading font-semibold text-dark-2 uppercase text-sm">
            Total
          </span>
          <span className="font-heading text-2xl font-semibold text-primary">
            ${order.total.toLocaleString("es-CO")}
          </span>
        </div>
      </div>

      {/* Datos del cliente */}
      <div className="max-w-3xl mx-auto mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="bg-white border border-gray-100 rounded-xl p-5">
          <h3 className="font-heading text-sm font-semibold text-dark-2 uppercase mb-3">
            Datos de contacto
          </h3>
          <dl className="space-y-1.5 text-sm font-sans">
            <div>
              <dt className="text-xs text-gray-light uppercase tracking-wide">Nombre</dt>
              <dd className="text-dark font-medium">{order.customer_name}</dd>
            </div>
            <div>
              <dt className="text-xs text-gray-light uppercase tracking-wide">Correo</dt>
              <dd className="text-dark" title="Email enmascarado por privacidad">{maskEmail(order.customer_email)}</dd>
            </div>
            {order.customer_phone && (
              <div>
                <dt className="text-xs text-gray-light uppercase tracking-wide">Teléfono</dt>
                <dd className="text-dark">{order.customer_phone}</dd>
              </div>
            )}
          </dl>
        </div>

        {(order.shipping_address || order.notes) && (
          <div className="bg-white border border-gray-100 rounded-xl p-5">
            <h3 className="font-heading text-sm font-semibold text-dark-2 uppercase mb-3">
              Envío y notas
            </h3>
            <dl className="space-y-1.5 text-sm font-sans">
              {order.shipping_address && (
                <div>
                  <dt className="text-xs text-gray-light uppercase tracking-wide">Dirección</dt>
                  <dd className="text-dark whitespace-pre-line">{order.shipping_address}</dd>
                </div>
              )}
              {order.notes && (
                <div>
                  <dt className="text-xs text-gray-light uppercase tracking-wide">Notas</dt>
                  <dd className="text-dark whitespace-pre-line">{order.notes}</dd>
                </div>
              )}
            </dl>
          </div>
        )}
      </div>

      {/* Próximos pasos */}
      <div className="max-w-3xl mx-auto mt-8 bg-blue-50 border border-blue-200 rounded-xl p-5 print:hidden">
        <h3 className="font-heading text-sm font-semibold text-dark-2 uppercase mb-2">
          Próximos pasos
        </h3>
        <ul className="text-sm font-sans text-gray-mid space-y-1.5 list-disc list-inside">
          <li>Te enviaremos un correo confirmando los detalles del pedido</li>
          <li>Un asesor te contactará para coordinar el método de pago</li>
          <li>
            Una vez confirmado el pago, despacharemos los repuestos según la
            dirección indicada
          </li>
        </ul>
      </div>

      {/* Acciones */}
      <div className="max-w-3xl mx-auto mt-8 flex flex-col sm:flex-row gap-3 print:hidden">
        <a
          href={whatsappUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="btn-primary flex-1 justify-center bg-green-600 hover:bg-green-700 border-green-600"
        >
          <MessageCircle size={16} />
          Coordinar pago por WhatsApp
        </a>
        <button
          onClick={() => window.print()}
          className="btn-secondary flex-1 justify-center"
        >
          <Printer size={16} />
          Imprimir / Guardar PDF
        </button>
        <Link href="/repuestos" className="btn-secondary flex-1 justify-center">
          <Home size={16} />
          Seguir comprando
        </Link>
      </div>

      {/* Soporte */}
      <p className="max-w-3xl mx-auto mt-6 text-center text-sm text-gray-mid font-sans print:hidden">
        ¿Dudas?{" "}
        <a
          href="tel:+573007192973"
          className="text-primary hover:underline inline-flex items-center gap-1"
        >
          <Phone size={13} />
          300 719 2973
        </a>
      </p>

      {/* Botón sticky en mobile — siempre visible al hacer scroll en celular */}
      {isFresh && (
        <a
          href={whatsappUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="sm:hidden fixed bottom-0 left-0 right-0 bg-green-600 hover:bg-green-700 text-white font-heading font-semibold uppercase py-4 text-center flex items-center justify-center gap-2 shadow-lg z-50 print:hidden"
          style={{ paddingBottom: "calc(1rem + env(safe-area-inset-bottom))" }}
        >
          <MessageCircle size={18} />
          Enviar por WhatsApp
        </a>
      )}
    </div>
  );
}
