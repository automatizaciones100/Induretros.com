/**
 * Decorator Pattern sobre IProductRepository.
 *
 * Agrega una capa de caché configurable (unstable_cache de Next.js) sin tocar
 * la implementación HTTP ni los Use Cases. Cumple el principio Open/Closed:
 * extendemos el comportamiento de HttpProductRepository sin modificarlo.
 *
 * TTLs diferenciados por tipo de dato:
 *  - Categorías: 5 min  (cambian muy poco)
 *  - Productos destacados: 2 min
 *  - Producto individual: 60 s
 *  - Listado de productos: 60 s
 */
import { unstable_cache } from "next/cache";
import type { IProductRepository } from "@/domain/repositories/IProductRepository";
import type { Product, Category, ProductList, GetProductsParams } from "@/domain/entities/Product";

export class CachedProductRepository implements IProductRepository {
  constructor(private readonly inner: IProductRepository) {}

  getProducts(params?: GetProductsParams): Promise<ProductList> {
    const key = `products-${JSON.stringify(params ?? {})}`;
    return unstable_cache(
      () => this.inner.getProducts(params),
      [key],
      { revalidate: 60, tags: ["products"] }
    )();
  }

  getProduct(slug: string): Promise<Product> {
    return unstable_cache(
      () => this.inner.getProduct(slug),
      [`product-${slug}`],
      { revalidate: 60, tags: ["products", `product-${slug}`] }
    )();
  }

  getCategories(): Promise<Category[]> {
    return unstable_cache(
      () => this.inner.getCategories(),
      ["categories"],
      { revalidate: 300, tags: ["categories"] }
    )();
  }

  getCategory(slug: string): Promise<Category> {
    return unstable_cache(
      () => this.inner.getCategory(slug),
      [`category-${slug}`],
      { revalidate: 300, tags: ["categories", `category-${slug}`] }
    )();
  }
}
