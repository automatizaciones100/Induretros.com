import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

/**
 * Genera un nonce criptográfico por request y lo inyecta en:
 * 1. El header Content-Security-Policy — reemplaza 'unsafe-inline' con 'nonce-{nonce}'
 * 2. El header x-nonce — para que el layout lo lea y lo pase a Next.js / scripts inline
 *
 * Sin nonce: cualquier <script> inline podría ejecutarse (XSS).
 * Con nonce: solo los scripts que tengan el atributo nonce={nonce} se ejecutan.
 */
export function proxy(request: NextRequest) {
  const nonce = Buffer.from(crypto.getRandomValues(new Uint8Array(16))).toString("base64");

  const apiUrl = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";
  const isDev = process.env.NODE_ENV === "development";

  const csp = [
    "default-src 'self'",
    // En dev: 'unsafe-eval' requerido por React para reconstruir callstacks en el overlay de errores.
    // En producción React nunca usa eval(), así que se omite.
    // Cloudflare Turnstile necesita su dominio en script-src.
    `script-src 'self' 'nonce-${nonce}' https://challenges.cloudflare.com${isDev ? " 'unsafe-eval'" : ""}`,
    // Estilos: 'unsafe-inline' necesario para Tailwind CSS
    "style-src 'self' 'unsafe-inline'",
    "img-src 'self' https://induretros.com data:",
    "font-src 'self'",
    // Turnstile hace fetch a su API desde el iframe y el script
    `connect-src 'self' ${apiUrl} https://challenges.cloudflare.com`,
    "form-action 'self'",
    // Turnstile renderiza un iframe desde challenges.cloudflare.com
    "frame-src https://challenges.cloudflare.com",
    "frame-ancestors 'none'",
  ].join("; ");

  // Pasar el nonce al layout via header de request
  const requestHeaders = new Headers(request.headers);
  requestHeaders.set("x-nonce", nonce);

  const response = NextResponse.next({ request: { headers: requestHeaders } });

  // Aplicar CSP y demás security headers en la response
  response.headers.set("Content-Security-Policy", csp);
  response.headers.set("X-Frame-Options", "DENY");
  response.headers.set("X-Content-Type-Options", "nosniff");
  response.headers.set("Referrer-Policy", "strict-origin-when-cross-origin");
  response.headers.set("Permissions-Policy", "camera=(), microphone=(), geolocation=()");
  response.headers.set("X-XSS-Protection", "1; mode=block");
  response.headers.set("X-Permitted-Cross-Domain-Policies", "none");
  response.headers.set(
    "Strict-Transport-Security",
    "max-age=31536000; includeSubDomains"
  );

  return response;
}

export const config = {
  matcher: [
    /*
     * Aplica a todas las rutas excepto:
     * - _next/static  (archivos estáticos del build)
     * - _next/image   (optimización de imágenes)
     * - favicon.ico
     */
    "/((?!_next/static|_next/image|favicon.ico).*)",
  ],
};
