import Link from "next/link";
import Image from "next/image";
import { Search, Phone } from "lucide-react";
import TopBar from "./TopBar";
import Navbar from "./Navbar";
import CartIcon from "@/components/cart/CartIcon";

export default function Header() {
  return (
    <header className="sticky top-0 z-50 w-full shadow-sm">
      <TopBar />

      {/* Barra principal: Logo + Búsqueda + Carrito */}
      <div className="bg-white py-3">
        <div className="container mx-auto flex items-center justify-between gap-4">
          {/* Logo */}
          <Link href="/" className="flex-shrink-0">
            <Image
              src="/Logo-nuevo-pagina-web.png"
              alt="Induretros - Repuestos para Excavadoras Hidráulicas"
              width={200}
              height={60}
              priority
              className="h-12 w-auto object-contain"
            />
          </Link>

          {/* Buscador */}
          <div className="flex-1 max-w-2xl hidden md:flex">
            <div className="flex w-full">
              <input
                type="search"
                placeholder="Buscar repuestos, referencias, marcas..."
                className="flex-1 border border-r-0 border-gray-300 rounded-l px-4 py-2.5 text-sm font-sans text-dark
                           focus:outline-none focus:border-primary placeholder:text-gray-light"
              />
              <button
                className="bg-primary hover:bg-secondary text-white px-5 py-2.5 rounded-r transition-colors duration-200 flex items-center gap-2"
                aria-label="Buscar"
              >
                <Search size={18} />
                <span className="text-sm font-semibold hidden lg:inline">Buscar</span>
              </button>
            </div>
          </div>

          {/* Teléfono + Carrito */}
          <div className="flex items-center gap-4">
            <a
              href="tel:+573007192973"
              className="hidden xl:flex items-center gap-2 text-dark-2 hover:text-primary transition-colors"
            >
              <div className="bg-primary rounded-full p-2">
                <Phone size={16} className="text-white" />
              </div>
              <div className="flex flex-col">
                <span className="text-xs text-gray-light font-sans leading-tight">Llámanos</span>
                <span className="text-sm font-semibold font-sans leading-tight">300 719 2973</span>
              </div>
            </a>

            <CartIcon />
          </div>
        </div>
      </div>

      <Navbar />
    </header>
  );
}
