"use client";

import { useState } from "react";
import { ShoppingCart, Check } from "lucide-react";
import { useCartStore } from "@/stores/cartStore";
import type { Product } from "@/domain/entities/Product";

interface Props {
  product: Product;
  quantity?: number;
  className?: string;
  size?: "sm" | "md" | "lg";
  showLabel?: boolean;
}

/**
 * Botón que agrega un producto al carrito con feedback visual.
 * Muestra un check verde por 1.2s tras agregar.
 */
export default function AddToCartButton({
  product,
  quantity = 1,
  className = "",
  size = "sm",
  showLabel = true,
}: Props) {
  const addItem = useCartStore((s) => s.addItem);
  const [justAdded, setJustAdded] = useState(false);

  const disabled = !product.in_stock || !(product.sale_price ?? product.price);

  const handleClick = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (disabled) return;
    addItem(product, quantity);
    setJustAdded(true);
    setTimeout(() => setJustAdded(false), 1200);
  };

  const iconSize = size === "lg" ? 18 : size === "md" ? 15 : 13;
  const padding = size === "lg" ? "py-3 px-5" : size === "md" ? "py-2.5 px-4" : "py-2";

  return (
    <button
      onClick={handleClick}
      disabled={disabled}
      aria-label="Agregar al carrito"
      className={`${padding} font-semibold rounded flex items-center justify-center gap-1.5 transition-colors disabled:opacity-50 disabled:cursor-not-allowed ${
        justAdded
          ? "bg-green-600 text-white"
          : "bg-primary text-white hover:bg-secondary hover:text-dark"
      } ${className}`}
    >
      {justAdded ? (
        <>
          <Check size={iconSize} />
          {showLabel && <span className="text-xs">Agregado</span>}
        </>
      ) : (
        <>
          <ShoppingCart size={iconSize} />
          {showLabel && <span className="text-xs">Agregar</span>}
        </>
      )}
    </button>
  );
}
