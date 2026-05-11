/**
 * robots.txt generado por Next.js.
 * Permite a todos los crawlers indexar el sitio y apunta al sitemap.
 */
import type { MetadataRoute } from "next";

const BASE_URL = process.env.NEXT_PUBLIC_SITE_URL || "https://www.induretros.com";

export default function robots(): MetadataRoute.Robots {
  return {
    rules: [
      {
        userAgent: "*",
        allow: "/",
        disallow: [
          // Internas de Next.js / API
          "/api/",
          "/_next/",
          // Admin — nunca indexar
          "/admin/",
          // Páginas privadas de usuario
          "/mi-cuenta",
          "/orden/",
          // Funnel de compra — sin valor SEO y pueden contener datos sensibles
          "/carrito",
          "/checkout",
          // Auth — Google a veces las indexa como duplicado del home
          "/login",
          "/registro",
        ],
      },
    ],
    sitemap: `${BASE_URL}/sitemap.xml`,
  };
}
