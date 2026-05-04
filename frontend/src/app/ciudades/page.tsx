import type { Metadata } from "next";
import Link from "next/link";
import { MapPin, ArrowRight } from "lucide-react";

export const metadata: Metadata = {
  title: "Ciudades con cobertura — Induretros",
  description:
    "Despachamos repuestos para excavadoras hidráulicas y maquinaria pesada a Medellín, Bogotá, Cali, Barranquilla, Bucaramanga, Cúcuta, Pereira, Montería y todo el país.",
};

const CITIES: { slug: string; name: string }[] = [
  { slug: "medellin", name: "Medellín" },
  { slug: "bogota", name: "Bogotá" },
  { slug: "cali", name: "Cali" },
  { slug: "barranquilla", name: "Barranquilla" },
  { slug: "bucaramanga", name: "Bucaramanga" },
  { slug: "cucuta", name: "Cúcuta" },
  { slug: "pereira", name: "Pereira" },
  { slug: "monteria", name: "Montería" },
];

export default function CiudadesIndexPage() {
  return (
    <div className="container mx-auto py-12 px-4">
      <nav className="text-sm text-gray-light mb-6 font-sans">
        <Link href="/" className="hover:text-primary">Inicio</Link>
        <span className="mx-2">/</span>
        <span className="text-dark">Ciudades</span>
      </nav>

      <div className="max-w-3xl mx-auto text-center mb-10">
        <div className="w-14 h-14 rounded-full bg-primary/10 mx-auto flex items-center justify-center mb-4">
          <MapPin size={28} className="text-primary" />
        </div>
        <h1 className="section-title">Cobertura nacional</h1>
        <p className="section-subtitle">
          Despachamos repuestos a toda Colombia. Estas son las ciudades principales que servimos.
        </p>
      </div>

      <div className="max-w-4xl mx-auto grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        {CITIES.map((c) => (
          <Link
            key={c.slug}
            href={`/ciudades/${c.slug}`}
            className="bg-white rounded-xl border border-gray-100 p-5 text-center hover:border-primary hover:shadow-md transition-all group"
          >
            <MapPin size={20} className="text-primary mx-auto mb-2" />
            <p className="font-heading text-sm font-semibold text-dark-2 uppercase mb-1">
              {c.name}
            </p>
            <p className="text-xs text-gray-mid font-sans inline-flex items-center gap-1 group-hover:text-primary">
              Ver detalle <ArrowRight size={10} />
            </p>
          </Link>
        ))}
      </div>

      <p className="text-center text-sm text-gray-mid font-sans mt-10">
        ¿Eres de otra ciudad o municipio?{" "}
        <Link href="/contacto" className="text-primary hover:underline font-semibold">
          Escríbenos
        </Link>{" "}
        — despachamos a toda Colombia.
      </p>
    </div>
  );
}
