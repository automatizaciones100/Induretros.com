import type { IProductRepository } from "@/domain/repositories/IProductRepository";
import type { ProductList, GetProductsParams } from "@/domain/entities/Product";

export class GetProductsUseCase {
  constructor(private readonly repo: IProductRepository) {}

  async execute(params?: GetProductsParams): Promise<ProductList> {
    return this.repo.getProducts(params);
  }
}
