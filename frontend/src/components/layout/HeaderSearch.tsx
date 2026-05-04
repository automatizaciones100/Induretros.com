"use client";

import { FormEvent, useState, useEffect } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { Search, X } from "lucide-react";

/**
 * Input de búsqueda del Header. Submit lleva a /repuestos?buscar=...
 * Si el usuario está en /repuestos, sincroniza el input con el querystring
 * para que la URL sea la fuente de verdad.
 */
export default function HeaderSearch() {
  const router = useRouter();
  const params = useSearchParams();
  const [value, setValue] = useState(params.get("buscar") ?? "");

  // Mantén el input sincronizado al navegar entre páginas
  useEffect(() => {
    setValue(params.get("buscar") ?? "");
  }, [params]);

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    const term = value.trim();
    if (!term) {
      router.push("/repuestos");
      return;
    }
    const search = new URLSearchParams({ buscar: term });
    router.push(`/repuestos?${search.toString()}`);
  };

  const clear = () => {
    setValue("");
    router.push("/repuestos");
  };

  return (
    <form onSubmit={handleSubmit} className="flex w-full" role="search">
      <div className="relative flex-1">
        <input
          type="search"
          name="buscar"
          value={value}
          onChange={(e) => setValue(e.target.value)}
          placeholder="Buscar repuestos, referencias, marcas..."
          aria-label="Buscar productos"
          className="w-full border border-r-0 border-gray-300 rounded-l px-4 py-2.5 pr-9 text-sm font-sans text-dark
                     focus:outline-none focus:border-primary placeholder:text-gray-light"
        />
        {value && (
          <button
            type="button"
            onClick={clear}
            aria-label="Limpiar búsqueda"
            className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-light hover:text-dark-2 p-1"
          >
            <X size={14} />
          </button>
        )}
      </div>
      <button
        type="submit"
        className="bg-primary hover:bg-secondary text-white px-5 py-2.5 rounded-r transition-colors duration-200 flex items-center gap-2"
        aria-label="Buscar"
      >
        <Search size={18} />
        <span className="text-sm font-semibold hidden lg:inline">Buscar</span>
      </button>
    </form>
  );
}
