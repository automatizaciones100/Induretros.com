/**
 * API client de autenticación: login, registro, login con Google.
 * NO usa authFetch (no hay token todavía). Todas las llamadas son fetch raw.
 * Endpoints en backend/app/presentation/routers/auth.py.
 */
import { extractErrorMessage, readJsonOrEmpty } from "./errors";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

export interface TokenResponse {
  access_token: string;
  token_type: string;
}

async function postJson<T>(
  path: string,
  payload: object,
  fallbackError: string,
): Promise<T> {
  const res = await fetch(`${API_URL}${path}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  if (!res.ok) {
    const body = await readJsonOrEmpty(res);
    throw new Error(extractErrorMessage(body, fallbackError));
  }
  return res.json();
}

export function login(payload: {
  email: string;
  password: string;
  cf_turnstile_response?: string;
}): Promise<TokenResponse> {
  return postJson("/api/auth/login", payload, "Credenciales incorrectas");
}

export function register(payload: {
  name: string;
  email: string;
  password: string;
  phone?: string | null;
  cf_turnstile_response?: string;
}): Promise<{ id: number; email: string; name: string }> {
  return postJson("/api/auth/register", payload, "No se pudo crear la cuenta");
}

export function loginWithGoogle(credential: string): Promise<TokenResponse> {
  return postJson("/api/auth/google", { credential }, "Error al iniciar sesión con Google");
}
