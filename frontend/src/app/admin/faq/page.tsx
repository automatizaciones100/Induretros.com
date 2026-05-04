"use client";

import { useEffect, useState, FormEvent } from "react";
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
} from "lucide-react";
import { authFetch } from "@/lib/authFetch";

interface FaqItem {
  id: number;
  question: string;
  answer: string;
  category?: string | null;
  position: number;
  active: boolean;
}

interface FormState {
  question: string;
  answer: string;
  category: string;
  position: number;
  active: boolean;
}

const empty: FormState = { question: "", answer: "", category: "", position: 0, active: true };

const SUGGESTED_CATEGORIES = ["Pedidos", "Pagos", "Envíos", "Productos", "Garantía", "Cuenta"];

export default function AdminFaqPage() {
  const [list, setList] = useState<FaqItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [editing, setEditing] = useState<FaqItem | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState<FormState>(empty);
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  const fetchAll = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await authFetch("/api/faq/admin/all");
      if (!res.ok) throw new Error(`Error ${res.status}`);
      setList(await res.json());
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error cargando FAQ");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAll();
  }, []);

  const startNew = () => {
    setEditing(null);
    setForm({ ...empty, position: list.length + 1 });
    setFormError(null);
    setShowForm(true);
  };

  const startEdit = (item: FaqItem) => {
    setEditing(item);
    setForm({
      question: item.question,
      answer: item.answer,
      category: item.category || "",
      position: item.position,
      active: item.active,
    });
    setFormError(null);
    setShowForm(true);
  };

  const cancelForm = () => {
    setShowForm(false);
    setEditing(null);
    setForm(empty);
    setFormError(null);
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setFormError(null);
    setSaving(true);

    const payload = {
      question: form.question.trim(),
      answer: form.answer.trim(),
      category: form.category.trim() || null,
      position: form.position,
      active: form.active,
    };

    try {
      const url = editing ? `/api/faq/${editing.id}` : "/api/faq";
      const method = editing ? "PUT" : "POST";
      const res = await authFetch(url, { method, body: JSON.stringify(payload) });
      if (!res.ok) {
        const body = await res.json().catch(() => ({}));
        if (Array.isArray(body.detail)) {
          throw new Error(body.detail.map((e: { msg: string; loc: string[] }) => `${e.loc.slice(-1)[0]}: ${e.msg}`).join(" · "));
        }
        throw new Error(body.detail || `Error ${res.status}`);
      }
      cancelForm();
      fetchAll();
    } catch (err) {
      setFormError(err instanceof Error ? err.message : "Error al guardar");
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = async (item: FaqItem) => {
    if (!confirm(`¿Eliminar la pregunta "${item.question.slice(0, 60)}..."?`)) return;
    try {
      const res = await authFetch(`/api/faq/${item.id}`, { method: "DELETE" });
      if (!res.ok && res.status !== 204) throw new Error(`Error ${res.status}`);
      fetchAll();
    } catch (err) {
      alert(err instanceof Error ? err.message : "Error al eliminar");
    }
  };

  const toggleActive = async (item: FaqItem) => {
    try {
      const res = await authFetch(`/api/faq/${item.id}`, {
        method: "PUT",
        body: JSON.stringify({ ...item, active: !item.active }),
      });
      if (!res.ok) throw new Error(`Error ${res.status}`);
      fetchAll();
    } catch (err) {
      alert(err instanceof Error ? err.message : "Error al cambiar estado");
    }
  };

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
