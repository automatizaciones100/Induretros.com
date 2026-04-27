"use client";

import { useEffect, useState } from "react";
import { useRouter, usePathname } from "next/navigation";
import { useAuthStore } from "@/stores/authStore";
import { Loader2, ShieldOff } from "lucide-react";

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const isAdmin = useAuthStore((s) => s.isAdmin);

  const [hydrated, setHydrated] = useState(false);
  useEffect(() => setHydrated(true), []);

  // Redirige al login si no hay sesión válida
  useEffect(() => {
    if (!hydrated) return;
    if (!isAuthenticated()) {
      router.replace(`/login?next=${encodeURIComponent(pathname)}`);
    }
  }, [hydrated, isAuthenticated, router, pathname]);

  if (!hydrated) {
    return (
      <div className="container mx-auto py-16 text-center">
        <Loader2 size={28} className="animate-spin text-gray-light mx-auto mb-3" />
        <p className="text-gray-mid font-sans text-sm">Verificando sesión…</p>
      </div>
    );
  }

  if (!isAuthenticated()) {
    // Sin sesión — el useEffect ya disparó el redirect, mostramos placeholder
    return (
      <div className="container mx-auto py-16 text-center">
        <p className="text-gray-mid font-sans">Redirigiendo al login…</p>
      </div>
    );
  }

  if (!isAdmin()) {
    return (
      <div className="container mx-auto py-16">
        <div className="max-w-md mx-auto text-center">
          <div className="w-16 h-16 rounded-full bg-red-50 mx-auto flex items-center justify-center mb-4">
            <ShieldOff size={32} className="text-red-500" />
          </div>
          <h1 className="font-heading text-xl font-semibold text-dark-2 uppercase mb-2">
            Acceso denegado
          </h1>
          <p className="text-gray-mid font-sans mb-6">
            Esta sección requiere permisos de administrador.
          </p>
          <button
            onClick={() => {
              useAuthStore.getState().logout();
              router.replace("/login");
            }}
            className="btn-secondary"
          >
            Cerrar sesión y volver
          </button>
        </div>
      </div>
    );
  }

  return <>{children}</>;
}
