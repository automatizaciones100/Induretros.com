/**
 * Carrito de compras global.
 *
 * - Estado persistido en localStorage (sobrevive a recargas y cierres de pestaña)
 * - Operaciones atómicas: agregar, quitar, cambiar cantidad, vaciar
 * - Derivados calculados al vuelo: totalItems, totalPrice
 *
 * Acceso desde componentes:
 *   const items = useCartStore(s => s.items);
 *   const addItem = useCartStore(s => s.addItem);
 */
"use client";

import { create } from "zustand";
import { persist } from "zustand/middleware";
import type { Product } from "@/domain/entities/Product";

export interface CartItem {
  product_id: number;
  slug: string;
  name: string;
  sku?: string;
  image_url?: string;
  unit_price: number;   // precio al momento de agregar (oferta o regular)
  quantity: number;
}

interface CartState {
  items: CartItem[];

  // Acciones
  addItem: (product: Product, quantity?: number) => void;
  removeItem: (productId: number) => void;
  updateQuantity: (productId: number, quantity: number) => void;
  clearCart: () => void;

  // Derivados
  totalItems: () => number;
  totalPrice: () => number;
}

export const useCartStore = create<CartState>()(
  persist(
    (set, get) => ({
      items: [],

      addItem: (product, quantity = 1) => {
        const price = product.sale_price ?? product.price ?? 0;
        if (price <= 0) return; // no permitir agregar sin precio

        set((state) => {
          const existing = state.items.find((i) => i.product_id === product.id);
          if (existing) {
            return {
              items: state.items.map((i) =>
                i.product_id === product.id
                  ? { ...i, quantity: i.quantity + quantity }
                  : i
              ),
            };
          }
          return {
            items: [
              ...state.items,
              {
                product_id: product.id,
                slug: product.slug,
                name: product.name,
                sku: product.sku,
                image_url: product.image_url,
                unit_price: price,
                quantity,
              },
            ],
          };
        });
      },

      removeItem: (productId) =>
        set((state) => ({
          items: state.items.filter((i) => i.product_id !== productId),
        })),

      updateQuantity: (productId, quantity) => {
        if (quantity <= 0) {
          get().removeItem(productId);
          return;
        }
        set((state) => ({
          items: state.items.map((i) =>
            i.product_id === productId ? { ...i, quantity } : i
          ),
        }));
      },

      clearCart: () => set({ items: [] }),

      totalItems: () => get().items.reduce((sum, i) => sum + i.quantity, 0),

      totalPrice: () =>
        get().items.reduce((sum, i) => sum + i.unit_price * i.quantity, 0),
    }),
    {
      name: "induretros-cart", // clave en localStorage
      version: 1,
    }
  )
);
