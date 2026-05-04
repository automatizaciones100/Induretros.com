"use client";

import { useEffect, useState, FormEvent } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { ArrowLeft, Loader2, Save, AlertCircle } from "lucide-react";
import { authFetch } from "@/lib/authFetch";
import GoogleSerpPreview from "@/components/admin/GoogleSerpPreview";

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || "https://www.induretros.com";

interface Category {
  id: number;
  name: string;
  slug: string;
}

export interface ProductFormData {
  id?: number;
  name?: string;
  slug?: string;
  description?: string | null;
  short_description?: string | null;
  price?: number | null;
  regular_price?: number | null;
  sale_price?: number | null;
  sku?: string | null;
  stock?: number;
  in_stock?: boolean;
  image_url?: string | null;
  category_id?: number | null;
  featured?: boolean;
  meta_title?: string | null;
  meta_description?: string | null;
}

interface Props {
  initial?: ProductFormData;
  mode: "create" | "edit";
}

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

export default function ProductForm({ initial, mode }: Props) {
  const router = useRouter();
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [categories, setCategories] = useState<Category[]>([]);

  // Estado del formulario
  const [form, setForm] = useState<ProductFormData>({
    name: "",
    slug: "",
    description: "",
    short_description: "",
    price: null,
    regular_price: null,
    sale_price: null,
    sku: "",
    stock: 0,
    in_stock: true,
    image_url: "",
    category_id: null,
    featured: false,
    meta_title: "",
    meta_description: "",
    ...initial,
  });

  useEffect(() => {
    // Cargar categorías para el selector
    fetch(`${API_URL}/api/products/categories`)
      .then((r) => r.json())
      .then(setCategories)
      .catch(() => {});
  }, []);

  const update = <K extends keyof ProductFormData>(key: K, value: ProductFormData[K]) => {
    setForm((f) => ({ ...f, [key]: value }));
  };

  const slugify = (s: string) =>
    s
      .toLowerCase()
      .normalize("NFD")
      .replace(/[̀-ͯ]/g, "")
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-|-$/g, "")
      .slice(0, 200);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setSubmitting(true);

    // Construir payload — convertir strings vacíos a null y números válidos
    const payload: Record<string, unknown> = {
      name: form.name?.trim(),
      slug: form.slug?.trim(),
      description: form.description?.trim() || null,
      short_description: form.short_description?.trim() || null,
      sku: form.sku?.trim() || null,
      stock: Number(form.stock) || 0,
      in_stock: !!form.in_stock,
      image_url: form.image_url?.trim() || null,
      category_id: form.category_id ?? null,
      featured: !!form.featured,
      price: form.price === null || form.price === undefined ? null : Number(form.price),
      regular_price: form.regular_price === null || form.regular_price === undefined ? null : Number(form.regular_price),
      sale_price: form.sale_price === null || form.sale_price === undefined ? null : Number(form.sale_price),
      meta_title: form.meta_title?.trim() || null,
      meta_description: form.meta_description?.trim() || null,
    };

    try {
      const url = mode === "create" ? "/api/products" : `/api/products/${initial?.id}`;
      const method = mode === "create" ? "POST" : "PUT";
      const res = await authFetch(url, { method, body: JSON.stringify(payload) });

      if (!res.ok) {
        const body = await res.json().catch(() => ({}));
        // Errores de validación de Pydantic vienen como array
        if (Array.isArray(body.detail)) {
          throw new Error(body.detail.map((e: { msg: string; loc: string[] }) => `${e.loc.slice(-1)[0]}: ${e.msg}`).join(" · "));
        }
        throw new Error(body.detail || `Error ${res.status}`);
      }

      router.push("/admin/productos");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error al guardar");
      setSubmitting(false);
    }
  };

  return (
    <div className="p-6 lg:p-8 max-w-4xl mx-auto">
      <Link
        href="/admin/productos"
        className="inline-flex items-center gap-1.5 text-sm text-gray-mid hover:text-primary mb-4 font-sans"
      >
        <ArrowLeft size={14} />
        Volver al listado
      </Link>

      <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase mb-6">
        {mode === "create" ? "Nuevo producto" : "Editar producto"}
      </h1>

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Datos básicos */}
        <Section title="1. Información básica">
          <Field label="Nombre del producto *">
            <input
              type="text"
              required
              minLength={2}
              maxLength={200}
              value={form.name ?? ""}
              onChange={(e) => {
                update("name", e.target.value);
                // Auto-generar slug si está creando y slug está vacío o coincide con el slug previo
                if (mode === "create" && (!form.slug || form.slug === slugify(form.name ?? ""))) {
                  update("slug", slugify(e.target.value));
                }
              }}
              className="input-field"
            />
          </Field>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Slug (URL) *" hint="Solo minúsculas, números y guiones">
              <input
                type="text"
                required
                pattern="^[a-z0-9]+(?:-[a-z0-9]+)*$"
                value={form.slug ?? ""}
                onChange={(e) => update("slug", e.target.value)}
                className="input-field font-mono text-xs"
              />
            </Field>
            <Field label="SKU / Referencia">
              <input
                type="text"
                maxLength={100}
                value={form.sku ?? ""}
                onChange={(e) => update("sku", e.target.value)}
                className="input-field"
              />
            </Field>
          </div>
          <Field label="Descripción corta" hint="Máximo 500 caracteres — aparece en listados">
            <textarea
              rows={2}
              maxLength={500}
              value={form.short_description ?? ""}
              onChange={(e) => update("short_description", e.target.value)}
              className="input-field resize-none"
            />
          </Field>
          <Field label="Descripción completa" hint="HTML básico permitido — aparece en la ficha del producto">
            <textarea
              rows={6}
              maxLength={50_000}
              value={form.description ?? ""}
              onChange={(e) => update("description", e.target.value)}
              className="input-field resize-y"
            />
          </Field>
        </Section>

        {/* Precio */}
        <Section title="2. Precios">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <Field label="Precio actual *" hint="El que se muestra al cliente">
              <input
                type="number"
                min="0"
                step="100"
                value={form.price ?? ""}
                onChange={(e) => update("price", e.target.value === "" ? null : Number(e.target.value))}
                className="input-field"
              />
            </Field>
            <Field label="Precio regular" hint="Precio sin descuento (opcional)">
              <input
                type="number"
                min="0"
                step="100"
                value={form.regular_price ?? ""}
                onChange={(e) => update("regular_price", e.target.value === "" ? null : Number(e.target.value))}
                className="input-field"
              />
            </Field>
            <Field label="Precio de oferta" hint="Si está en oferta">
              <input
                type="number"
                min="0"
                step="100"
                value={form.sale_price ?? ""}
                onChange={(e) => update("sale_price", e.target.value === "" ? null : Number(e.target.value))}
                className="input-field"
              />
            </Field>
          </div>
        </Section>

        {/* Inventario */}
        <Section title="3. Inventario">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Cantidad en stock">
              <input
                type="number"
                min="0"
                value={form.stock ?? 0}
                onChange={(e) => update("stock", Number(e.target.value))}
                className="input-field"
              />
            </Field>
            <div className="flex items-end">
              <label className="flex items-center gap-2 cursor-pointer pb-2.5">
                <input
                  type="checkbox"
                  checked={!!form.in_stock}
                  onChange={(e) => update("in_stock", e.target.checked)}
                  className="w-4 h-4"
                />
                <span className="text-sm font-sans">Disponible para vender</span>
              </label>
            </div>
          </div>
        </Section>

        {/* Imagen y categoría */}
        <Section title="4. Categoría e imagen">
          <Field label="Categoría">
            <select
              value={form.category_id ?? ""}
              onChange={(e) =>
                update("category_id", e.target.value === "" ? null : Number(e.target.value))
              }
              className="input-field"
            >
              <option value="">Sin categoría</option>
              {categories.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}
                </option>
              ))}
            </select>
          </Field>
          <Field label="URL de imagen" hint="https://… o /static/images/SKU.jpg">
            <input
              type="text"
              maxLength={500}
              value={form.image_url ?? ""}
              onChange={(e) => update("image_url", e.target.value)}
              placeholder="/static/images/FLT-001.jpg"
              className="input-field font-mono text-xs"
            />
          </Field>
        </Section>

        {/* SEO */}
        <Section title="5. SEO (cómo se ve en Google)">
          <p className="text-xs text-gray-light font-sans -mt-2">
            Si dejas estos campos vacíos, Google usará el nombre y la descripción corta automáticamente.
          </p>

          <Field
            label={`Meta título (${form.meta_title?.length ?? 0}/70)`}
            hint="Lo que Google muestra como título azul. Recomendado entre 50-60 caracteres."
          >
            <input
              type="text"
              maxLength={70}
              value={form.meta_title ?? ""}
              onChange={(e) => update("meta_title", e.target.value)}
              placeholder={form.name || "Se usará el nombre del producto"}
              className="input-field"
            />
          </Field>

          <Field
            label={`Meta descripción (${form.meta_description?.length ?? 0}/160)`}
            hint="Lo que Google muestra debajo del título. Recomendado entre 120-155 caracteres."
          >
            <textarea
              rows={3}
              maxLength={200}
              value={form.meta_description ?? ""}
              onChange={(e) => update("meta_description", e.target.value)}
              placeholder={form.short_description || "Se usará la descripción corta"}
              className="input-field resize-none"
            />
          </Field>

          {/* Preview en vivo de cómo se verá en Google */}
          <div className="pt-2">
            <GoogleSerpPreview
              url={`${SITE_URL}/producto/${form.slug || "ejemplo"}`}
              title={form.meta_title?.trim() || form.name || ""}
              description={form.meta_description?.trim() || form.short_description || ""}
            />
          </div>
        </Section>

        {/* Visibilidad */}
        <Section title="6. Visibilidad">
          <label className="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              checked={!!form.featured}
              onChange={(e) => update("featured", e.target.checked)}
              className="w-4 h-4"
            />
            <span className="text-sm font-sans">
              Producto destacado <span className="text-xs text-gray-light">(aparece en home)</span>
            </span>
          </label>
        </Section>

        {error && (
          <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700 font-sans flex items-start gap-2">
            <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
            {error}
          </div>
        )}

        {/* Acciones */}
        <div className="flex gap-3 pt-4">
          <button
            type="submit"
            disabled={submitting}
            className="btn-primary disabled:opacity-60 disabled:cursor-not-allowed"
          >
            {submitting ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />}
            {mode === "create" ? "Crear producto" : "Guardar cambios"}
          </button>
          <Link href="/admin/productos" className="btn-secondary">
            Cancelar
          </Link>
        </div>
      </form>
    </div>
  );
}

// ─── Subcomponentes ───
function Section({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <section className="bg-white border border-gray-100 rounded-xl p-6 space-y-4">
      <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">{title}</h2>
      {children}
    </section>
  );
}

function Field({
  label,
  hint,
  children,
}: {
  label: string;
  hint?: string;
  children: React.ReactNode;
}) {
  return (
    <div>
      <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
        {label}
      </label>
      {children}
      {hint && <p className="text-xs text-gray-light font-sans mt-1">{hint}</p>}
    </div>
  );
}
