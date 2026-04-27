export interface Category {
  id: number;
  name: string;
  slug: string;
  description?: string;
  image_url?: string;
  parent_id?: number;
  created_at: string;
  children?: Category[];
}

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
  stock?: number;
  in_stock: boolean;
  image_url?: string;
  category?: Category;
  featured: boolean;
  meta_title?: string | null;
  meta_description?: string | null;
  created_at: string;
}

export interface ProductList {
  items: Product[];
  total: number;
  page: number;
  pages: number;
}

export interface GetProductsParams {
  page?: number;
  per_page?: number;
  category?: string;
  search?: string;
  featured?: boolean;
}
