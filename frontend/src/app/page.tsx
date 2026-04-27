import Link from "next/link";
import { getProductsUseCase, getCategoriesUseCase } from "@/lib/container";
import ProductCard from "@/components/products/ProductCard";
import { ArrowRight, Award, Clock, Users, Package } from "lucide-react";

const categoryIcons: Record<string, string> = {
  "accesorios-maquinaria-pesada": "🔧",
  "balineras-para-maquinaria-pesada": "⚙️",
  "filtros-para-maquinaria-pesada": "🔩",
  "partes-hidraulicas": "💧",
  "partes-electricas": "⚡",
  "piezas-de-desgaste": "🦾",
  "empaquetaduras-para-maquinaria-pesada": "🛡️",
  "lubricantes-para-maquinaria-pesada": "🛢️",
};

export default async function HomePage() {
  const [featuredResult, categories] = await Promise.allSettled([
    getProductsUseCase.execute({ featured: true, per_page: 8 }),
    getCategoriesUseCase.execute(),
  ]);

  const featuredProducts = featuredResult.status === "fulfilled" ? featuredResult.value.items : [];
  const cats = categories.status === "fulfilled" ? categories.value : [];

  return (
    <>
      {/* HERO */}
      <section className="bg-gradient-to-br from-dark-2 to-dark text-white py-16 md:py-24">
        <div className="container mx-auto flex flex-col md:flex-row items-center gap-10">
          <div className="flex-1 text-center md:text-left">
            <p className="text-primary font-semibold font-sans text-sm uppercase tracking-widest mb-3">
              Importadores directos
            </p>
            <h1 className="font-heading text-4xl md:text-5xl lg:text-6xl font-semibold text-white uppercase leading-tight mb-6">
              Repuestos para<br />
              <span className="text-primary">Excavadoras</span><br />
              Hidráulicas
            </h1>
            <p className="font-sans text-gray-400 text-lg mb-8 max-w-lg">
              Más de 9 años importando directamente los mejores repuestos para maquinaria pesada.
              Disponibilidad inmediata y atención personalizada.
            </p>
            <div className="flex flex-col sm:flex-row gap-3 justify-center md:justify-start">
              <Link href="/repuestos" className="btn-primary text-base px-8 py-3.5">
                Ver catálogo
                <ArrowRight size={18} />
              </Link>
              <a href="https://wa.me/573007192973" target="_blank" rel="noopener noreferrer"
                className="btn-secondary text-base px-8 py-3.5 border-gray-600 text-gray-300 hover:text-dark">
                Cotizar por WhatsApp
              </a>
            </div>
          </div>
          <div className="flex-1 hidden md:flex justify-center">
            <div className="relative w-full max-w-md h-80 bg-dark rounded-2xl overflow-hidden">
              <div className="absolute inset-0 flex items-center justify-center text-8xl opacity-20">🏗️</div>
              <div className="absolute inset-0 bg-gradient-to-t from-dark-2/80 to-transparent" />
            </div>
          </div>
        </div>
      </section>

      {/* ESTADÍSTICAS */}
      <section className="bg-primary py-10">
        <div className="container mx-auto grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
          {[
            { icon: <Clock size={28} />, value: "+9", label: "Años de experiencia" },
            { icon: <Package size={28} />, value: "+1200", label: "Referencias disponibles" },
            { icon: <Users size={28} />, value: "+500", label: "Clientes satisfechos" },
            { icon: <Award size={28} />, value: "100%", label: "Garantía de calidad" },
          ].map((stat) => (
            <div key={stat.label} className="text-white">
              <div className="flex justify-center mb-2 opacity-80">{stat.icon}</div>
              <div className="font-heading text-3xl font-semibold">{stat.value}</div>
              <div className="font-sans text-sm opacity-80 mt-1">{stat.label}</div>
            </div>
          ))}
        </div>
      </section>

      {/* CATEGORÍAS */}
      {cats.length > 0 && (
        <section className="py-14 bg-white">
          <div className="container mx-auto">
            <div className="text-center mb-10">
              <h2 className="section-title">Nuestras Categorías</h2>
              <p className="section-subtitle">Encuentra el repuesto que necesitas</p>
            </div>
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
              {cats.map((cat) => (
                <Link
                  key={cat.id}
                  href={`/repuestos?categoria=${cat.slug}`}
                  className="group bg-bg-light hover:bg-primary rounded-xl p-5 flex flex-col items-center text-center
                             transition-all duration-200 hover:shadow-lg hover:-translate-y-1"
                >
                  <span className="text-4xl mb-3">{categoryIcons[cat.slug] || "🔧"}</span>
                  <span className="font-sans font-semibold text-dark-2 text-sm group-hover:text-white transition-colors leading-tight">
                    {cat.name}
                  </span>
                </Link>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* PRODUCTOS DESTACADOS */}
      {featuredProducts.length > 0 && (
        <section className="py-14 bg-bg-soft">
          <div className="container mx-auto">
            <div className="flex items-end justify-between mb-10">
              <div>
                <h2 className="section-title">Productos Destacados</h2>
                <p className="section-subtitle">Los más solicitados por nuestros clientes</p>
              </div>
              <Link href="/repuestos" className="hidden md:flex items-center gap-1.5 text-primary font-semibold text-sm hover:underline">
                Ver todos <ArrowRight size={16} />
              </Link>
            </div>
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-5">
              {featuredProducts.map((product) => (
                <ProductCard key={product.id} product={product} />
              ))}
            </div>
            <div className="text-center mt-8 md:hidden">
              <Link href="/repuestos" className="btn-outline">
                Ver todos los productos <ArrowRight size={16} />
              </Link>
            </div>
          </div>
        </section>
      )}

      {/* CTA WHATSAPP */}
      <section className="bg-dark-2 py-14">
        <div className="container mx-auto text-center">
          <h2 className="font-heading text-3xl md:text-4xl font-semibold text-white uppercase mb-4">
            ¿No encuentras lo que buscas?
          </h2>
          <p className="font-sans text-gray-400 text-lg mb-8 max-w-xl mx-auto">
            Contáctanos directamente y te ayudamos a encontrar el repuesto exacto que necesitas.
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <a
              href="https://wa.me/573007192973"
              target="_blank"
              rel="noopener noreferrer"
              className="btn-primary text-base px-8 py-3.5"
            >
              Escribir por WhatsApp
              <ArrowRight size={18} />
            </a>
            <a href="tel:+573007192973" className="btn-secondary text-base px-8 py-3.5 border-gray-600 text-gray-300 hover:text-dark">
              Llamar ahora: 300 719 2973
            </a>
          </div>
        </div>
      </section>
    </>
  );
}
