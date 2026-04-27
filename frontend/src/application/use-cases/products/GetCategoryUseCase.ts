import type { IProductRepository } from "@/domain/repositories/IProductRepository";
import type { Category } from "@/domain/entities/Product";

export class GetCategoryUseCase {
  constructor(private readonly repo: IProductRepository) {}

  async execute(slug: string): Promise<Category> {
    return this.repo.getCategory(slug);
  }
}
