"use client";

import { useEffect, useRef, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { ChevronDown, User, Package, LogOut, ShieldCheck } from "lucide-react";
import { useAuthStore } from "@/stores/authStore";

/**
 * Menú de usuario en el TopBar.
 * - Anónimo: "Iniciar sesión" + "Crear cuenta"
 * - Autenticado: dropdown con Mi cuenta, Pedidos, Panel admin (si aplica), Cerrar sesión
 *
 * Hidrata desde localStorage; antes de hidratar muestra el estado anónimo
 * para evitar parpadeo SSR mismatch.
 */
export default function UserMenu() {
  const router = useRouter();
  const isAuthenticated = useAuthStore((s) => s.isAuthenticated);
  const isAdmin = useAuthStore((s) => s.isAdmin);
  const email = useAuthStore((s) => s.email());
  const logout = useAuthStore((s) => s.logout);

  const [hydrated, setHydrated] = useState(false);
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => setHydrated(true), []);

  useEffect(() => {
    if (!open) return;
    const onClick = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
    };
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") setOpen(false);
    };
    document.addEventListener("mousedown", onClick);
    document.addEventListener("keydown", onKey);
    return () => {
      document.removeEventListener("mousedown", onClick);
      document.removeEventListener("keydown", onKey);
    };
  }, [open]);

  const handleLogout = () => {
    logout();
    setOpen(false);
    router.replace("/");
  };

  // Estado anónimo o pre-hidratación
  if (!hydrated || !isAuthenticated()) {
    return (
      <div className="flex items-center gap-3 ml-2 border-l border-gray-600 pl-3">
        <Link href="/login" className="top-bar-link hover:text-primary">
          Iniciar sesión
        </Link>
        <Link href="/registro" className="top-bar-link hover:text-primary hidden sm:inline">
          Crear cuenta
        </Link>
      </div>
    );
  }

  // Estado autenticado: dropdown
  const initial = (email?.[0] ?? "U").toUpperCase();
  const display = email?.split("@")[0] ?? "cuenta";

  return (
    <div className="relative ml-2 border-l border-gray-600 pl-3" ref={ref}>
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        className="top-bar-link hover:text-primary flex items-center gap-1.5"
        aria-haspopup="menu"
        aria-expanded={open}
      >
        <span className="w-5 h-5 rounded-full bg-primary text-white flex items-center justify-center text-[10px] font-semibold uppercase">
          {initial}
        </span>
        <span className="hidden sm:inline max-w-[120px] truncate">{display}</span>
        <ChevronDown size={12} className={`transition-transform ${open ? "rotate-180" : ""}`} />
      </button>

      {open && (
        <div
          role="menu"
          className="absolute right-0 top-full mt-1.5 w-52 bg-white border border-gray-200 rounded-lg shadow-lg py-1 z-50 text-dark-2"
        >
          <div className="px-4 py-2 border-b border-gray-100">
            <p className="text-xs text-gray-light font-sans uppercase tracking-wide">Sesión</p>
            <p className="text-sm font-sans truncate" title={email ?? ""}>
              {email}
            </p>
          </div>

          <Link
            href="/mi-cuenta"
            onClick={() => setOpen(false)}
            className="flex items-center gap-2.5 px-4 py-2 text-sm font-sans hover:bg-bg-light"
            role="menuitem"
          >
            <User size={14} className="text-gray-mid" />
            Mi cuenta
          </Link>
          <Link
            href="/mi-cuenta"
            onClick={() => setOpen(false)}
            className="flex items-center gap-2.5 px-4 py-2 text-sm font-sans hover:bg-bg-light"
            role="menuitem"
          >
            <Package size={14} className="text-gray-mid" />
            Mis pedidos
          </Link>

          {isAdmin() && (
            <Link
              href="/admin"
              onClick={() => setOpen(false)}
              className="flex items-center gap-2.5 px-4 py-2 text-sm font-sans hover:bg-bg-light text-primary"
              role="menuitem"
            >
              <ShieldCheck size={14} />
              Panel admin
            </Link>
          )}

          <button
            type="button"
            onClick={handleLogout}
            className="w-full flex items-center gap-2.5 px-4 py-2 text-sm font-sans hover:bg-red-50 hover:text-red-600 border-t border-gray-100 text-left"
            role="menuitem"
          >
            <LogOut size={14} />
            Cerrar sesión
          </button>
        </div>
      )}
    </div>
  );
}
