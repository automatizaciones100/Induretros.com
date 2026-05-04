"use client";

import Image from "next/image";
import { ArrowRight } from "lucide-react";
import { resolveImageUrl } from "@/lib/imageUrl";

interface Props {
  label?: string | null;
  title?: string | null;
  subtitle?: string | null;
  ctaText?: string | null;
  ctaUrl?: string | null;
  cta2Text?: string | null;
  cta2Url?: string | null;
  imageUrl?: string | null;
}

/**
 * Reproducción fiel del hero del home, alimentado por el form del admin
 * para que marketing pueda ver los cambios antes de guardar.
 */
export default function HeroPreview({
  label,
  title,
  subtitle,
  ctaText,
  ctaUrl,
  cta2Text,
  cta2Url,
  imageUrl,
}: Props) {
  const heroLabel = label || "Importadores directos";
  const heroTitle = title || "Repuestos para Excavadoras Hidráulicas";
  const heroSubtitle = subtitle || "Más de 9 años importando directamente los mejores repuestos para maquinaria pesada.";
  const heroCtaText = ctaText || "Ver catálogo";
  const heroCta2Text = cta2Text || "Cotizar por WhatsApp";
  const heroImage = imageUrl?.trim() || "/noshadow-excabadora-768x576.webp";
  const resolved = resolveImageUrl(heroImage) || "/noshadow-excabadora-768x576.webp";

  return (
    <div className="rounded-xl overflow-hidden border border-gray-200">
      <section className="bg-gradient-to-br from-dark-2 to-dark text-white py-10 px-6">
        <div className="flex flex-col md:flex-row items-center gap-6">
          <div className="flex-1 text-center md:text-left">
            {heroLabel && (
              <p className="text-primary font-semibold font-sans text-xs uppercase tracking-widest mb-2">
                {heroLabel}
              </p>
            )}
            <h2 className="font-heading text-xl md:text-3xl font-semibold text-white uppercase leading-tight mb-3">
              {heroTitle}
            </h2>
            {heroSubtitle && (
              <p className="font-sans text-gray-400 text-sm mb-5 max-w-md">
                {heroSubtitle}
              </p>
            )}
            <div className="flex flex-col sm:flex-row gap-2 justify-center md:justify-start">
              {heroCtaText && (
                <span className="btn-primary text-xs px-5 py-2 inline-flex items-center justify-center gap-1.5 cursor-default select-none">
                  {heroCtaText}
                  <ArrowRight size={14} />
                </span>
              )}
              {heroCta2Text && (
                <span className="btn-secondary text-xs px-5 py-2 border-gray-600 text-gray-300 inline-flex items-center justify-center cursor-default select-none">
                  {heroCta2Text}
                </span>
              )}
            </div>
            {/* URLs en pequeñito como referencia, NO clickables */}
            <div className="mt-3 text-[10px] text-gray-500 font-mono space-y-0.5 hidden sm:block">
              {ctaUrl && <p>1 → {ctaUrl}</p>}
              {cta2Url && <p>2 → {cta2Url}</p>}
            </div>
          </div>
          <div className="flex-1 hidden md:flex justify-center">
            <div className="relative w-full max-w-xs aspect-[4/3]">
              <Image
                src={resolved}
                alt="hero preview"
                fill
                className="object-contain"
                sizes="200px"
                unoptimized
              />
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
