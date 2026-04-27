"use client";

import { useEffect, useState } from "react";
import { useRouter, usePathname } from "next/navigation";
import { useAuthStore } from "@/stores/authStore";
import { Loader2, ShieldOff } from "lucide-react";
import AdminSidebar from "@/components/admin/AdminSidebar";

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const isAdmin = useAuthStore((s) => s.isAdmin);

  const [hydrated, setHydrated] = useState(false);
  useEffect(() => setHydrated(true), []);

  useEffect(() => {
    if (!hydrated) return;
    if (!isAuthenticated()) {
      router.replace(`/login?next=${encodeURIComponent(pathname)}`);
    }
  }, [hydrated, isAuthenticated, router, pathname]);

  if (!hydrated) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <Loader2 size={28} className="animate-spin text-gray-light mx-auto mb-3" />
          <p className="text-gray-mid font-sans text-sm">Verificando sesión…</p>
        </div>
      </div>
    );
  }

  if (!isAuthenticated()) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <p className="text-gray-mid font-sans">Redirigiendo al login…</p>
      </div>
    );
  }

  if (!isAdmin()) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="max-w-md mx-auto text-center px-4">
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

  return (
    <div className="flex min-h-screen bg-bg-light">
      <AdminSidebar />
      <main className="flex-1 overflow-x-hidden">{children}</main>
    </div>
  );
}
