"use client";

import { useState, useEffect, FormEvent, useCallback } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";
import { Lock, Mail, Loader2, AlertCircle, ShieldCheck } from "lucide-react";
import { useAuthStore } from "@/stores/authStore";
import GoogleSignInButton from "@/components/auth/GoogleSignInButton";
import { login as loginApi } from "@/lib/api/auth";

export default function LoginPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const setToken = useAuthStore((s) => s.setToken);
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const isAdmin = useAuthStore((s) => s.isAdmin);

  const [hydrated, setHydrated] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => setHydrated(true), []);

  // Si ya hay sesión válida, redirigir directo
  useEffect(() => {
    if (!hydrated) return;
    if (isAuthenticated()) {
      const next = searchParams.get("next") ?? (isAdmin() ? "/admin" : "/mi-cuenta");
      router.replace(next);
    }
  }, [hydrated, isAuthenticated, isAdmin, router, searchParams]);

  const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setError(null);
    setSubmitting(true);

    const fd = new FormData(e.currentTarget);
    const payload = {
      email: String(fd.get("email") || "").trim(),
      password: String(fd.get("password") || ""),
    };

    try {
      const data = await loginApi(payload);
      setToken(data.access_token);

      // Decodificar el token nuevo para saber a dónde redirigir
      let isAdminToken = false;
      try {
        const parts = data.access_token.split(".");
        if (parts.length === 3) {
          const decoded = JSON.parse(atob(parts[1].replace(/-/g, "+").replace(/_/g, "/")));
          isAdminToken = decoded?.is_admin ?? false;
        }
      } catch {
        /* ignore decode errors → defaults a cliente */
      }
      const next = searchParams.get("next") ?? (isAdminToken ? "/admin" : "/mi-cuenta");
      router.replace(next);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error al iniciar sesión");
      setSubmitting(false);
    }
  };

  const handleGoogleSuccess = useCallback(
    (accessToken: string) => {
      setToken(accessToken);
      let isAdminToken = false;
      try {
        const parts = accessToken.split(".");
        if (parts.length === 3) {
          const payload = JSON.parse(atob(parts[1].replace(/-/g, "+").replace(/_/g, "/")));
          isAdminToken = payload?.is_admin ?? false;
        }
      } catch {
        /* ignore */
      }
      const next = searchParams.get("next") ?? (isAdminToken ? "/admin" : "/mi-cuenta");
      router.replace(next);
    },
    [router, searchParams, setToken],
  );

  return (
    <div className="container mx-auto py-16">
      <div className="max-w-md mx-auto">
        <div className="text-center mb-8">
          <div className="w-14 h-14 rounded-full bg-primary/10 mx-auto flex items-center justify-center mb-4">
            <ShieldCheck size={28} className="text-primary" />
          </div>
          <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">
            Iniciar sesión
          </h1>
          <p className="text-sm text-gray-mid font-sans mt-2">
            Accede a tu cuenta para ver tus pedidos
          </p>
        </div>

        {/* Google Sign-In — preferido para clientes nuevos */}
        <div className="bg-white border border-gray-100 rounded-xl p-6 mb-4 flex flex-col items-center">
          <GoogleSignInButton
            text="signin_with"
            onSuccess={handleGoogleSuccess}
            onError={(msg) => setError(msg)}
          />
        </div>

        {/* Separador "ó con tu correo" */}
        <div className="flex items-center gap-3 my-3 text-xs text-gray-light font-sans uppercase tracking-wide">
          <span className="flex-1 h-px bg-gray-200" />
          <span>ó con tu correo</span>
          <span className="flex-1 h-px bg-gray-200" />
        </div>

        <form
          onSubmit={handleSubmit}
          className="bg-white border border-gray-100 rounded-xl p-6 space-y-4"
        >
          <div>
            <label
              htmlFor="email"
              className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans"
            >
              Correo
            </label>
            <div className="relative">
              <Mail
                size={16}
                className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none"
              />
              <input
                id="email"
                name="email"
                type="email"
                required
                autoComplete="email"
                placeholder="admin@induretros.com"
                className="input-field pl-10"
              />
            </div>
          </div>

          <div>
            <label
              htmlFor="password"
              className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans"
            >
              Contraseña
            </label>
            <div className="relative">
              <Lock
                size={16}
                className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none"
              />
              <input
                id="password"
                name="password"
                type="password"
                required
                autoComplete="current-password"
                className="input-field pl-10"
              />
            </div>
          </div>

          {error && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700 font-sans flex items-start gap-2">
              <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
              {error}
            </div>
          )}

          <button
            type="submit"
            disabled={submitting}
            className="btn-primary w-full justify-center py-3 disabled:opacity-60 disabled:cursor-not-allowed"
          >
            {submitting ? (
              <>
                <Loader2 size={16} className="animate-spin" />
                Iniciando…
              </>
            ) : (
              "Iniciar sesión"
            )}
          </button>
        </form>

        <p className="text-center text-sm text-gray-mid font-sans mt-6">
          ¿No tienes cuenta?{" "}
          <Link href="/registro" className="text-primary font-semibold hover:underline">
            Crear una cuenta
          </Link>
        </p>
        <p className="text-center text-xs text-gray-light font-sans mt-3">
          ¿Problemas para acceder?{" "}
          <Link href="/contacto" className="text-primary hover:underline">
            Contáctanos
          </Link>
        </p>
      </div>
    </div>
  );
}
