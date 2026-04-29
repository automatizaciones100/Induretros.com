"use client";

import { useState, useEffect, FormEvent } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import Image from "next/image";
import { ArrowLeft, MessageCircle, Loader2 } from "lucide-react";
import { useCartStore } from "@/stores/cartStore";
import { buildOrderWhatsAppUrl } from "@/lib/whatsapp";
import { resolveImageUrl } from "@/lib/imageUrl";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

export default function CheckoutPage() {
  const router = useRouter();

  const [hydrated, setHydrated] = useState(false);
  useEffect(() => setHydrated(true), []);

  const items = useCartStore((s) => s.items);
  const totalPrice = useCartStore((s) => s.totalPrice());
  const totalItems = useCartStore((s) => s.totalItems());
  const clearCart = useCartStore((s) => s.clearCart);

  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Si el carrito está vacío, redirigir
  useEffect(() => {
    if (hydrated && items.length === 0 && !submitting) {
      router.replace("/carrito");
    }
  }, [hydrated, items.length, submitting, router]);

  if (!hydrated || items.length === 0) {
    return (
      <div className="container mx-auto py-16 text-center">
        <p className="text-gray-mid font-sans">Cargando…</p>
      </div>
    );
  }

  // ───────────── Submit ─────────────
  const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setError(null);
    setSubmitting(true);

    const formData = new FormData(e.currentTarget);
    const customer = {
      customer_name: String(formData.get("customer_name") || "").trim(),
      customer_email: String(formData.get("customer_email") || "").trim(),
      customer_phone: String(formData.get("customer_phone") || "").trim() || undefined,
      shipping_address: String(formData.get("shipping_address") || "").trim() || undefined,
      notes: String(formData.get("notes") || "").trim() || undefined,
    };
    const payload = {
      ...customer,
      items: items.map((i) => ({ product_id: i.product_id, quantity: i.quantity })),
    };

    // Abrir pestaña SINCRÓNICAMENTE (en user gesture) para evitar popup blocker.
    // Si el navegador devuelve null (mobile o bloqueado), haremos same-tab redirect.
    const wppWindow = window.open("about:blank", "_blank");

    try {
      const res = await fetch(`${API_URL}/api/orders`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      if (!res.ok) {
        const body = await res.json().catch(() => ({}));
        throw new Error(body.detail || `Error ${res.status} al crear el pedido`);
      }

      const order = await res.json();

      // Enriquecer items con datos del carrito (nombre, imagen, slug, sku) para la confirmación
      const enrichedItems = order.items.map((apiItem: { product_id: number; quantity: number; unit_price: number; subtotal: number; id: number }) => {
        const cartItem = items.find((c) => c.product_id === apiItem.product_id);
        return {
          ...apiItem,
          name: cartItem?.name,
          slug: cartItem?.slug,
          sku: cartItem?.sku,
          image_url: cartItem?.image_url,
        };
      });
      const enrichedOrder = { ...order, items: enrichedItems };
      sessionStorage.setItem(`order:${order.id}`, JSON.stringify(enrichedOrder));

      // Construir URL de WhatsApp con el detalle completo del pedido
      const whatsappUrl = buildOrderWhatsAppUrl({
        id: order.id,
        customer_name: customer.customer_name,
        customer_email: customer.customer_email,
        customer_phone: customer.customer_phone,
        shipping_address: customer.shipping_address,
        notes: customer.notes,
        total: order.total,
        items: items.map((i) => ({
          product_id: i.product_id,
          name: i.name,
          sku: i.sku,
          unit_price: i.unit_price,
          quantity: i.quantity,
        })),
      });

      clearCart();

      if (wppWindow && !wppWindow.closed) {
        // Desktop / browser permitió la pestaña: la mandamos a WhatsApp
        wppWindow.location.href = whatsappUrl;
        router.push(`/orden/${order.id}?fresh=1`);
      } else {
        // Mobile o popup bloqueado: redirigimos same-tab a WhatsApp.
        // Antes hacemos push de /orden/[id] para que el back-button regrese ahí.
        router.push(`/orden/${order.id}?fresh=1`);
        setTimeout(() => {
          window.location.href = whatsappUrl;
        }, 50);
      }
    } catch (err) {
      if (wppWindow && !wppWindow.closed) wppWindow.close();
      setError(err instanceof Error ? err.message : "Error al procesar el pedido");
      setSubmitting(false);
    }
  };

  return (
    <div className="container mx-auto py-12">
      <h1 className="section-title mb-2">Confirmar pedido</h1>
      <p className="text-gray-mid font-sans mb-8">
        Completa tus datos. Al confirmar, abriremos WhatsApp con el detalle del
        pedido para que un asesor coordine el pago y envío contigo.
      </p>

      <form onSubmit={handleSubmit} className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Formulario */}
        <div className="lg:col-span-2 space-y-6">
          {/* Datos del cliente */}
          <section className="bg-white border border-gray-100 rounded-xl p-6">
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase mb-5">
              1. Datos de contacto
            </h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div className="sm:col-span-2">
                <label htmlFor="customer_name" className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                  Nombre completo *
                </label>
                <input
                  id="customer_name"
                  name="customer_name"
                  type="text"
                  required
                  minLength={2}
                  maxLength={150}
                  placeholder="Juan Pérez"
                  className="input-field"
                />
              </div>
              <div>
                <label htmlFor="customer_email" className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                  Correo electrónico *
                </label>
                <input
                  id="customer_email"
                  name="customer_email"
                  type="email"
                  required
                  placeholder="tu@correo.com"
                  className="input-field"
                />
              </div>
              <div>
                <label htmlFor="customer_phone" className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                  Teléfono
                </label>
                <input
                  id="customer_phone"
                  name="customer_phone"
                  type="tel"
                  maxLength={30}
                  placeholder="+57 300 123 4567"
                  className="input-field"
                />
              </div>
            </div>
          </section>

          {/* Envío */}
          <section className="bg-white border border-gray-100 rounded-xl p-6">
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase mb-5">
              2. Dirección de envío
            </h2>
            <label htmlFor="shipping_address" className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
              Dirección
            </label>
            <textarea
              id="shipping_address"
              name="shipping_address"
              rows={3}
              maxLength={300}
              placeholder="Calle 50 # 30-25, Apto 401, Medellín, Antioquia"
              className="input-field resize-none"
            />
            <p className="text-xs text-gray-light font-sans mt-2">
              Si prefieres recoger en oficina, déjalo en blanco e indícalo en notas.
            </p>
          </section>

          {/* Notas */}
          <section className="bg-white border border-gray-100 rounded-xl p-6">
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase mb-5">
              3. Notas adicionales
            </h2>
            <label htmlFor="notes" className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
              ¿Algo más que debamos saber? (opcional)
            </label>
            <textarea
              id="notes"
              name="notes"
              rows={3}
              maxLength={1000}
              placeholder="Modelo y serial de la máquina, urgencia, preferencia de pago, etc."
              className="input-field resize-none"
            />
          </section>

          {error && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700 font-sans">
              {error}
            </div>
          )}

          <Link
            href="/carrito"
            className="text-sm font-sans text-gray-mid hover:text-primary transition-colors flex items-center gap-1.5"
          >
            <ArrowLeft size={15} />
            Volver al carrito
          </Link>
        </div>

        {/* Resumen del pedido */}
        <aside className="lg:col-span-1">
          <div className="bg-bg-light rounded-xl p-6 sticky top-32">
            <h2 className="font-heading text-lg font-semibold text-dark-2 uppercase mb-4">
              Tu pedido
            </h2>

            <ul className="space-y-3 mb-5 max-h-72 overflow-y-auto pr-1">
              {items.map((item) => (
                <li key={item.product_id} className="flex gap-3 items-start">
                  <div className="bg-white rounded w-14 h-14 relative flex-shrink-0 border border-gray-100">
                    {item.image_url ? (
                      <Image
                        src={resolveImageUrl(item.image_url)!}
                        alt={item.name}
                        fill
                        className="object-contain p-1"
                        sizes="56px"
                      />
                    ) : (
                      <div className="w-full h-full flex items-center justify-center text-xl opacity-30">
                        🔧
                      </div>
                    )}
                    <span className="absolute -top-1.5 -right-1.5 bg-primary text-white text-[10px] rounded-full min-w-[18px] h-[18px] px-1 flex items-center justify-center font-semibold">
                      {item.quantity}
                    </span>
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-xs font-medium text-dark line-clamp-2 leading-snug">
                      {item.name}
                    </p>
                    <p className="text-xs text-gray-mid font-sans mt-0.5">
                      ${(item.unit_price * item.quantity).toLocaleString("es-CO")}
                    </p>
                  </div>
                </li>
              ))}
            </ul>

            <div className="space-y-2 text-sm font-sans border-t border-gray-200 pt-4 mb-4">
              <div className="flex justify-between text-gray-mid">
                <span>Subtotal ({totalItems})</span>
                <span className="text-dark font-medium">
                  ${totalPrice.toLocaleString("es-CO")}
                </span>
              </div>
              <div className="flex justify-between text-gray-mid">
                <span>Envío</span>
                <span className="text-xs italic">A coordinar</span>
              </div>
            </div>

            <div className="border-t border-gray-200 pt-4 mb-6">
              <div className="flex justify-between items-baseline">
                <span className="font-heading font-semibold text-dark-2 uppercase text-sm">
                  Total
                </span>
                <span className="font-heading text-2xl font-semibold text-primary">
                  ${totalPrice.toLocaleString("es-CO")}
                </span>
              </div>
            </div>

            <button
              type="submit"
              disabled={submitting}
              className="w-full justify-center py-3.5 rounded font-semibold flex items-center gap-2 transition-colors disabled:opacity-60 disabled:cursor-not-allowed bg-dark-2 hover:bg-dark text-white"
            >
              {submitting ? (
                <>
                  <Loader2 size={16} className="animate-spin" />
                  Procesando…
                </>
              ) : (
                <>
                  <MessageCircle size={16} />
                  Confirmar y abrir WhatsApp
                </>
              )}
            </button>

            <p className="text-xs text-gray-light font-sans text-center mt-4 leading-relaxed">
              Se abrirá WhatsApp en una pestaña nueva con el detalle del pedido.
              Al confirmar aceptas nuestra{" "}
              <Link href="/privacidad" className="text-primary hover:underline">
                política de privacidad
              </Link>
              . Te contactaremos para coordinar el pago.
            </p>
          </div>
        </aside>
      </form>
    </div>
  );
}
