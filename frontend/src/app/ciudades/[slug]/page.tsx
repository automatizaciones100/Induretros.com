import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { MapPin } from "lucide-react";
import { getLegalPage } from "@/lib/legalPages";
import LandingPageView from "@/components/landing/LandingPageView";

interface PageProps {
  params: Promise<{ slug: string }>;
}

/** Ciudades con landing SEO. Mantener en sync con migrate_landing_pages.py. */
const CITY_NAMES: Record<string, string> = {
  medellin: "Medellín",
  bogota: "Bogotá",
  cali: "Cali",
  barranquilla: "Barranquilla",
  bucaramanga: "Bucaramanga",
  cucuta: "Cúcuta",
  pereira: "Pereira",
  monteria: "Montería",
};

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { slug } = await params;
  const name = CITY_NAMES[slug];
  if (!name) return { title: "Ciudad no encontrada" };
  const page = await getLegalPage(`ciudad-${slug}`);
  return {
    title: page?.title || `Repuestos para excavadoras en ${name} — Induretros`,
    description: `Repuestos para excavadoras hidráulicas y maquinaria pesada con envío a ${name} y municipios cercanos. Importadores directos, atención por WhatsApp.`,
  };
}

export default async function CiudadPage({ params }: PageProps) {
  const { slug } = await params;
  const name = CITY_NAMES[slug];
  if (!name) notFound();

  return (
    <LandingPageView
      pageSlug={`ciudad-${slug}`}
      productsQuery={null /* destacados — la ciudad no filtra el catálogo */}
      icon={MapPin}
      parentLabel="Ciudades"
      parentHref="/ciudades"
      currentLabel={name}
      subtitle={`Envío de repuestos para excavadoras y maquinaria pesada a ${name}`}
    />
  );
}
