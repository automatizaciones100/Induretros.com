"use client";

import Link from "next/link";
import { ShoppingCart } from "lucide-react";
import { useCartStore } from "@/stores/cartStore";

export default function CartIcon() {
  const count = useCartStore((s) => s.totalItems());

  return (
    <Link
      href="/carrito"
      className="relative flex items-center gap-2 text-dark-2 hover:text-primary transition-colors"
      aria-label={`Carrito de compras (${count} productos)`}
    >
      <div className="relative">
        <ShoppingCart size={24} />
        {count > 0 && (
          <span
            className="absolute -top-2 -right-2 bg-primary text-white text-xs rounded-full min-w-5 h-5 px-1 flex items-center justify-center font-semibold"
            aria-hidden="true"
          >
            {count > 99 ? "99+" : count}
          </span>
        )}
      </div>
      <span className="hidden sm:block text-sm font-sans font-medium">Carrito</span>
    </Link>
  );
}
