import Link from "next/link";
import Image from "next/image";
import { Eye } from "lucide-react";
import type { Product } from "@/domain/entities/Product";
import AddToCartButton from "@/components/cart/AddToCartButton";

interface ProductCardProps {
  product: Product;
}

export default function ProductCard({ product }: ProductCardProps) {
  const displayPrice = product.sale_price || product.price;
  const hasDiscount = product.sale_price && product.regular_price && product.sale_price < product.regular_price;

  return (
    <div className="product-card group">
      {/* Imagen */}
      <div className="relative overflow-hidden bg-bg-light aspect-square">
        {product.image_url ? (
          <Image
            src={product.image_url}
            alt={product.name}
            fill
            className="object-contain p-4 group-hover:scale-105 transition-transform duration-300"
            sizes="(max-width: 640px) 50vw, (max-width: 1024px) 33vw, 25vw"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center text-gray-light">
            <svg className="w-16 h-16 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1}
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
        )}

        {/* Badge descuento */}
        {hasDiscount && (
          <span className="absolute top-2 left-2 bg-red text-white text-xs font-semibold px-2 py-0.5 rounded">
            Oferta
          </span>
        )}

        {/* Badge sin stock */}
        {!product.in_stock && (
          <span className="absolute top-2 right-2 bg-gray-mid text-white text-xs font-semibold px-2 py-0.5 rounded">
            Sin stock
          </span>
        )}

        {/* Acciones hover */}
        <div className="absolute inset-x-0 bottom-0 flex gap-2 p-3 translate-y-full group-hover:translate-y-0 transition-transform duration-200">
          <Link
            href={`/producto/${product.slug}`}
            className="flex-1 bg-dark-2 text-white text-xs font-semibold py-2 rounded flex items-center justify-center gap-1.5 hover:bg-dark transition-colors"
          >
            <Eye size={13} />
            Ver producto
          </Link>
          <AddToCartButton product={product} className="flex-1" />
        </div>
      </div>

      {/* Info */}
      <div className="p-4">
        {product.category && (
          <Link
            href={`/repuestos?categoria=${product.category.slug}`}
            className="text-xs text-primary font-semibold uppercase tracking-wide hover:underline"
          >
            {product.category.name}
          </Link>
        )}

        <Link href={`/producto/${product.slug}`}>
          <h3 className="font-sans font-medium text-dark-2 text-sm mt-1 mb-2 line-clamp-2 hover:text-primary transition-colors leading-snug">
            {product.name}
          </h3>
        </Link>

        {/* SKU */}
        {product.sku && (
          <p className="text-xs text-gray-light mb-2">Ref: {product.sku}</p>
        )}

        {/* Precio */}
        <div className="flex items-center gap-2">
          {displayPrice ? (
            <>
              <span className="text-primary font-semibold font-sans text-base">
                ${displayPrice.toLocaleString("es-CO")}
              </span>
              {hasDiscount && (
                <span className="text-gray-light text-sm line-through">
                  ${product.regular_price!.toLocaleString("es-CO")}
                </span>
              )}
            </>
          ) : (
            <span className="text-sm text-gray-light font-sans">Consultar precio</span>
          )}
        </div>
      </div>
    </div>
  );
}
