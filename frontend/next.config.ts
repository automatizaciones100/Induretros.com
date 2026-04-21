import type { NextConfig } from "next";

/**
 * Los security headers (incluyendo CSP con nonce) los gestiona src/middleware.ts.
 * next.config.ts solo controla la configuración del servidor de Next.js.
 */
const nextConfig: NextConfig = {
  images: {
    remotePatterns: [
      { protocol: "https", hostname: "induretros.com" },
      { protocol: "http", hostname: "localhost" },
    ],
  },
};

export default nextConfig;
