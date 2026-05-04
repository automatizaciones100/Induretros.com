"use client";

import {
  Plus,
  Edit3,
  Trash2,
  Save,
  X,
  Loader2,
  AlertCircle,
  HelpCircle,
  Eye,
  EyeOff,
  ChevronDown,
} from "lucide-react";
import { faqApi } from "@/lib/api/adminCrud";
import type { FaqItem } from "@/lib/api/types";
import { useAdminCrud } from "@/hooks/useAdminCrud";

interface FormState {
  question: string;
  answer: string;
  category: string;
  position: number;
  active: boolean;
}

const EMPTY: FormState = { question: "", answer: "", category: "", position: 0, active: true };

const SUGGESTED_CATEGORIES = ["Pedidos", "Pagos", "Envíos", "Productos", "Garantía", "Cuenta"];

export default function AdminFaqPage() {
  const crud = useAdminCrud<FaqItem, FormState>({
    api: faqApi,
    emptyForm: EMPTY,
    toFormState: (item) => ({
      question: item.question,
      answer: item.answer,
      category: item.category || "",
      position: item.position,
      active: item.active,
    }),
    toPayload: (f) => ({
      question: f.question.trim(),
      answer: f.answer.trim(),
      category: f.category.trim() || null,
      position: f.position,
      active: f.active,
    }),
    startNewOverrides: (list) => ({ position: list.length + 1 }),
    describeForDelete: (item) => `la pregunta "${item.question.slice(0, 60)}..."`,
    loadErrorMessage: "Error cargando FAQ",
  });

  const {
    list, loading, error,
    editing, showForm, form, setForm, saving, formError,
    startNew, startEdit, cancelForm, handleSubmit, handleDelete, toggleActive,
  } = crud;

  // Categorías que ya existen + sugeridas, sin duplicados
  const existingCategories = [...new Set(list.map((f) => f.category).filter(Boolean) as string[])];
  const allCategories = [...new Set([...SUGGESTED_CATEGORIES, ...existingCategories])];

  return (
    <div className="p-6 lg:p-8 max-w-5xl mx-auto">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
          <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">
            Preguntas frecuentes
          </h1>
          <p className="text-sm text-gray-mid font-sans mt-1">
            Aparecen en /faq agrupadas por categoría con accordion expandible.
          </p>
        </div>
        {!showForm && (
          <button onClick={startNew} className="btn-primary">
            <Plus size={16} />
            Nueva pregunta
          </button>
        )}
      </div>

      {/* Form */}
      {showForm && (
        <form onSubmit={handleSubmit} className="bg-white border border-gray-100 rounded-xl p-6 mb-6 space-y-4">
          <div className="flex items-center justify-between mb-2">
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
              {editing ? "Editar pregunta" : "Nueva pregunta"}
            </h2>
            <button type="button" onClick={cancelForm} className="text-gray-light hover:text-red-500 p-1">
              <X size={18} />
            </button>
          </div>

          <div>
            <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
              Pregunta * <span className="text-gray-light">({form.question.length}/200)</span>
            </label>
            <input
              type="text"
              required
              minLength={3}
              maxLength={200}
              value={form.question}
              onChange={(e) => setForm((f) => ({ ...f, question: e.target.value }))}
              placeholder="¿Hacen envíos a toda Colombia?"
              className="input-field"
            />
          </div>

          <div>
            <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
              Respuesta * <span className="text-gray-light">({form.answer.length}/5000)</span>
            </label>
            <textarea
              required
              minLength={3}
              maxLength={5000}
              rows={5}
              value={form.answer}
              onChange={(e) => setForm((f) => ({ ...f, answer: e.target.value }))}
              placeholder="Sí, enviamos a todas las ciudades del país..."
              className="input-field resize-none"
            />
            <p className="text-xs text-gray-light font-sans mt-1">
              HTML permitido: &lt;strong&gt;, &lt;em&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;, &lt;a&gt;, &lt;br&gt;, &lt;p&gt;
            </p>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div className="sm:col-span-2">
              <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                Categoría (opcional)
              </label>
              <input
                type="text"
                list="faq-cats"
                maxLength={50}
                value={form.category}
                onChange={(e) => setForm((f) => ({ ...f, category: e.target.value }))}
                placeholder="Ej. Envíos, Pagos, Pedidos..."
                className="input-field"
              />
              <datalist id="faq-cats">
                {allCategories.map((c) => <option key={c} value={c} />)}
              </datalist>
            </div>
            <div>
              <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                Posición
              </label>
              <input
                type="number"
                min="0"
                max="999"
                value={form.position}
                onChange={(e) => setForm((f) => ({ ...f, position: Number(e.target.value) }))}
                className="input-field"
              />
            </div>
          </div>

          <label className="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              checked={form.active}
              onChange={(e) => setForm((f) => ({ ...f, active: e.target.checked }))}
              className="w-4 h-4"
            />
            <span className="text-sm font-sans">Visible en /faq</span>
          </label>

          {formError && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700 font-sans flex items-start gap-2">
              <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
              {formError}
            </div>
          )}

          <div className="flex gap-3 pt-2">
            <button type="submit" disabled={saving} className="btn-primary disabled:opacity-60">
              {saving ? <Loader2 size={14} className="animate-spin" /> : <Save size={14} />}
              {editing ? "Guardar cambios" : "Crear pregunta"}
            </button>
            <button type="button" onClick={cancelForm} className="btn-secondary">
              Cancelar
            </button>
          </div>

          {/* Vista previa antes de guardar */}
          <div className="pt-4 border-t border-gray-100">
            <p className="text-xs uppercase tracking-wide text-gray-mid font-sans mb-2">
              Vista previa (cómo se verá en /faq)
            </p>
            <div className="bg-bg-light rounded-xl p-4">
              {form.category.trim() && (
                <h3 className="font-heading text-base font-semibold uppercase tracking-wide text-primary mb-3">
                  {form.category.trim()}
                </h3>
              )}
              <article className="bg-white border border-primary rounded-xl shadow-sm">
                <div className="w-full flex items-center justify-between gap-4 px-5 py-4">
                  <span className="font-heading font-semibold text-dark-2 text-base leading-snug">
                    {form.question.trim() || "Aquí aparecerá la pregunta…"}
                  </span>
                  <ChevronDown size={20} className="text-primary flex-shrink-0 rotate-180" />
                </div>
                <div className="px-5 pb-5 -mt-1">
                  <div
                    className="font-sans text-sm text-gray-mid leading-relaxed prose prose-sm max-w-none"
                    dangerouslySetInnerHTML={{
                      __html: (form.answer.trim() || "<em>Aquí aparecerá la respuesta…</em>").replace(/\n/g, "<br/>"),
                    }}
                  />
                </div>
              </article>
              <p className="text-xs text-gray-light font-sans mt-2">
                Los tags HTML no permitidos (script, iframe…) se eliminan automáticamente al guardar.
              </p>
            </div>
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
            <HelpCircle size={32} className="text-gray-light mx-auto mb-3" />
            <p className="text-gray-mid font-sans">Sin preguntas. Crea la primera.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm font-sans">
              <thead className="bg-bg-light text-xs uppercase tracking-wide text-gray-mid">
                <tr>
                  <th className="text-center px-3 py-3 w-12">#</th>
                  <th className="text-left px-4 py-3 hidden sm:table-cell w-32">Categoría</th>
                  <th className="text-left px-4 py-3">Pregunta</th>
                  <th className="text-center px-4 py-3 w-24">Estado</th>
                  <th className="text-right px-4 py-3 w-24">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {list.map((f) => (
                  <tr key={f.id} className={`hover:bg-bg-light/50 ${!f.active ? "opacity-50" : ""}`}>
                    <td className="px-3 py-3 text-center text-gray-mid">{f.position}</td>
                    <td className="px-4 py-3 hidden sm:table-cell">
                      {f.category ? (
                        <span className="text-xs bg-primary/10 text-primary px-2 py-1 rounded font-semibold">
                          {f.category}
                        </span>
                      ) : (
                        <span className="text-gray-light italic text-xs">Sin categoría</span>
                      )}
                    </td>
                    <td className="px-4 py-3">
                      <p className="font-medium text-dark line-clamp-1">{f.question}</p>
                      <p className="text-xs text-gray-light line-clamp-1 mt-0.5">{f.answer.replace(/<[^>]*>/g, "")}</p>
                    </td>
                    <td className="px-4 py-3 text-center">
                      <button
                        onClick={() => toggleActive(f)}
                        className={`text-xs px-2 py-1 rounded font-semibold inline-flex items-center gap-1 ${
                          f.active ? "bg-green-100 text-green-700" : "bg-gray-100 text-gray-600"
                        }`}
                      >
                        {f.active ? <Eye size={11} /> : <EyeOff size={11} />}
                        {f.active ? "Visible" : "Oculto"}
                      </button>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-1 justify-end">
                        <button
                          onClick={() => startEdit(f)}
                          className="p-1.5 text-gray-mid hover:text-primary hover:bg-primary/5 rounded"
                          title="Editar"
                        >
                          <Edit3 size={15} />
                        </button>
                        <button
                          onClick={() => handleDelete(f)}
                          className="p-1.5 text-gray-mid hover:text-red-600 hover:bg-red-50 rounded"
                          title="Eliminar"
                        >
                          <Trash2 size={15} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
