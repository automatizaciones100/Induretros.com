import type { IProductRepository } from "@/domain/repositories/IProductRepository";
import type { Category } from "@/domain/entities/Product";

export class GetCategoriesUseCase {
  constructor(private readonly repo: IProductRepository) {}

  async execute(): Promise<Category[]> {
    return this.repo.getCategories();
  }
}
