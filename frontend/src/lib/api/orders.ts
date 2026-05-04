/**
 * API client para pedidos. Cubre operaciones del cliente (mi historial,
 * detalle propio) y del admin (listado completo, cambio de estado).
 * Endpoints en backend/app/presentation/routers/orders.py y admin.py.
 */
import { authFetch } from "@/lib/authFetch";
import { throwIfNotOk } from "./errors";
import type { OrderDetail, OrderSummary, OrdersPage } from "./types";

// ───────── Cliente: mis pedidos ─────────

export async function listMyOrders(params: {
  page?: number;
  per_page?: number;
} = {}): Promise<OrdersPage> {
  const search = new URLSearchParams();
  if (params.page !== undefined) search.set("page", String(params.page));
  if (params.per_page !== undefined) search.set("per_page", String(params.per_page));
  const qs = search.toString();
  const res = await authFetch(`/api/orders/me${qs ? `?${qs}` : ""}`);
  await throwIfNotOk(res);
  return res.json();
}

/** Obtiene un pedido. El backend valida que el usuario sea el dueño o admin. */
export async function getOrderById(id: number): Promise<OrderDetail> {
  const res = await authFetch(`/api/orders/${id}`);
  await throwIfNotOk(res);
  return res.json();
}

// ───────── Admin: listado y gestión ─────────

export async function listOrdersAdmin(params: {
  page?: number;
  per_page?: number;
  status?: string;
  search?: string;
} = {}): Promise<{ items: OrderSummary[]; total: number; page: number; pages: number }> {
  const search = new URLSearchParams();
  if (params.page !== undefined) search.set("page", String(params.page));
  if (params.per_page !== undefined) search.set("per_page", String(params.per_page));
  if (params.status) search.set("status", params.status);
  if (params.search) search.set("search", params.search);
  const qs = search.toString();
  const res = await authFetch(`/api/admin/orders${qs ? `?${qs}` : ""}`);
  await throwIfNotOk(res);
  return res.json();
}

export async function getOrderAdmin(id: number): Promise<OrderDetail> {
  const res = await authFetch(`/api/admin/orders/${id}`);
  await throwIfNotOk(res);
  return res.json();
}

export async function updateOrderStatus(
  id: number,
  status: string,
): Promise<OrderDetail> {
  const res = await authFetch(`/api/admin/orders/${id}/status`, {
    method: "PATCH",
    body: JSON.stringify({ status }),
  });
  await throwIfNotOk(res);
  return res.json();
}
