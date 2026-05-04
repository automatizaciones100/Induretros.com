import Link from "next/link";
import Image from "next/image";
import { getProductsUseCase, getCategoriesUseCase } from "@/lib/container";
import ProductCard from "@/components/products/ProductCard";
import { resolveImageUrl } from "@/lib/imageUrl";
import { getSiteSettings, whatsappLink } from "@/lib/siteSettings";
import { getHomeStats } from "@/lib/homeStats";
import { getStatIcon } from "@/lib/statIcon";
import { getActiveTestimonials } from "@/lib/testimonials";
import TestimonialsSection from "@/components/home/TestimonialsSection";
import { getActiveWhyUs } from "@/lib/whyUs";
import WhyUsSection from "@/components/home/WhyUsSection";
import { ArrowRight } from "lucide-react";

const categoryIcons: Record<string, string> = {
  "accesorios": "🔧",
  "balineras": "⚙️",
  "empaquetadura": "🛡️",
  "filtros": "🔩",
  "partes-electricas": "⚡",
  "partes-completas-y-estructura": "🚜",
  "lubricantes": "🛢️",
  "partes-de-motor": "🔥",
  "partes-hidraulicas": "💧",
  "tren-de-rodaje": "🚂",
  "reductores": "🔄",
  "piezas-de-desgaste": "🦾",
  "valvulas-solenoides-y-electrovalvulas": "🎛️",
};

export default async function HomePage() {
  const [featuredResult, categories, settings, statsResult, testimonialsResult, whyUsResult] = await Promise.allSettled([
    getProductsUseCase.execute({ featured: true, per_page: 8 }),
    getCategoriesUseCase.execute(),
    getSiteSettings(),
    getHomeStats(),
    getActiveTestimonials(),
    getActiveWhyUs(),
  ]);

  const featuredProducts = featuredResult.status === "fulfilled" ? featuredResult.value.items : [];
  const cats = categories.status === "fulfilled" ? categories.value : [];
  const s = settings.status === "fulfilled" ? settings.value : {};
  const stats = statsResult.status === "fulfilled" ? statsResult.value : [];
  const testimonials = testimonialsResult.status === "fulfilled" ? testimonialsResult.value : [];
  const whyUs = whyUsResult.status === "fulfilled" ? whyUsResult.value : [];

  // Hero — todos los textos editables desde /admin/configuracion
  const heroLabel = s.hero_label || "Importadores directos";
  const heroTitle = s.hero_title || "Repuestos para Excavadoras Hidráulicas";
  const heroSubtitle = s.hero_subtitle || "Más de 9 años importando directamente los mejores repuestos para maquinaria pesada. Disponibilidad inmediata y atención personalizada.";
  const heroCtaText = s.hero_cta_text || "Ver catálogo";
  const heroCtaUrl = s.hero_cta_url || "/repuestos";
  const heroCta2Text = s.hero_cta2_text || "Cotizar por WhatsApp";
  // 'whatsapp:default' se resuelve al wa.me con el número de la organización
  const heroCta2Url = s.hero_cta2_url === "whatsapp:default"
    ? whatsappLink(s.whatsapp_number)
    : (s.hero_cta2_url || whatsappLink(s.whatsapp_number));
  const heroImage = s.hero_image_url || "/noshadow-excabadora-768x576.webp";
  const heroCta2IsWhatsApp = heroCta2Url.startsWith("https://wa.me/") || s.hero_cta2_url === "whatsapp:default";

  return (
    <>
      {/* HERO */}
      <section className="bg-gradient-to-br from-dark-2 to-dark text-white py-16 md:py-24">
        <div className="container mx-auto flex flex-col md:flex-row items-center gap-10">
          <div className="flex-1 text-center md:text-left">
            {heroLabel && (
              <p className="text-primary font-semibold font-sans text-sm uppercase tracking-widest mb-3">
                {heroLabel}
              </p>
            )}
            <h1 className="font-heading text-4xl md:text-5xl lg:text-6xl font-semibold text-white uppercase leading-tight mb-6">
              {heroTitle}
            </h1>
            {heroSubtitle && (
              <p className="font-sans text-gray-400 text-lg mb-8 max-w-lg">
                {heroSubtitle}
              </p>
            )}
            <div className="flex flex-col sm:flex-row gap-3 justify-center md:justify-start">
              {heroCtaText && (
                <Link href={heroCtaUrl} className="btn-primary text-base px-8 py-3.5">
                  {heroCtaText}
                  <ArrowRight size={18} />
                </Link>
              )}
              {heroCta2Text && (
                <a
                  href={heroCta2Url}
                  {...(heroCta2IsWhatsApp ? { target: "_blank", rel: "noopener noreferrer" } : {})}
                  className="btn-secondary text-base px-8 py-3.5 border-gray-600 text-gray-300 hover:text-dark"
                >
                  {heroCta2Text}
                </a>
              )}
            </div>
          </div>
          <div className="flex-1 hidden md:flex justify-center">
            <div className="relative w-full max-w-lg aspect-[4/3]">
              <Image
                src={resolveImageUrl(heroImage) || "/noshadow-excabadora-768x576.webp"}
                alt={heroTitle}
                fill
                className="object-contain"
                priority
                sizes="(max-width: 768px) 100vw, 50vw"
              />
            </div>
          </div>
        </div>
      </section>

      {/* ESTADÍSTICAS */}
      {stats.length > 0 && (
        <section className="bg-primary py-10">
          <div
            className="container mx-auto grid gap-6 text-center"
            style={{ gridTemplateColumns: `repeat(auto-fit, minmax(180px, 1fr))` }}
          >
            {stats.map((stat) => {
              const Icon = getStatIcon(stat.icon);
              return (
                <div key={stat.id || stat.label} className="text-white">
                  <div className="flex justify-center mb-2 opacity-80">
                    <Icon size={28} />
                  </div>
                  <div className="font-heading text-3xl font-semibold">{stat.value}</div>
                  <div className="font-sans text-sm opacity-80 mt-1">{stat.label}</div>
                </div>
              );
            })}
          </div>
        </section>
      )}

      {/* CATEGORÍAS */}
      {cats.length > 0 && (
        <section className="py-14 bg-white">
          <div className="container mx-auto">
            <div className="text-center mb-10">
              <h2 className="section-title">Compra por categorías</h2>
              <p className="section-subtitle">Encuentra el repuesto que necesitas</p>
            </div>
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
              {cats.map((cat, i) => {
                // Patrón asimétrico que se repite cada 13 cards: 4 normales,
                // 1 ancha + 2 normales, 4 normales, 2 anchas. Todo suma 4 cols por fila.
                const PATTERN = [1, 1, 1, 1, 2, 1, 1, 1, 1, 1, 1, 2, 2];
                const span = PATTERN[i % PATTERN.length];
                return (
                  <Link
                    key={cat.id}
                    href={`/repuestos?categoria=${cat.slug}`}
                    className={`group relative aspect-[4/3] overflow-hidden rounded-lg shadow-sm hover:shadow-xl transition-shadow ${
                      span === 2 ? "lg:col-span-2" : ""
                    }`}
                  >
                    {/* Fondo: imagen real si existe; si no, gradient + emoji */}
                    {cat.image_url ? (
                      <Image
                        src={resolveImageUrl(cat.image_url)!}
                        alt={cat.name}
                        fill
                        className="object-cover group-hover:scale-105 transition-transform duration-500"
                        sizes={span === 2 ? "(max-width: 1024px) 100vw, 50vw" : "(max-width: 1024px) 50vw, 25vw"}
                      />
                    ) : (
                      <div className="absolute inset-0 bg-gradient-to-br from-dark-2 via-dark to-dark-2 flex items-center justify-center">
                        <span className="text-6xl opacity-25">{categoryIcons[cat.slug] || "🔧"}</span>
                      </div>
                    )}

                    {/* Overlay degradado para asegurar legibilidad del título */}
                    <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent" />

                    {/* Ribbon del título — pasa a primary en hover */}
                    <div className="absolute bottom-0 left-0 right-0 bg-dark-2/95 group-hover:bg-primary py-3 px-4 transition-colors duration-300">
                      <span className="font-heading font-semibold text-white text-xs sm:text-sm uppercase tracking-wide block leading-tight">
                        {cat.name}
                      </span>
                    </div>
                  </Link>
                );
              })}
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

      {/* TESTIMONIOS */}
      <TestimonialsSection testimonials={testimonials} />

      {/* POR QUÉ ELEGIRNOS */}
      <WhyUsSection items={whyUs} />

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
