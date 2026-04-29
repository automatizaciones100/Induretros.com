import type { IOrderRepository } from "@/domain/repositories/IOrderRepository";
import type { Order, CreateOrderInput } from "@/domain/entities/Order";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

export class HttpOrderRepository implements IOrderRepository {
  async createOrder(input: CreateOrderInput): Promise<Order> {
    const res = await fetch(`${API_URL}/api/orders`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(input),
    });
    if (!res.ok) throw new Error("Error creando el pedido");
    return res.json();
  }

  async getOrder(id: number): Promise<Order> {
    const res = await fetch(`${API_URL}/api/orders/${id}`, {
      next: { revalidate: 0 },
    });
    if (!res.ok) throw new Error("Pedido no encontrado");
    return res.json();
  }
}
