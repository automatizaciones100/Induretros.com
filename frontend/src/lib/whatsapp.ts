/**
 * Construye la URL de wa.me con un mensaje pre-llenado que detalla el pedido.
 * El cliente abre WhatsApp y solo tiene que enviar — el vendedor recibe todo el contexto.
 *
 * Usa formato markdown de WhatsApp: *negrita* para resaltar secciones.
 */

export const WHATSAPP_NUMBER = "573007192973";

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

/**
 * Escapa los caracteres que WhatsApp interpreta como markdown (*, _, ~)
 * para que no rompan el formato cuando aparecen en valores ingresados por el usuario.
 * WhatsApp soporta el escape con barra invertida.
 */
const escapeMarkdown = (s: string): string => s.replace(/([*_~`])/g, "\\$1");

export function buildOrderWhatsAppUrl(order: OrderForWhatsApp): string {
  const lines: string[] = [];

  lines.push("Hola Induretros, quiero coordinar este pedido:");
  lines.push("");

  if (order.id) {
    lines.push(`*PEDIDO #${order.id}*`);
    lines.push("━━━━━━━━━━━━━━━━");
    lines.push("");
  }

  lines.push("*Productos:*");
  for (const item of order.items) {
    const ref = item.sku ? ` _(${escapeMarkdown(item.sku)})_` : "";
    lines.push(`• ${escapeMarkdown(item.name)}${ref}`);
    lines.push(`   ${item.quantity} × ${fmtCOP(item.unit_price)} = *${fmtCOP(item.unit_price * item.quantity)}*`);
  }
  lines.push("");

  lines.push(`*Total:* ${fmtCOP(order.total)}`);
  lines.push("━━━━━━━━━━━━━━━━");
  lines.push("");

  lines.push("*Contacto:*");
  lines.push(`• ${escapeMarkdown(order.customer_name)}`);
  if (order.customer_email) lines.push(`• ${escapeMarkdown(order.customer_email)}`);
  if (order.customer_phone) lines.push(`• ${escapeMarkdown(order.customer_phone)}`);
  lines.push("");

  if (order.shipping_address) {
    lines.push("*Dirección de envío:*");
    lines.push(escapeMarkdown(order.shipping_address));
    lines.push("");
  }

  if (order.notes) {
    lines.push("*Notas:*");
    lines.push(escapeMarkdown(order.notes));
    lines.push("");
  }

  lines.push("¿Cómo coordinamos el pago?");

  const message = lines.join("\n");
  return `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(message)}`;
}
