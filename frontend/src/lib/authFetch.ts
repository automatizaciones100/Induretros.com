/**
 * Wrapper de fetch que añade Authorization: Bearer <token> automáticamente.
 * Si recibe 401, llama a logout() — el provider de auth se encarga del redirect.
 */
"use client";

import { useAuthStore } from "@/stores/authStore";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

export async function authFetch(
  path: string,
  init: RequestInit = {}
): Promise<Response> {
  const token = useAuthStore.getState().token;

  const headers = new Headers(init.headers);
  if (token) headers.set("Authorization", `Bearer ${token}`);
  if (!headers.has("Content-Type") && init.body) {
    headers.set("Content-Type", "application/json");
  }

  const res = await fetch(`${API_URL}${path}`, { ...init, headers });

  if (res.status === 401) {
    useAuthStore.getState().logout();
  }
  return res;
}
