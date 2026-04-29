import type { IProductRepository } from "@/domain/repositories/IProductRepository";
import type { Product, Category, ProductList, GetProductsParams } from "@/domain/entities/Product";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

export class HttpProductRepository implements IProductRepository {
  async getProducts(params?: GetProductsParams): Promise<ProductList> {
    const query = new URLSearchParams();
    if (params?.page) query.set("page", String(params.page));
    if (params?.per_page) query.set("per_page", String(params.per_page));
    if (params?.category) query.set("category", params.category);
    if (params?.search) query.set("search", params.search);
    if (params?.featured !== undefined) query.set("featured", String(params.featured));

    const res = await fetch(`${API_URL}/api/products?${query}`, {
      next: { revalidate: 60 },
    });
    if (!res.ok) throw new Error("Error cargando productos");
    return res.json();
  }

  async getProduct(slug: string): Promise<Product> {
    const res = await fetch(`${API_URL}/api/products/${slug}`, {
      next: { revalidate: 60 },
    });
    if (!res.ok) throw new Error("Producto no encontrado");
    return res.json();
  }

  async getCategories(): Promise<Category[]> {
    const res = await fetch(`${API_URL}/api/products/categories`, {
      next: { revalidate: 300 },
    });
    if (!res.ok) throw new Error("Error cargando categorías");
    return res.json();
  }

  async getCategory(slug: string): Promise<Category> {
    const res = await fetch(`${API_URL}/api/products/categories/${slug}`, {
      next: { revalidate: 300 },
    });
    if (!res.ok) throw new Error("Categoría no encontrada");
    return res.json();
  }
}
