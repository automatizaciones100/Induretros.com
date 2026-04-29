/**
 * robots.txt generado por Next.js.
 * Permite a todos los crawlers indexar el sitio y apunta al sitemap.
 */
import type { MetadataRoute } from "next";

const BASE_URL = process.env.NEXT_PUBLIC_SITE_URL || "https://induretros.com";

export default function robots(): MetadataRoute.Robots {
  return {
    rules: [
      {
        userAgent: "*",
        allow: "/",
        disallow: ["/api/", "/_next/", "/api/auth/", "/api/orders/"],
      },
    ],
    sitemap: `${BASE_URL}/sitemap.xml`,
  };
}
