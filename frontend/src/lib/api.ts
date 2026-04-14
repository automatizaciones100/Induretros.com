const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

export interface Product {
  id: number;
  name: string;
  slug: string;
  description?: string;
  short_description?: string;
  price?: number;
  regular_price?: number;
  sale_price?: number;
  sku?: string;
  in_stock: boolean;
  image_url?: string;
  category?: Category;
  featured: boolean;
}

export interface Category {
  id: number;
  name: string;
  slug: string;
  description?: string;
  image_url?: string;
  children?: Category[];
}

export interface ProductListResponse {
  items: Product[];
  total: number;
  page: number;
  pages: number;
}

export async function getProducts(params?: {
  page?: number;
  per_page?: number;
  category?: string;
  search?: string;
  featured?: boolean;
}): Promise<ProductListResponse> {
  const query = new URLSearchParams();
  if (params?.page) query.set("page", String(params.page));
  if (params?.per_page) query.set("per_page", String(params.per_page));
  if (params?.category) query.set("category", params.category);
  if (params?.search) query.set("search", params.search);
  if (params?.featured !== undefined) query.set("featured", String(params.featured));

  const res = await fetch(`${API_URL}/api/products?${query}`, { next: { revalidate: 60 } });
  if (!res.ok) throw new Error("Error cargando productos");
  return res.json();
}

export async function getProduct(slug: string): Promise<Product> {
  const res = await fetch(`${API_URL}/api/products/${slug}`, { next: { revalidate: 60 } });
  if (!res.ok) throw new Error("Producto no encontrado");
  return res.json();
}

export async function getCategories(): Promise<Category[]> {
  const res = await fetch(`${API_URL}/api/products/categories`, { next: { revalidate: 300 } });
  if (!res.ok) throw new Error("Error cargando categorías");
  return res.json();
}

export async function getCategory(slug: string): Promise<Category> {
  const res = await fetch(`${API_URL}/api/products/categories/${slug}`, { next: { revalidate: 300 } });
  if (!res.ok) throw new Error("Categoría no encontrada");
  return res.json();
}
