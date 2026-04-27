"use client";

import Link from "next/link";
import Image from "next/image";
import { Trash2, ShoppingBag, Minus, Plus, ArrowLeft, ArrowRight } from "lucide-react";
import { useCartStore } from "@/stores/cartStore";
import { useState, useEffect } from "react";

export default function CarritoPage() {
  // Evitar mismatch de hidratación: el store lee localStorage solo en cliente
  const [hydrated, setHydrated] = useState(false);
  useEffect(() => setHydrated(true), []);

  const items = useCartStore((s) => s.items);
  const updateQuantity = useCartStore((s) => s.updateQuantity);
  const removeItem = useCartStore((s) => s.removeItem);
  const clearCart = useCartStore((s) => s.clearCart);
  const totalPrice = useCartStore((s) => s.totalPrice());
  const totalItems = useCartStore((s) => s.totalItems());

  if (!hydrated) {
    return (
      <div className="container mx-auto py-16 text-center">
        <p className="text-gray-mid font-sans">Cargando carrito…</p>
      </div>
    );
  }

  // ───────────── Carrito vacío ─────────────
  if (items.length === 0) {
    return (
      <div className="container mx-auto py-16">
        <div className="max-w-md mx-auto text-center">
          <div className="w-20 h-20 rounded-full bg-bg-light mx-auto flex items-center justify-center mb-6">
            <ShoppingBag size={32} className="text-gray-light" />
          </div>
          <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase mb-3">
            Tu carrito está vacío
          </h1>
          <p className="text-gray-mid font-sans mb-8">
            Explora nuestro catálogo y encuentra los repuestos que necesitas.
          </p>
          <Link href="/repuestos" className="btn-primary inline-flex">
            <ArrowLeft size={16} />
            Ir al catálogo
          </Link>
        </div>
      </div>
    );
  }

  // ───────────── Carrito con productos ─────────────
  return (
    <div className="container mx-auto py-12">
      <h1 className="section-title mb-2">Carrito de compras</h1>
      <p className="text-gray-mid font-sans mb-8">
        {totalItems} {totalItems === 1 ? "producto" : "productos"} en tu carrito
      </p>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Lista de items */}
        <div className="lg:col-span-2 space-y-4">
          {items.map((item) => (
            <div
              key={item.product_id}
              className="bg-white border border-gray-100 rounded-xl p-4 flex gap-4"
            >
              {/* Imagen */}
              <Link
                href={`/producto/${item.slug}`}
                className="flex-shrink-0 bg-bg-light rounded-lg w-24 h-24 sm:w-28 sm:h-28 relative overflow-hidden"
              >
                {item.image_url ? (
                  <Image
                    src={item.image_url}
                    alt={item.name}
                    fill
                    className="object-contain p-2"
                    sizes="112px"
                  />
                ) : (
                  <div className="w-full h-full flex items-center justify-center text-3xl opacity-30">
                    🔧
                  </div>
                )}
              </Link>

              {/* Detalle */}
              <div className="flex-1 min-w-0 flex flex-col justify-between">
                <div>
                  <Link
                    href={`/producto/${item.slug}`}
                    className="font-heading font-semibold text-dark-2 text-sm sm:text-base hover:text-primary transition-colors line-clamp-2"
                  >
                    {item.name}
                  </Link>
                  {item.sku && (
                    <p className="text-xs text-gray-light font-sans mt-1">
                      Ref: <span className="font-medium text-gray-mid">{item.sku}</span>
                    </p>
                  )}
                  <p className="text-sm text-gray-mid font-sans mt-1">
                    ${item.unit_price.toLocaleString("es-CO")} c/u
                  </p>
                </div>

                {/* Selector de cantidad + eliminar */}
                <div className="flex items-center justify-between mt-3 gap-3">
                  <div className="flex items-center border border-gray-200 rounded">
                    <button
                      onClick={() =>
                        updateQuantity(item.product_id, item.quantity - 1)
                      }
                      className="px-2.5 py-1.5 hover:bg-gray-50 transition-colors text-gray-mid"
                      aria-label="Disminuir cantidad"
                    >
                      <Minus size={14} />
                    </button>
                    <input
                      type="number"
                      min="1"
                      max="999"
                      value={item.quantity}
                      onChange={(e) => {
                        const v = parseInt(e.target.value, 10);
                        if (!isNaN(v) && v > 0) {
                          updateQuantity(item.product_id, Math.min(v, 999));
                        }
                      }}
                      className="w-12 text-center font-sans text-sm font-semibold text-dark border-x border-gray-200 py-1.5 focus:outline-none"
                      aria-label="Cantidad"
                    />
                    <button
                      onClick={() =>
                        updateQuantity(item.product_id, item.quantity + 1)
                      }
                      className="px-2.5 py-1.5 hover:bg-gray-50 transition-colors text-gray-mid"
                      aria-label="Aumentar cantidad"
                    >
                      <Plus size={14} />
                    </button>
                  </div>

                  <div className="flex items-center gap-3">
                    <span className="font-heading font-semibold text-primary text-base whitespace-nowrap">
                      ${(item.unit_price * item.quantity).toLocaleString("es-CO")}
                    </span>
                    <button
                      onClick={() => removeItem(item.product_id)}
                      className="text-gray-light hover:text-red transition-colors p-1.5"
                      aria-label="Eliminar producto"
                    >
                      <Trash2 size={16} />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          ))}

          {/* Acciones de la lista */}
          <div className="flex justify-between pt-4">
            <Link
              href="/repuestos"
              className="text-sm font-sans text-gray-mid hover:text-primary transition-colors flex items-center gap-1.5"
            >
              <ArrowLeft size={15} />
              Seguir comprando
            </Link>
            <button
              onClick={() => {
                if (confirm("¿Vaciar todo el carrito?")) clearCart();
              }}
              className="text-sm font-sans text-gray-light hover:text-red transition-colors flex items-center gap-1.5"
            >
              <Trash2 size={14} />
              Vaciar carrito
            </button>
          </div>
        </div>

        {/* Resumen lateral */}
        <aside className="lg:col-span-1">
          <div className="bg-bg-light rounded-xl p-6 sticky top-32">
            <h2 className="font-heading text-lg font-semibold text-dark-2 uppercase mb-4">
              Resumen
            </h2>

            <div className="space-y-2 mb-4 text-sm font-sans">
              <div className="flex justify-between text-gray-mid">
                <span>Subtotal ({totalItems} {totalItems === 1 ? "producto" : "productos"})</span>
                <span className="font-medium text-dark">
                  ${totalPrice.toLocaleString("es-CO")}
                </span>
              </div>
              <div className="flex justify-between text-gray-mid">
                <span>Envío</span>
                <span className="text-xs italic">Se calcula al confirmar</span>
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

            <Link
              href="/checkout"
              className="btn-primary w-full justify-center py-3.5"
            >
              Continuar al pago
              <ArrowRight size={16} />
            </Link>

            <p className="text-xs text-gray-light font-sans text-center mt-4">
              Pago seguro · Datos cifrados · Soporte por WhatsApp
            </p>
          </div>
        </aside>
      </div>
    </div>
  );
}
