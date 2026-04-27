import type { Product, Category, ProductList, GetProductsParams } from "@/domain/entities/Product";

export interface IProductRepository {
  getProducts(params?: GetProductsParams): Promise<ProductList>;
  getProduct(slug: string): Promise<Product>;
  getCategories(): Promise<Category[]>;
  getCategory(slug: string): Promise<Category>;
}
