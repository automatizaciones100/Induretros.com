/**
 * Contenedor de Inyección de Dependencias.
 *
 * Es el único lugar del frontend donde las capas se conectan:
 *   Infrastructure (implementaciones HTTP) → Application (use cases)
 *
 * Las páginas y componentes importan solo los use cases de aquí,
 * nunca los repositorios concretos directamente.
 */
import { HttpProductRepository } from "@/infrastructure/repositories/HttpProductRepository";
import { CachedProductRepository } from "@/infrastructure/repositories/CachedProductRepository";
import { HttpOrderRepository } from "@/infrastructure/repositories/HttpOrderRepository";
import { GetProductsUseCase } from "@/application/use-cases/products/GetProductsUseCase";
import { GetProductUseCase } from "@/application/use-cases/products/GetProductUseCase";
import { GetCategoriesUseCase } from "@/application/use-cases/products/GetCategoriesUseCase";
import { GetCategoryUseCase } from "@/application/use-cases/products/GetCategoryUseCase";
import { CreateOrderUseCase } from "@/application/use-cases/orders/CreateOrderUseCase";
import { GetOrderUseCase } from "@/application/use-cases/orders/GetOrderUseCase";

// --- Repositorios (implementaciones concretas) ---
// Decorator pattern: HttpProductRepository → CachedProductRepository
// El Use Case no sabe que hay caché — solo ve IProductRepository
const productRepository = new CachedProductRepository(new HttpProductRepository());
const orderRepository = new HttpOrderRepository();

// --- Use Cases (expuestos al resto de la app) ---
export const getProductsUseCase = new GetProductsUseCase(productRepository);
export const getProductUseCase = new GetProductUseCase(productRepository);
export const getCategoriesUseCase = new GetCategoriesUseCase(productRepository);
export const getCategoryUseCase = new GetCategoryUseCase(productRepository);
export const createOrderUseCase = new CreateOrderUseCase(orderRepository);
export const getOrderUseCase = new GetOrderUseCase(orderRepository);
