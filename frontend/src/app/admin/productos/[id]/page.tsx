"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { Loader2 } from "lucide-react";
import ProductForm, { ProductFormData } from "@/components/admin/ProductForm";
import { authFetch } from "@/lib/authFetch";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

export default function EditarProductoPage() {
  const { id } = useParams<{ id: string }>();
  const [data, setData] = useState<ProductFormData | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    // El endpoint público GET /api/products/{slug} es por slug, no por id.
    // Usamos el listado y filtramos — alternativa: añadir GET /api/products/by-id/{id}
    // Para edición, hacemos fetch directo al detalle por slug — primero buscamos el slug.
    authFetch(`/api/products?per_page=100`)
      .then(async (r) => {
        if (!r.ok) throw new Error(`Error ${r.status}`);
        return r.json();
      })
      .then((list: { items: { id: number; slug: string }[] }) => {
        const found = list.items.find((p) => p.id === Number(id));
        if (!found) throw new Error("Producto no encontrado");
        return fetch(`${API_URL}/api/products/${found.slug}`).then((r) => r.json());
      })
      .then((p: ProductFormData & { category?: { id: number } | null }) => {
        // Aplanar category → category_id
        const flat: ProductFormData = {
          ...p,
          category_id: p.category?.id ?? null,
        };
        setData(flat);
      })
      .catch((err) => setError(err instanceof Error ? err.message : "Error"));
  }, [id]);

  if (error) {
    return (
      <div className="p-8 max-w-4xl mx-auto">
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700 font-sans">
          {error}
        </div>
      </div>
    );
  }

  if (!data) {
    return (
      <div className="p-8 flex items-center gap-2 text-gray-mid font-sans">
        <Loader2 size={18} className="animate-spin" />
        Cargando producto…
      </div>
    );
  }

  return <ProductForm mode="edit" initial={data} />;
}
