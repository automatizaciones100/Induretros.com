import type { IOrderRepository } from "@/domain/repositories/IOrderRepository";
import type { Order } from "@/domain/entities/Order";

export class GetOrderUseCase {
  constructor(private readonly repo: IOrderRepository) {}

  async execute(id: number): Promise<Order> {
    return this.repo.getOrder(id);
  }
}
