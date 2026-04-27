"use client";

import { useState } from "react";
import Link from "next/link";
import { ChevronDown, Menu, X } from "lucide-react";

const categories = [
  {
    name: "Accesorios",
    slug: "accesorios-maquinaria-pesada",
    children: ["Abrazaderas", "Chapas", "Espejos", "Medidores", "Vidrios de cabina"],
  },
  {
    name: "Balineras",
    slug: "balineras-para-maquinaria-pesada",
    children: ["Balinera de bomba hidráulica", "Balinera de motor de traslación", "Balinera de transmisión"],
  },
  {
    name: "Filtros",
    slug: "filtros-para-maquinaria-pesada",
    children: ["Filtro de aceite", "Filtro de aire", "Filtro de combustible", "Filtro hidráulico"],
  },
  {
    name: "Partes Hidráulicas",
    slug: "partes-hidraulicas",
    children: ["Bombas hidráulicas", "Cilindros", "Motores hidráulicos", "Válvulas"],
  },
  {
    name: "Partes Eléctricas",
    slug: "partes-electricas",
    children: ["Alternadores", "Arrancadores", "Sensores", "Switches"],
  },
  {
    name: "Piezas de Desgaste",
    slug: "piezas-de-desgaste",
    children: ["Cuchillas", "Dientes de balde", "Orugas", "Rodillos"],
  },
];

export default function Navbar() {
  const [mobileOpen, setMobileOpen] = useState(false);
  const [activeDropdown, setActiveDropdown] = useState<string | null>(null);

  return (
    <nav className="bg-white border-b border-gray-100 relative z-50">
      <div className="container mx-auto flex items-center justify-between h-14">
        {/* Links principales desktop */}
        <div className="hidden lg:flex items-center gap-1">
          <Link href="/" className="px-4 py-2 text-dark font-sans font-medium text-sm hover:text-primary transition-colors">
            Inicio
          </Link>

          {/* Mega menú Tienda */}
          <div
            className="relative group"
            onMouseEnter={() => setActiveDropdown("tienda")}
            onMouseLeave={() => setActiveDropdown(null)}
          >
            <button className="flex items-center gap-1 px-4 py-2 text-dark font-sans font-medium text-sm hover:text-primary transition-colors">
              Tienda
              <ChevronDown size={14} className="group-hover:rotate-180 transition-transform duration-200" />
            </button>

            {activeDropdown === "tienda" && (
              <div className="absolute top-full left-0 bg-white shadow-xl border border-gray-100 rounded-b-lg p-6 grid grid-cols-3 gap-6 w-[640px]">
                {categories.map((cat) => (
                  <div key={cat.slug}>
                    <Link
                      href={`/repuestos?categoria=${cat.slug}`}
                      className="font-heading font-semibold text-dark-2 text-sm uppercase hover:text-primary block mb-2"
                    >
                      {cat.name}
                    </Link>
                    <ul className="space-y-1">
                      {cat.children.map((child) => (
                        <li key={child}>
                          <Link
                            href={`/repuestos?categoria=${cat.slug}`}
                            className="text-xs text-gray-mid hover:text-primary transition-colors"
                          >
                            {child}
                          </Link>
                        </li>
                      ))}
                    </ul>
                  </div>
                ))}
              </div>
            )}
          </div>

          <Link href="/nosotros" className="px-4 py-2 text-dark font-sans font-medium text-sm hover:text-primary transition-colors">
            Nosotros
          </Link>
          <Link href="/blog" className="px-4 py-2 text-dark font-sans font-medium text-sm hover:text-primary transition-colors">
            Blog
          </Link>
          <Link href="/contacto" className="px-4 py-2 text-dark font-sans font-medium text-sm hover:text-primary transition-colors">
            Contáctanos
          </Link>
        </div>

        {/* Botón móvil */}
        <button
          className="lg:hidden p-2 text-dark-2"
          onClick={() => setMobileOpen(!mobileOpen)}
          aria-label="Menú"
        >
          {mobileOpen ? <X size={22} /> : <Menu size={22} />}
        </button>
      </div>

      {/* Menú móvil */}
      {mobileOpen && (
        <div className="lg:hidden bg-white border-t border-gray-100 py-4 px-4">
          <div className="flex flex-col gap-1">
            {["Inicio", "Tienda", "Nosotros", "Blog", "Contáctanos"].map((item) => (
              <Link
                key={item}
                href={item === "Inicio" ? "/" : `/${item.toLowerCase().replace("á", "a").replace("é", "e")}`}
                className="py-2.5 px-3 text-dark font-sans font-medium text-sm hover:text-primary hover:bg-bg-light rounded transition-colors"
                onClick={() => setMobileOpen(false)}
              >
                {item}
              </Link>
            ))}
          </div>
        </div>
      )}
    </nav>
  );
}
