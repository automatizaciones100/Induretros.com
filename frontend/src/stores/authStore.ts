/**
 * Estado global de autenticación.
 *
 * - JWT persistido en localStorage (clave: induretros-auth)
 * - Decodifica el payload para exponer email, user_id, is_admin sin extra fetch
 * - El token expira en 30 min (config del backend); si está vencido, isAuthenticated() → false
 */
"use client";

import { create } from "zustand";
import { persist } from "zustand/middleware";

interface JwtPayload {
  sub: string;       // user_id como string
  email: string;
  is_admin: boolean;
  exp: number;       // unix timestamp
}

interface AuthState {
  token: string | null;
  payload: JwtPayload | null;

  setToken: (token: string) => void;
  logout: () => void;

  isAuthenticated: () => boolean;
  isAdmin: () => boolean;
  userId: () => number | null;
  email: () => string | null;
}

function decodeJwt(token: string): JwtPayload | null {
  try {
    const parts = token.split(".");
    if (parts.length !== 3) return null;
    const json = atob(parts[1].replace(/-/g, "+").replace(/_/g, "/"));
    return JSON.parse(json) as JwtPayload;
  } catch {
    return null;
  }
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      token: null,
      payload: null,

      setToken: (token) => {
        const payload = decodeJwt(token);
        set({ token, payload });
      },

      logout: () => set({ token: null, payload: null }),

      isAuthenticated: () => {
        const p = get().payload;
        if (!p) return false;
        // exp está en segundos (unix); Date.now() en ms
        return p.exp * 1000 > Date.now();
      },

      isAdmin: () => {
        return get().isAuthenticated() && (get().payload?.is_admin ?? false);
      },

      userId: () => {
        const p = get().payload;
        return p ? Number(p.sub) : null;
      },

      email: () => get().payload?.email ?? null,
    }),
    {
      name: "induretros-auth",
      version: 1,
    }
  )
);
