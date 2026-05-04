import type { Metadata } from "next";
import Link from "next/link";
import { Wrench, ArrowRight } from "lucide-react";

export const metadata: Metadata = {
  title: "Marcas — Repuestos para excavadoras",
  description:
    "Repuestos para excavadoras y maquinaria pesada de las marcas Caterpillar, Komatsu, Kobelco, Hyundai, Doosan, Sany, Volvo, Case, Hitachi y más. Importadores directos en Colombia.",
};

const BRANDS: { slug: string; name: string }[] = [
  { slug: "caterpillar", name: "Caterpillar" },
  { slug: "komatsu", name: "Komatsu" },
  { slug: "kobelco", name: "Kobelco" },
  { slug: "hyundai", name: "Hyundai" },
  { slug: "doosan", name: "Doosan" },
  { slug: "sany", name: "Sany" },
  { slug: "volvo", name: "Volvo" },
  { slug: "case", name: "Case" },
  { slug: "kawasaki", name: "Kawasaki" },
  { slug: "hitachi", name: "Hitachi" },
  { slug: "liugong", name: "LiuGong" },
  { slug: "kato", name: "Kato" },
];

export default function MarcasIndexPage() {
  return (
    <div className="container mx-auto py-12 px-4">
      <nav className="text-sm text-gray-light mb-6 font-sans">
        <Link href="/" className="hover:text-primary">Inicio</Link>
        <span className="mx-2">/</span>
        <span className="text-dark">Marcas</span>
      </nav>

      <div className="max-w-3xl mx-auto text-center mb-10">
        <div className="w-14 h-14 rounded-full bg-primary/10 mx-auto flex items-center justify-center mb-4">
          <Wrench size={28} className="text-primary" />
        </div>
        <h1 className="section-title">Marcas que importamos</h1>
        <p className="section-subtitle">
          Repuestos originales y compatibles para las principales marcas de excavadoras y maquinaria pesada
        </p>
      </div>

      <div className="max-w-5xl mx-auto grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        {BRANDS.map((b) => (
          <Link
            key={b.slug}
            href={`/marcas/${b.slug}`}
            className="bg-white rounded-xl border border-gray-100 p-6 text-center hover:border-primary hover:shadow-md transition-all group"
          >
            <p className="font-heading text-base font-semibold text-dark-2 uppercase tracking-wide mb-2">
              {b.name}
            </p>
            <p className="text-xs text-gray-mid font-sans inline-flex items-center gap-1 group-hover:text-primary">
              Ver repuestos <ArrowRight size={11} />
            </p>
          </Link>
        ))}
      </div>

      <p className="text-center text-sm text-gray-mid font-sans mt-10">
        ¿No ves tu marca?{" "}
        <Link href="/contacto" className="text-primary hover:underline font-semibold">
          Escríbenos
        </Link>{" "}
        — manejamos referencias para más fabricantes.
      </p>
    </div>
  );
}
