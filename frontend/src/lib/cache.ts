/**
 * Request Memoization (patrón Proxy/Cache).
 *
 * React.cache() deduplica llamadas idénticas dentro del MISMO request.
 * Problema que resuelve: generateMetadata y el page component llaman
 * getProductUseCase.execute(slug) por separado → 2 peticiones al backend.
 * Con este wrapper, la segunda llamada devuelve el resultado en memoria sin
 * hacer una nueva petición HTTP.
 *
 * Scope: una entrada por request de Next.js (no persiste entre requests).
 */
import { cache } from "react";
import { getProductUseCase, getCategoriesUseCase, getProductsUseCase } from "@/lib/container";
import type { GetProductsParams } from "@/domain/entities/Product";

export const getCachedProduct = cache((slug: string) =>
  getProductUseCase.execute(slug)
);

export const getCachedCategories = cache(() =>
  getCategoriesUseCase.execute()
);

export const getCachedProducts = cache((params: GetProductsParams) =>
  getProductsUseCase.execute(params)
);
