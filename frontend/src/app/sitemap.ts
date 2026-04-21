/**
 * Sitemap dinámico — generado en build time por Next.js.
 * Google lo usa para descubrir y priorizar todas las páginas del sitio.
 *
 * Incluye:
 * - Páginas estáticas (priority alta)
 * - Todas las páginas de producto (generadas desde el backend)
 * - Páginas de categoría como filtros del catálogo
 */
import type { MetadataRoute } from "next";
import { getCachedProducts, getCachedCategories } from "@/lib/cache";

const BASE_URL = process.env.NEXT_PUBLIC_SITE_URL || "https://induretros.com";

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const [{ items: products }, categories] = await Promise.all([
    getCachedProducts({ per_page: 100 }),
    getCachedCategories(),
  ]);

  const staticRoutes: MetadataRoute.Sitemap = [
    { url: BASE_URL, lastModified: new Date(), changeFrequency: "daily", priority: 1.0 },
    { url: `${BASE_URL}/repuestos`, lastModified: new Date(), changeFrequency: "daily", priority: 0.9 },
    { url: `${BASE_URL}/contacto`, lastModified: new Date(), changeFrequency: "monthly", priority: 0.5 },
  ];

  const productRoutes: MetadataRoute.Sitemap = products.map((product) => ({
    url: `${BASE_URL}/producto/${product.slug}`,
    lastModified: new Date(product.created_at),
    changeFrequency: "weekly",
    priority: 0.8,
  }));

  const categoryRoutes: MetadataRoute.Sitemap = categories.map((cat) => ({
    url: `${BASE_URL}/repuestos?categoria=${cat.slug}`,
    lastModified: new Date(),
    changeFrequency: "weekly",
    priority: 0.7,
  }));

  return [...staticRoutes, ...productRoutes, ...categoryRoutes];
}
