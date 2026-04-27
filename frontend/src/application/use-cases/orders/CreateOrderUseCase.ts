import type { IOrderRepository } from "@/domain/repositories/IOrderRepository";
import type { Order, CreateOrderInput } from "@/domain/entities/Order";

export class CreateOrderUseCase {
  constructor(private readonly repo: IOrderRepository) {}

  async execute(input: CreateOrderInput): Promise<Order> {
    return this.repo.createOrder(input);
  }
}
