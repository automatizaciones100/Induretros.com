import type { Order, CreateOrderInput } from "@/domain/entities/Order";

export interface IOrderRepository {
  createOrder(input: CreateOrderInput): Promise<Order>;
  getOrder(id: number): Promise<Order>;
}
