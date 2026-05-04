import type { NextConfig } from "next";

/**
 * Los security headers (incluyendo CSP con nonce) los gestiona src/middleware.ts.
 * next.config.ts solo controla la configuración del servidor de Next.js.
 */
const nextConfig: NextConfig = {
  images: {
    // En dev el optimizador se omite porque el backend está en localhost:8000
    // y Next.js 16 bloquea por seguridad las imágenes que resuelven a IPs privadas.
    // En producción el backend estará en un dominio público y el optimizador funciona normal.
    unoptimized: process.env.NODE_ENV === "development",
    remotePatterns: [
      { protocol: "https", hostname: "www.induretros.com" },
      { protocol: "https", hostname: "induretros.com" },
      { protocol: "http", hostname: "localhost", port: "8000" },
      { protocol: "http", hostname: "127.0.0.1", port: "8000" },
    ],
  },
};

export default nextConfig;
