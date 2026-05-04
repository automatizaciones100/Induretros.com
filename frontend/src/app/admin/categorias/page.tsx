"use client";

import {
  Plus,
  Edit3,
  Trash2,
  Save,
  X,
  Loader2,
  AlertCircle,
  FolderTree,
} from "lucide-react";
import { categoriesApi } from "@/lib/api/adminCrud";
import type { Category } from "@/lib/api/types";
import { useAdminCrud } from "@/hooks/useAdminCrud";

interface FormState {
  name: string;
  slug: string;
  description: string;
  image_url: string;
  parent_id: string; // string para el select; "" = sin padre
}

const EMPTY: FormState = { name: "", slug: "", description: "", image_url: "", parent_id: "" };

const slugify = (s: string) =>
  s.toLowerCase()
    .normalize("NFD")
    .replace(/[̀-ͯ]/g, "")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-|-$/g, "")
    .slice(0, 100);

export default function AdminCategoriasPage() {
  const crud = useAdminCrud<Category, FormState>({
    api: categoriesApi,
    emptyForm: EMPTY,
    toFormState: (cat) => ({
      name: cat.name,
      slug: cat.slug,
      description: cat.description ?? "",
      image_url: cat.image_url ?? "",
      parent_id: cat.parent_id ? String(cat.parent_id) : "",
    }),
    toPayload: (f) => ({
      name: f.name.trim(),
      slug: f.slug.trim(),
      description: f.description.trim() || null,
      image_url: f.image_url.trim() || null,
      parent_id: f.parent_id ? Number(f.parent_id) : null,
    }),
    describeForDelete: (cat) => `la categoría "${cat.name}"`,
    loadErrorMessage: "Error cargando categorías",
  });

  const {
    list, loading, error,
    editing, showForm, form, setForm, saving, formError,
    startNew, startEdit, cancelForm, handleSubmit, handleDelete,
  } = crud;

  return (
    <div className="p-6 lg:p-8 max-w-6xl mx-auto">
      {/* Encabezado */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
          <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">
            Categorías
          </h1>
          <p className="text-sm text-gray-mid font-sans mt-1">
            {list.length} categorías en total
          </p>
        </div>
        {!showForm && (
          <button onClick={startNew} className="btn-primary">
            <Plus size={16} />
            Nueva categoría
          </button>
        )}
      </div>

      {/* Formulario */}
      {showForm && (
        <form
          onSubmit={handleSubmit}
          className="bg-white border border-gray-100 rounded-xl p-6 mb-6 space-y-4"
        >
          <div className="flex items-center justify-between mb-2">
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
              {editing ? `Editar: ${editing.name}` : "Nueva categoría"}
            </h2>
            <button
              type="button"
              onClick={cancelForm}
              className="text-gray-light hover:text-red-500 p-1"
              aria-label="Cancelar"
            >
              <X size={18} />
            </button>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                Nombre *
              </label>
              <input
                type="text"
                required
                minLength={2}
                maxLength={100}
                value={form.name}
                onChange={(e) => {
                  const v = e.target.value;
                  setForm((f) => ({
                    ...f,
                    name: v,
                    slug: !editing && (!f.slug || f.slug === slugify(f.name)) ? slugify(v) : f.slug,
                  }));
                }}
                className="input-field"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                Slug *
              </label>
              <input
                type="text"
                required
                pattern="^[a-z0-9]+(?:-[a-z0-9]+)*$"
                maxLength={100}
                value={form.slug}
                onChange={(e) => setForm((f) => ({ ...f, slug: e.target.value }))}
                className="input-field font-mono text-xs"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
              Descripción
            </label>
            <textarea
              rows={2}
              maxLength={1000}
              value={form.description}
              onChange={(e) => setForm((f) => ({ ...f, description: e.target.value }))}
              className="input-field resize-none"
            />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                URL de imagen
              </label>
              <input
                type="text"
                maxLength={500}
                value={form.image_url}
                onChange={(e) => setForm((f) => ({ ...f, image_url: e.target.value }))}
                placeholder="/static/images/categoria.jpg"
                className="input-field font-mono text-xs"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                Categoría padre
              </label>
              <select
                value={form.parent_id}
                onChange={(e) => setForm((f) => ({ ...f, parent_id: e.target.value }))}
                className="input-field"
              >
                <option value="">Sin padre (raíz)</option>
                {list
                  .filter((c) => c.id !== editing?.id) // no permitir auto-padre
                  .map((c) => (
                    <option key={c.id} value={c.id}>
                      {c.name}
                    </option>
                  ))}
              </select>
            </div>
          </div>

          {formError && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700 font-sans flex items-start gap-2">
              <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
              {formError}
            </div>
          )}

          <div className="flex gap-3 pt-2">
            <button
              type="submit"
              disabled={saving}
              className="btn-primary disabled:opacity-60 disabled:cursor-not-allowed"
            >
              {saving ? <Loader2 size={14} className="animate-spin" /> : <Save size={14} />}
              {editing ? "Guardar cambios" : "Crear categoría"}
            </button>
            <button type="button" onClick={cancelForm} className="btn-secondary">
              Cancelar
            </button>
          </div>
        </form>
      )}

      {/* Tabla */}
      <div className="bg-white border border-gray-100 rounded-xl overflow-hidden">
        {loading && list.length === 0 ? (
          <div className="p-12 text-center">
            <Loader2 size={24} className="animate-spin text-gray-light mx-auto mb-3" />
            <p className="text-gray-mid font-sans text-sm">Cargando…</p>
          </div>
        ) : error ? (
          <div className="p-6 flex items-start gap-2 text-red-700 bg-red-50 text-sm font-sans">
            <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
            {error}
          </div>
        ) : list.length === 0 ? (
          <div className="p-12 text-center">
            <FolderTree size={32} className="text-gray-light mx-auto mb-3" />
            <p className="text-gray-mid font-sans">No hay categorías. Crea la primera.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm font-sans">
              <thead className="bg-bg-light text-xs uppercase tracking-wide text-gray-mid">
                <tr>
                  <th className="text-left px-4 py-3">Nombre</th>
                  <th className="text-left px-4 py-3 hidden md:table-cell">Slug</th>
                  <th className="text-left px-4 py-3 hidden lg:table-cell">Padre</th>
                  <th className="text-right px-4 py-3 w-24">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {list.map((c) => {
                  const parent = c.parent_id ? list.find((p) => p.id === c.parent_id) : null;
                  return (
                    <tr key={c.id} className="hover:bg-bg-light/50">
                      <td className="px-4 py-3">
                        <p className="font-medium text-dark">{c.name}</p>
                        {c.description && (
                          <p className="text-xs text-gray-light line-clamp-1 mt-0.5">
                            {c.description}
                          </p>
                        )}
                      </td>
                      <td className="px-4 py-3 hidden md:table-cell text-gray-mid font-mono text-xs">
                        {c.slug}
                      </td>
                      <td className="px-4 py-3 hidden lg:table-cell">
                        {parent ? (
                          <span className="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">
                            {parent.name}
                          </span>
                        ) : (
                          <span className="text-gray-light italic text-xs">Raíz</span>
                        )}
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-1 justify-end">
                          <button
                            onClick={() => startEdit(c)}
                            className="p-1.5 text-gray-mid hover:text-primary hover:bg-primary/5 rounded"
                            title="Editar"
                          >
                            <Edit3 size={15} />
                          </button>
                          <button
                            onClick={() => handleDelete(c)}
                            className="p-1.5 text-gray-mid hover:text-red-600 hover:bg-red-50 rounded"
                            title="Eliminar"
                          >
                            <Trash2 size={15} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
