"use client";

import { useAuthStore } from "@/stores/authStore";
import { useRouter } from "next/navigation";
import { LogOut, ShieldCheck } from "lucide-react";

export default function AdminHome() {
  const router = useRouter();
  const email = useAuthStore((s) => s.email());
  const logout = useAuthStore((s) => s.logout);

  const handleLogout = () => {
    logout();
    router.replace("/login");
  };

  return (
    <div className="container mx-auto py-12">
      <div className="max-w-2xl mx-auto bg-white border border-gray-100 rounded-xl p-8">
        <div className="flex items-center gap-3 mb-6">
          <div className="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
            <ShieldCheck size={20} className="text-primary" />
          </div>
          <div>
            <h1 className="font-heading text-xl font-semibold text-dark-2 uppercase">
              Panel de administración
            </h1>
            <p className="text-xs text-gray-mid font-sans">
              Sesión activa: <span className="font-medium">{email}</span>
            </p>
          </div>
        </div>

        <p className="text-sm text-gray-mid font-sans mb-6">
          El dashboard, productos, pedidos y categorías llegarán en los próximos hitos (2.2 a 2.5).
        </p>

        <button onClick={handleLogout} className="btn-secondary">
          <LogOut size={15} />
          Cerrar sesión
        </button>
      </div>
    </div>
  );
}
