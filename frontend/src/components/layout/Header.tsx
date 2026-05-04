import Link from "next/link";
import Image from "next/image";
import { Phone } from "lucide-react";
import TopBar from "./TopBar";
import Navbar from "./Navbar";
import HeaderSearch from "./HeaderSearch";
import CartIcon from "@/components/cart/CartIcon";
import { getSiteSettings, telLink } from "@/lib/siteSettings";

export default async function Header() {
  const s = await getSiteSettings();

  return (
    <header className="sticky top-0 z-50 w-full shadow-sm">
      <TopBar />

      {/* Barra principal: Logo + Búsqueda + Carrito */}
      <div className="bg-white py-3">
        <div className="container mx-auto flex items-center justify-between gap-4">
          <Link href="/" className="flex-shrink-0">
            <Image
              src="/Logo-nuevo-pagina-web.png"
              alt={`${s.site_title || "Induretros"} - Repuestos para Excavadoras Hidráulicas`}
              width={200}
              height={60}
              priority
              className="h-12 w-auto object-contain"
            />
          </Link>

          <div className="flex-1 max-w-2xl hidden md:flex">
            <HeaderSearch />
          </div>

          <div className="flex items-center gap-4">
            {s.organization_phone && (
              <a
                href={telLink(s.organization_phone)}
                className="hidden xl:flex items-center gap-2 text-dark-2 hover:text-primary transition-colors"
              >
                <div className="bg-primary rounded-full p-2">
                  <Phone size={16} className="text-white" />
                </div>
                <div className="flex flex-col">
                  <span className="text-xs text-gray-light font-sans leading-tight">Llámanos</span>
                  <span className="text-sm font-semibold font-sans leading-tight">{s.organization_phone}</span>
                </div>
              </a>
            )}

            <CartIcon />
          </div>
        </div>

        {/* Búsqueda en móvil — se muestra debajo del logo en pantallas pequeñas */}
        <div className="container mx-auto md:hidden mt-3 px-4">
          <HeaderSearch />
        </div>
      </div>

      <Navbar />
    </header>
  );
}
