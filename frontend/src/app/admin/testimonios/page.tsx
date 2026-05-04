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
  MessageSquareQuote,
  Eye,
  EyeOff,
  Star,
} from "lucide-react";
import { authFetch } from "@/lib/authFetch";

interface Testimonial {
  id: number;
  client_name: string;
  client_company?: string | null;
  comment: string;
  rating: number;
  photo_url?: string | null;
  position: number;
  active: boolean;
}

interface FormState {
  client_name: string;
  client_company: string;
  comment: string;
  rating: number;
  photo_url: string;
  position: number;
  active: boolean;
}

const empty: FormState = {
  client_name: "",
  client_company: "",
  comment: "",
  rating: 5,
  photo_url: "",
  position: 0,
  active: true,
};

export default function AdminTestimoniosPage() {
  const [list, setList] = useState<Testimonial[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [editing, setEditing] = useState<Testimonial | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState<FormState>(empty);
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  const fetchAll = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await authFetch("/api/testimonials/admin/all");
      if (!res.ok) throw new Error(`Error ${res.status}`);
      setList(await res.json());
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error cargando testimonios");
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

  const startEdit = (t: Testimonial) => {
    setEditing(t);
    setForm({
      client_name: t.client_name,
      client_company: t.client_company || "",
      comment: t.comment,
      rating: t.rating,
      photo_url: t.photo_url || "",
      position: t.position,
      active: t.active,
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
      client_name: form.client_name.trim(),
      client_company: form.client_company.trim() || null,
      comment: form.comment.trim(),
      rating: form.rating,
      photo_url: form.photo_url.trim() || null,
      position: form.position,
      active: form.active,
    };

    try {
      const url = editing ? `/api/testimonials/${editing.id}` : "/api/testimonials";
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

  const handleDelete = async (t: Testimonial) => {
    if (!confirm(`¿Eliminar el testimonio de "${t.client_name}"?`)) return;
    try {
      const res = await authFetch(`/api/testimonials/${t.id}`, { method: "DELETE" });
      if (!res.ok && res.status !== 204) throw new Error(`Error ${res.status}`);
      fetchAll();
    } catch (err) {
      alert(err instanceof Error ? err.message : "Error al eliminar");
    }
  };

  const toggleActive = async (t: Testimonial) => {
    try {
      const res = await authFetch(`/api/testimonials/${t.id}`, {
        method: "PUT",
        body: JSON.stringify({ ...t, active: !t.active }),
      });
      if (!res.ok) throw new Error(`Error ${res.status}`);
      fetchAll();
    } catch (err) {
      alert(err instanceof Error ? err.message : "Error al cambiar estado");
    }
  };

  return (
    <div className="p-6 lg:p-8 max-w-5xl mx-auto">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
          <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">Testimonios</h1>
          <p className="text-sm text-gray-mid font-sans mt-1">
            Comentarios de clientes que aparecen en el home (sección &ldquo;Lo que dicen nuestros clientes&rdquo;).
          </p>
        </div>
        {!showForm && (
          <button onClick={startNew} className="btn-primary">
            <Plus size={16} />
            Nuevo testimonio
          </button>
        )}
      </div>

      {/* Form */}
      {showForm && (
        <form onSubmit={handleSubmit} className="bg-white border border-gray-100 rounded-xl p-6 mb-6 space-y-4">
          <div className="flex items-center justify-between mb-2">
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
              {editing ? `Editar testimonio de ${editing.client_name}` : "Nuevo testimonio"}
            </h2>
            <button type="button" onClick={cancelForm} className="text-gray-light hover:text-red-500 p-1">
              <X size={18} />
            </button>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                Nombre del cliente *
              </label>
              <input
                type="text"
                required
                maxLength={100}
                value={form.client_name}
                onChange={(e) => setForm((f) => ({ ...f, client_name: e.target.value }))}
                placeholder="Carlos Ramírez"
                className="input-field"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                Empresa o cargo
              </label>
              <input
                type="text"
                maxLength={100}
                value={form.client_company}
                onChange={(e) => setForm((f) => ({ ...f, client_company: e.target.value }))}
                placeholder="Constructora Andina S.A.S."
                className="input-field"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
              Comentario * <span className="text-gray-light">({form.comment.length}/2000)</span>
            </label>
            <textarea
              required
              rows={4}
              maxLength={2000}
              value={form.comment}
              onChange={(e) => setForm((f) => ({ ...f, comment: e.target.value }))}
              placeholder="Escribe el testimonio aquí..."
              className="input-field resize-none"
            />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                Estrellas
              </label>
              <div className="flex items-center gap-1.5 mt-2">
                {[1, 2, 3, 4, 5].map((n) => (
                  <button
                    key={n}
                    type="button"
                    onClick={() => setForm((f) => ({ ...f, rating: n === f.rating ? n - 1 : n }))}
                    className="text-primary hover:scale-110 transition-transform"
                    aria-label={`${n} estrellas`}
                  >
                    <Star size={22} className={n <= form.rating ? "fill-current" : "opacity-25"} />
                  </button>
                ))}
              </div>
              <p className="text-xs text-gray-light font-sans mt-1">0 = sin estrellas visibles</p>
            </div>
            <div>
              <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                Posición (orden)
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
            <div className="flex items-end">
              <label className="flex items-center gap-2 cursor-pointer pb-2">
                <input
                  type="checkbox"
                  checked={form.active}
                  onChange={(e) => setForm((f) => ({ ...f, active: e.target.checked }))}
                  className="w-4 h-4"
                />
                <span className="text-sm font-sans">Visible en el home</span>
              </label>
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
              Foto del cliente (URL)
            </label>
            <input
              type="text"
              maxLength={500}
              value={form.photo_url}
              onChange={(e) => setForm((f) => ({ ...f, photo_url: e.target.value }))}
              placeholder="/static/images/cliente.jpg o https://…"
              className="input-field font-mono text-xs"
            />
            <p className="text-xs text-gray-light font-sans mt-1">
              Si la dejas vacía se mostrarán las iniciales del nombre como avatar.
            </p>
          </div>

          {formError && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700 font-sans flex items-start gap-2">
              <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
              {formError}
            </div>
          )}

          <div className="flex gap-3 pt-2">
            <button type="submit" disabled={saving} className="btn-primary disabled:opacity-60">
              {saving ? <Loader2 size={14} className="animate-spin" /> : <Save size={14} />}
              {editing ? "Guardar cambios" : "Crear testimonio"}
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
            <MessageSquareQuote size={32} className="text-gray-light mx-auto mb-3" />
            <p className="text-gray-mid font-sans">Aún no hay testimonios. Crea el primero.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm font-sans">
              <thead className="bg-bg-light text-xs uppercase tracking-wide text-gray-mid">
                <tr>
                  <th className="text-center px-3 py-3 w-12">#</th>
                  <th className="text-left px-4 py-3">Cliente</th>
                  <th className="text-left px-4 py-3 hidden md:table-cell">Comentario</th>
                  <th className="text-center px-3 py-3 w-32 hidden sm:table-cell">Rating</th>
                  <th className="text-center px-4 py-3 w-24">Estado</th>
                  <th className="text-right px-4 py-3 w-24">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {list.map((t) => (
                  <tr key={t.id} className={`hover:bg-bg-light/50 ${!t.active ? "opacity-50" : ""}`}>
                    <td className="px-3 py-3 text-center text-gray-mid">{t.position}</td>
                    <td className="px-4 py-3">
                      <p className="font-medium text-dark">{t.client_name}</p>
                      {t.client_company && (
                        <p className="text-xs text-gray-light line-clamp-1">{t.client_company}</p>
                      )}
                    </td>
                    <td className="px-4 py-3 hidden md:table-cell text-dark">
                      <p className="line-clamp-2 text-xs italic">&ldquo;{t.comment}&rdquo;</p>
                    </td>
                    <td className="px-3 py-3 text-center hidden sm:table-cell">
                      <div className="flex justify-center gap-0.5 text-primary">
                        {[1, 2, 3, 4, 5].map((n) => (
                          <Star
                            key={n}
                            size={12}
                            className={n <= t.rating ? "fill-current" : "opacity-20"}
                          />
                        ))}
                      </div>
                    </td>
                    <td className="px-4 py-3 text-center">
                      <button
                        onClick={() => toggleActive(t)}
                        className={`text-xs px-2 py-1 rounded font-semibold inline-flex items-center gap-1 ${
                          t.active ? "bg-green-100 text-green-700" : "bg-gray-100 text-gray-600"
                        }`}
                      >
                        {t.active ? <Eye size={11} /> : <EyeOff size={11} />}
                        {t.active ? "Visible" : "Oculto"}
                      </button>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-1 justify-end">
                        <button
                          onClick={() => startEdit(t)}
                          className="p-1.5 text-gray-mid hover:text-primary hover:bg-primary/5 rounded"
                          title="Editar"
                        >
                          <Edit3 size={15} />
                        </button>
                        <button
                          onClick={() => handleDelete(t)}
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
