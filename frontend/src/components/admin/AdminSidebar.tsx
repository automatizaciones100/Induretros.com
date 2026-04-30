"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import {
  LayoutDashboard,
  Package,
  ShoppingBag,
  FolderTree,
  ImageIcon,
  Settings,
  BarChart3,
  Megaphone,
  LogOut,
  Home,
} from "lucide-react";
import { useAuthStore } from "@/stores/authStore";

const NAV = [
  { href: "/admin", label: "Dashboard", icon: LayoutDashboard, exact: true },
  { href: "/admin/productos", label: "Productos", icon: Package },
  { href: "/admin/pedidos", label: "Pedidos", icon: ShoppingBag },
  { href: "/admin/categorias", label: "Categorías", icon: FolderTree },
  { href: "/admin/imagenes", label: "Imágenes", icon: ImageIcon },
  { href: "/admin/estadisticas", label: "Estadísticas home", icon: BarChart3 },
  { href: "/admin/anuncios", label: "Anuncios", icon: Megaphone },
  { href: "/admin/configuracion", label: "Configuración", icon: Settings },
];

export default function AdminSidebar() {
  const pathname = usePathname();
  const router = useRouter();
  const email = useAuthStore((s) => s.email());
  const logout = useAuthStore((s) => s.logout);

  const handleLogout = () => {
    logout();
    router.replace("/login");
  };

  const isActive = (href: string, exact?: boolean) =>
    exact ? pathname === href : pathname === href || pathname.startsWith(`${href}/`);

  return (
    <aside className="bg-dark-2 text-gray-light w-64 flex-shrink-0 flex flex-col min-h-screen sticky top-0">
      {/* Logo / título */}
      <div className="px-6 py-5 border-b border-white/10">
        <Link href="/admin" className="block">
          <p className="font-heading text-xs font-semibold text-primary uppercase tracking-widest">
            Induretros
          </p>
          <p className="font-heading text-base font-semibold text-white uppercase">
            Panel admin
          </p>
        </Link>
      </div>

      {/* Navegación */}
      <nav className="flex-1 py-4 px-3 space-y-1">
        {NAV.map(({ href, label, icon: Icon, exact }) => {
          const active = isActive(href, exact);
          return (
            <Link
              key={href}
              href={href}
              className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-sans font-medium transition-colors ${
                active
                  ? "bg-primary text-white"
                  : "text-gray-400 hover:bg-white/5 hover:text-white"
              }`}
            >
              <Icon size={17} />
              {label}
            </Link>
          );
        })}
      </nav>

      {/* Footer del sidebar: usuario + acciones */}
      <div className="border-t border-white/10 p-3 space-y-1">
        <Link
          href="/"
          className="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-sans text-gray-400 hover:bg-white/5 hover:text-white transition-colors"
        >
          <Home size={14} />
          Ver tienda
        </Link>

        <div className="px-3 py-2">
          <p className="text-xs text-gray-500 font-sans">Conectado como</p>
          <p className="text-xs text-white font-medium font-sans truncate" title={email ?? ""}>
            {email}
          </p>
        </div>

        <button
          onClick={handleLogout}
          className="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-xs font-sans font-medium text-gray-400 hover:bg-red-500/10 hover:text-red-400 transition-colors"
        >
          <LogOut size={14} />
          Cerrar sesión
        </button>
      </div>
    </aside>
  );
}
