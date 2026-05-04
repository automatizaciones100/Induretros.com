import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Wrench } from "lucide-react";
import { getLegalPage } from "@/lib/legalPages";
import LandingPageView from "@/components/landing/LandingPageView";

interface PageProps {
  params: Promise<{ slug: string }>;
}

/** Marcas oficialmente soportadas con landing page propia. Mantener en sync
 *  con migrate_landing_pages.py para que los slugs coincidan. */
const BRAND_NAMES: Record<string, string> = {
  caterpillar: "Caterpillar",
  komatsu: "Komatsu",
  kobelco: "Kobelco",
  hyundai: "Hyundai",
  doosan: "Doosan",
  sany: "Sany",
  volvo: "Volvo",
  case: "Case",
  kawasaki: "Kawasaki",
  hitachi: "Hitachi",
  liugong: "LiuGong",
  kato: "Kato",
};

export async function generateMetadata({ params }: PageProps): Promise<Metadata> {
  const { slug } = await params;
  const name = BRAND_NAMES[slug];
  if (!name) return { title: "Marca no encontrada" };
  const page = await getLegalPage(`marca-${slug}`);
  return {
    title: page?.title || `Repuestos ${name} en Colombia — Induretros`,
    description: `Repuestos ${name} para excavadoras y maquinaria pesada importados directamente de fábrica. Filtros, bombas hidráulicas, partes eléctricas, tren de rodaje y más.`,
  };
}

export default async function MarcaPage({ params }: PageProps) {
  const { slug } = await params;
  const name = BRAND_NAMES[slug];
  if (!name) notFound();

  return (
    <LandingPageView
      pageSlug={`marca-${slug}`}
      productsQuery={name}
      icon={Wrench}
      parentLabel="Marcas"
      parentHref="/marcas"
      currentLabel={name}
      subtitle={`Repuestos ${name} importados directamente para excavadoras y maquinaria pesada`}
    />
  );
}
