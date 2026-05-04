import Image from "next/image";
import { Star, Quote } from "lucide-react";
import type { Testimonial } from "@/lib/testimonials";
import { initialsFromName } from "@/lib/testimonials";
import { resolveImageUrl } from "@/lib/imageUrl";

interface Props {
  testimonials: Testimonial[];
}

export default function TestimonialsSection({ testimonials }: Props) {
  if (testimonials.length === 0) return null;

  return (
    <section className="py-14 bg-white">
      <div className="container mx-auto">
        <div className="text-center mb-10">
          <h2 className="section-title">Lo que dicen nuestros clientes</h2>
          <p className="section-subtitle">Empresas y operadores que confían en Induretros</p>
        </div>

        <div
          className="grid gap-6"
          style={{ gridTemplateColumns: `repeat(auto-fit, minmax(280px, 1fr))` }}
        >
          {testimonials.map((t) => (
            <article
              key={t.id}
              className="bg-bg-light rounded-xl p-6 flex flex-col gap-4 relative hover:shadow-md transition-shadow"
            >
              <Quote size={28} className="text-primary opacity-30 absolute top-4 right-4" />

              {/* Estrellas */}
              {t.rating > 0 && (
                <div className="flex gap-0.5 text-primary">
                  {[1, 2, 3, 4, 5].map((n) => (
                    <Star
                      key={n}
                      size={16}
                      className={n <= t.rating ? "fill-current" : "opacity-25"}
                    />
                  ))}
                </div>
              )}

              {/* Comentario */}
              <p className="font-sans text-dark-2 text-sm leading-relaxed flex-1">
                &ldquo;{t.comment}&rdquo;
              </p>

              {/* Cliente */}
              <div className="flex items-center gap-3 pt-3 border-t border-gray-200">
                {t.photo_url ? (
                  <div className="w-12 h-12 rounded-full overflow-hidden bg-gray-200 flex-shrink-0 relative">
                    <Image
                      src={resolveImageUrl(t.photo_url) || t.photo_url}
                      alt={t.client_name}
                      fill
                      className="object-cover"
                      sizes="48px"
                    />
                  </div>
                ) : (
                  <div className="w-12 h-12 rounded-full bg-primary text-white flex-shrink-0 flex items-center justify-center font-heading font-semibold">
                    {initialsFromName(t.client_name)}
                  </div>
                )}
                <div className="min-w-0">
                  <p className="font-sans font-semibold text-dark-2 text-sm truncate">
                    {t.client_name}
                  </p>
                  {t.client_company && (
                    <p className="font-sans text-xs text-gray-mid truncate">
                      {t.client_company}
                    </p>
                  )}
                </div>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
