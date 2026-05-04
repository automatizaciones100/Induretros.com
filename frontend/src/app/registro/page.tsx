"use client";

import { useState, useEffect, FormEvent, useCallback } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";
import { Lock, Mail, User, Phone, Loader2, AlertCircle, UserPlus } from "lucide-react";
import { useAuthStore } from "@/stores/authStore";
import GoogleSignInButton from "@/components/auth/GoogleSignInButton";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

export default function RegistroPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const setToken = useAuthStore((s) => s.setToken);
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);

  const [hydrated, setHydrated] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => setHydrated(true), []);

  // Si ya hay sesión, no tiene sentido estar en /registro
  useEffect(() => {
    if (!hydrated) return;
    if (isAuthenticated()) {
      const next = searchParams.get("next") ?? "/mi-cuenta";
      router.replace(next);
    }
  }, [hydrated, isAuthenticated, router, searchParams]);

  const handleSubmit = async (e: FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setError(null);
    setSubmitting(true);

    const fd = new FormData(e.currentTarget);
    const payload = {
      name: String(fd.get("name") || "").trim(),
      email: String(fd.get("email") || "").trim().toLowerCase(),
      phone: String(fd.get("phone") || "").trim() || null,
      password: String(fd.get("password") || ""),
    };

    try {
      // 1) Crear cuenta
      const regRes = await fetch(`${API_URL}/api/auth/register`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });
      if (!regRes.ok) {
        const body = await regRes.json().catch(() => ({}));
        if (Array.isArray(body.detail)) {
          throw new Error(body.detail.map((e: { msg: string; loc: string[] }) => `${e.loc.slice(-1)[0]}: ${e.msg}`).join(" · "));
        }
        throw new Error(body.detail || "No se pudo crear la cuenta");
      }

      // 2) Login automático para obtener el JWT
      const loginRes = await fetch(`${API_URL}/api/auth/login`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email: payload.email, password: payload.password }),
      });
      if (!loginRes.ok) {
        // Cuenta creada pero login falló → mandamos al login manual
        router.replace("/login?next=/mi-cuenta");
        return;
      }
      const data = await loginRes.json();
      setToken(data.access_token);

      const next = searchParams.get("next") ?? "/mi-cuenta";
      router.replace(next);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error al crear la cuenta");
      setSubmitting(false);
    }
  };

  const handleGoogleSuccess = useCallback(
    (accessToken: string) => {
      setToken(accessToken);
      const next = searchParams.get("next") ?? "/mi-cuenta";
      router.replace(next);
    },
    [router, searchParams, setToken],
  );

  return (
    <div className="container mx-auto py-12">
      <div className="max-w-md mx-auto">
        <div className="text-center mb-8">
          <div className="w-14 h-14 rounded-full bg-primary/10 mx-auto flex items-center justify-center mb-4">
            <UserPlus size={28} className="text-primary" />
          </div>
          <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">
            Crear cuenta
          </h1>
          <p className="text-sm text-gray-mid font-sans mt-2">
            Para ver el historial de tus pedidos y agilizar futuras compras
          </p>
        </div>

        {/* Google Sign-In — recomendado, sin contraseña que recordar */}
        <div className="bg-white border border-gray-100 rounded-xl p-6 mb-4 flex flex-col items-center">
          <GoogleSignInButton
            text="signup_with"
            onSuccess={handleGoogleSuccess}
            onError={(msg) => setError(msg)}
          />
          <p className="text-xs text-gray-light font-sans mt-3 text-center">
            La forma más rápida — sin formulario ni contraseñas
          </p>
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
            <label htmlFor="name" className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
              Nombre completo *
            </label>
            <div className="relative">
              <User size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none" />
              <input
                id="name"
                name="name"
                type="text"
                required
                minLength={2}
                maxLength={100}
                autoComplete="name"
                placeholder="Carlos Ramírez"
                className="input-field pl-10"
              />
            </div>
          </div>

          <div>
            <label htmlFor="email" className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
              Correo *
            </label>
            <div className="relative">
              <Mail size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none" />
              <input
                id="email"
                name="email"
                type="email"
                required
                autoComplete="email"
                placeholder="cliente@empresa.com"
                className="input-field pl-10"
              />
            </div>
          </div>

          <div>
            <label htmlFor="phone" className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
              Teléfono <span className="text-gray-light normal-case">(opcional)</span>
            </label>
            <div className="relative">
              <Phone size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none" />
              <input
                id="phone"
                name="phone"
                type="tel"
                maxLength={30}
                autoComplete="tel"
                placeholder="300 123 4567"
                className="input-field pl-10"
              />
            </div>
            <p className="text-xs text-gray-light font-sans mt-1">
              Útil para que un asesor te contacte por WhatsApp
            </p>
          </div>

          <div>
            <label htmlFor="password" className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
              Contraseña *
            </label>
            <div className="relative">
              <Lock size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none" />
              <input
                id="password"
                name="password"
                type="password"
                required
                minLength={8}
                maxLength={128}
                autoComplete="new-password"
                className="input-field pl-10"
              />
            </div>
            <p className="text-xs text-gray-light font-sans mt-1">
              Mínimo 8 caracteres con al menos 1 letra y 1 número
            </p>
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
                Creando cuenta…
              </>
            ) : (
              "Crear cuenta"
            )}
          </button>

          <p className="text-xs text-gray-light font-sans text-center pt-2">
            Al crear tu cuenta aceptas nuestros{" "}
            <Link href="/terminos" className="text-primary hover:underline">términos</Link>
            {" "}y la{" "}
            <Link href="/privacidad" className="text-primary hover:underline">política de privacidad</Link>.
          </p>
        </form>

        <p className="text-center text-sm text-gray-mid font-sans mt-6">
          ¿Ya tienes cuenta?{" "}
          <Link href="/login" className="text-primary font-semibold hover:underline">
            Iniciar sesión
          </Link>
        </p>
      </div>
    </div>
  );
}
