import type { IProductRepository } from "@/domain/repositories/IProductRepository";
import type { Product } from "@/domain/entities/Product";

export class GetProductUseCase {
  constructor(private readonly repo: IProductRepository) {}

  async execute(slug: string): Promise<Product> {
    return this.repo.getProduct(slug);
  }
}
