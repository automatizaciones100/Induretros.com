/**
 * Construye la URL de wa.me con un mensaje pre-llenado que detalla el pedido.
 * El cliente abre WhatsApp y solo tiene que enviar — el vendedor recibe todo el contexto.
 */

export const WHATSAPP_NUMBER = "576045602662";

interface CartLikeItem {
  product_id: number;
  name: string;
  sku?: string;
  unit_price: number;
  quantity: number;
}

interface OrderForWhatsApp {
  id?: number;
  customer_name: string;
  customer_email?: string;
  customer_phone?: string;
  shipping_address?: string;
  notes?: string;
  items: CartLikeItem[];
  total: number;
}

const fmtCOP = (n: number) => `$${n.toLocaleString("es-CO")}`;

export function buildOrderWhatsAppUrl(order: OrderForWhatsApp): string {
  const lines: string[] = [];

  lines.push("Hola Induretros, quiero coordinar este pedido:");
  lines.push("");

  if (order.id) {
    lines.push(`PEDIDO #${order.id}`);
    lines.push("");
  }

  lines.push("PRODUCTOS:");
  for (const item of order.items) {
    const ref = item.sku ? ` (${item.sku})` : "";
    lines.push(`- ${item.name}${ref}`);
    lines.push(`  ${item.quantity} x ${fmtCOP(item.unit_price)} = ${fmtCOP(item.unit_price * item.quantity)}`);
  }
  lines.push("");

  lines.push(`TOTAL: ${fmtCOP(order.total)}`);
  lines.push("");

  lines.push("CONTACTO:");
  lines.push(`Nombre: ${order.customer_name}`);
  if (order.customer_email) lines.push(`Email: ${order.customer_email}`);
  if (order.customer_phone) lines.push(`Teléfono: ${order.customer_phone}`);
  lines.push("");

  if (order.shipping_address) {
    lines.push("ENVIO:");
    lines.push(order.shipping_address);
    lines.push("");
  }

  if (order.notes) {
    lines.push("NOTAS:");
    lines.push(order.notes);
    lines.push("");
  }

  lines.push("¿Cómo coordinamos el pago?");

  const message = lines.join("\n");
  return `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(message)}`;
}
