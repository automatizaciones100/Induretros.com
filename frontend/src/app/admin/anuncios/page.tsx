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
  Megaphone,
  Eye,
  EyeOff,
  ExternalLink,
} from "lucide-react";
import { authFetch } from "@/lib/authFetch";

type Theme = "info" | "promo" | "warning" | "success" | "alert" | "dark";

interface Announcement {
  id: number;
  text: string;
  link_url?: string | null;
  link_text?: string | null;
  theme: Theme;
  active: boolean;
  dismissible: boolean;
  expires_at?: string | null;
  priority: number;
}

interface FormState {
  text: string;
  link_url: string;
  link_text: string;
  theme: Theme;
  active: boolean;
  dismissible: boolean;
  expires_at: string;  // 'YYYY-MM-DDTHH:mm' del input datetime-local
  priority: number;
}

const empty: FormState = {
  text: "",
  link_url: "",
  link_text: "",
  theme: "dark",
  active: true,
  dismissible: true,
  expires_at: "",
  priority: 0,
};

const THEME_PREVIEW: Record<Theme, string> = {
  info: "bg-blue-600 text-white",
  promo: "bg-primary text-white",
  warning: "bg-yellow-500 text-dark-2",
  success: "bg-green-600 text-white",
  alert: "bg-red-600 text-white",
  dark: "bg-dark-2 text-white",
};

const THEME_LABELS: Record<Theme, string> = {
  info: "Información (azul)",
  promo: "Promoción (naranja)",
  warning: "Aviso (amarillo)",
  success: "Éxito (verde)",
  alert: "Alerta (rojo)",
  dark: "Neutral (oscuro)",
};

export default function AdminAnunciosPage() {
  const [list, setList] = useState<Announcement[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [editing, setEditing] = useState<Announcement | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState<FormState>(empty);
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  const fetchAll = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await authFetch("/api/announcements/admin/all");
      if (!res.ok) throw new Error(`Error ${res.status}`);
      setList(await res.json());
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error cargando anuncios");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAll();
  }, []);

  const startNew = () => {
    setEditing(null);
    setForm(empty);
    setFormError(null);
    setShowForm(true);
  };

  const startEdit = (a: Announcement) => {
    setEditing(a);
    setForm({
      text: a.text,
      link_url: a.link_url || "",
      link_text: a.link_text || "",
      theme: a.theme,
      active: a.active,
      dismissible: a.dismissible,
      // Convertir ISO a formato datetime-local: 'YYYY-MM-DDTHH:mm'
      expires_at: a.expires_at ? a.expires_at.slice(0, 16) : "",
      priority: a.priority,
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
      text: form.text.trim(),
      link_url: form.link_url.trim() || null,
      link_text: form.link_text.trim() || null,
      theme: form.theme,
      active: form.active,
      dismissible: form.dismissible,
      expires_at: form.expires_at ? new Date(form.expires_at).toISOString() : null,
      priority: form.priority,
    };

    try {
      const url = editing ? `/api/announcements/${editing.id}` : "/api/announcements";
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

  const handleDelete = async (a: Announcement) => {
    if (!confirm(`¿Eliminar el anuncio "${a.text.slice(0, 40)}..."?`)) return;
    try {
      const res = await authFetch(`/api/announcements/${a.id}`, { method: "DELETE" });
      if (!res.ok && res.status !== 204) throw new Error(`Error ${res.status}`);
      fetchAll();
    } catch (err) {
      alert(err instanceof Error ? err.message : "Error al eliminar");
    }
  };

  const toggleActive = async (a: Announcement) => {
    try {
      const res = await authFetch(`/api/announcements/${a.id}`, {
        method: "PUT",
        body: JSON.stringify({
          ...a,
          active: !a.active,
          expires_at: a.expires_at,
        }),
      });
      if (!res.ok) throw new Error(`Error ${res.status}`);
      fetchAll();
    } catch (err) {
      alert(err instanceof Error ? err.message : "Error al cambiar estado");
    }
  };

  const isExpired = (a: Announcement) =>
    a.expires_at ? new Date(a.expires_at) < new Date() : false;

  return (
    <div className="p-6 lg:p-8 max-w-5xl mx-auto">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
          <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">
            Anuncios y banners
          </h1>
          <p className="text-sm text-gray-mid font-sans mt-1">
            Barra delgada que aparece arriba del header. Útil para promos, avisos o noticias.
          </p>
        </div>
        {!showForm && (
          <button onClick={startNew} className="btn-primary">
            <Plus size={16} />
            Nuevo anuncio
          </button>
        )}
      </div>

      {/* Form */}
      {showForm && (
        <form onSubmit={handleSubmit} className="bg-white border border-gray-100 rounded-xl p-6 mb-6 space-y-4">
          <div className="flex items-center justify-between mb-2">
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
              {editing ? "Editar anuncio" : "Nuevo anuncio"}
            </h2>
            <button type="button" onClick={cancelForm} className="text-gray-light hover:text-red-500 p-1">
              <X size={18} />
            </button>
          </div>

          <div>
            <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
              Texto * <span className="text-gray-light">({form.text.length}/300)</span>
            </label>
            <input
              type="text"
              required
              maxLength={300}
              value={form.text}
              onChange={(e) => setForm((f) => ({ ...f, text: e.target.value }))}
              placeholder="¡Envíos gratis hasta el 15 de mayo!"
              className="input-field"
            />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                Enlace (opcional)
              </label>
              <input
                type="text"
                maxLength={300}
                value={form.link_url}
                onChange={(e) => setForm((f) => ({ ...f, link_url: e.target.value }))}
                placeholder="/repuestos o https://…"
                className="input-field font-mono text-xs"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                Texto del enlace
              </label>
              <input
                type="text"
                maxLength={50}
                value={form.link_text}
                onChange={(e) => setForm((f) => ({ ...f, link_text: e.target.value }))}
                placeholder="Ver más"
                className="input-field"
              />
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                Tema (color)
              </label>
              <select
                value={form.theme}
                onChange={(e) => setForm((f) => ({ ...f, theme: e.target.value as Theme }))}
                className="input-field"
              >
                {(Object.keys(THEME_LABELS) as Theme[]).map((t) => (
                  <option key={t} value={t}>{THEME_LABELS[t]}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                Prioridad <span className="text-gray-light">(menor = se muestra primero)</span>
              </label>
              <input
                type="number"
                min="0"
                max="999"
                value={form.priority}
                onChange={(e) => setForm((f) => ({ ...f, priority: Number(e.target.value) }))}
                className="input-field"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
              Expira el (opcional) — vacío = nunca
            </label>
            <input
              type="datetime-local"
              value={form.expires_at}
              onChange={(e) => setForm((f) => ({ ...f, expires_at: e.target.value }))}
              className="input-field"
            />
          </div>

          <div className="flex flex-wrap gap-4">
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={form.active}
                onChange={(e) => setForm((f) => ({ ...f, active: e.target.checked }))}
                className="w-4 h-4"
              />
              <span className="text-sm font-sans">Activo (visible en el sitio)</span>
            </label>
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                checked={form.dismissible}
                onChange={(e) => setForm((f) => ({ ...f, dismissible: e.target.checked }))}
                className="w-4 h-4"
              />
              <span className="text-sm font-sans">El usuario puede cerrarlo (X)</span>
            </label>
          </div>

          {/* Vista previa */}
          {form.text && (
            <div>
              <p className="text-xs text-gray-mid font-sans uppercase tracking-wide mb-2">Vista previa</p>
              <div className={`${THEME_PREVIEW[form.theme]} text-sm font-sans relative rounded`}>
                <div className="container mx-auto py-2 px-4 pr-10 flex items-center justify-center text-center gap-1">
                  <span>{form.text}</span>
                  {form.link_url && form.link_text && (
                    <span className="underline ml-2 font-semibold opacity-90">{form.link_text}</span>
                  )}
                </div>
                {form.dismissible && (
                  <span className="absolute right-3 top-1/2 -translate-y-1/2 opacity-70">
                    <X size={16} />
                  </span>
                )}
              </div>
            </div>
          )}

          {formError && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700 font-sans flex items-start gap-2">
              <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
              {formError}
            </div>
          )}

          <div className="flex gap-3 pt-2">
            <button type="submit" disabled={saving} className="btn-primary disabled:opacity-60">
              {saving ? <Loader2 size={14} className="animate-spin" /> : <Save size={14} />}
              {editing ? "Guardar cambios" : "Crear anuncio"}
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
            <Megaphone size={32} className="text-gray-light mx-auto mb-3" />
            <p className="text-gray-mid font-sans">Sin anuncios. Crea el primero para promos o avisos.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm font-sans">
              <thead className="bg-bg-light text-xs uppercase tracking-wide text-gray-mid">
                <tr>
                  <th className="text-center px-3 py-3 w-16">Pri.</th>
                  <th className="text-left px-3 py-3 w-24">Tema</th>
                  <th className="text-left px-4 py-3">Texto</th>
                  <th className="text-left px-4 py-3 w-32 hidden md:table-cell">Expira</th>
                  <th className="text-center px-4 py-3 w-24">Estado</th>
                  <th className="text-right px-4 py-3 w-24">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {list.map((a) => {
                  const expired = isExpired(a);
                  return (
                    <tr key={a.id} className={`hover:bg-bg-light/50 ${(!a.active || expired) ? "opacity-60" : ""}`}>
                      <td className="px-3 py-3 text-center text-gray-mid">{a.priority}</td>
                      <td className="px-3 py-3">
                        <span className={`inline-block text-xs px-2 py-0.5 rounded font-semibold ${THEME_PREVIEW[a.theme]}`}>
                          {a.theme}
                        </span>
                      </td>
                      <td className="px-4 py-3">
                        <p className="text-dark line-clamp-2">{a.text}</p>
                        {a.link_url && (
                          <a
                            href={a.link_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-xs text-primary hover:underline inline-flex items-center gap-1 mt-1"
                          >
                            <ExternalLink size={11} />
                            {a.link_text || a.link_url}
                          </a>
                        )}
                      </td>
                      <td className="px-4 py-3 hidden md:table-cell text-xs text-gray-mid">
                        {a.expires_at
                          ? new Date(a.expires_at).toLocaleString("es-CO", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit" })
                          : <span className="text-gray-light italic">Nunca</span>}
                        {expired && <p className="text-red-600 font-semibold mt-0.5">Expirado</p>}
                      </td>
                      <td className="px-4 py-3 text-center">
                        <button
                          onClick={() => toggleActive(a)}
                          className={`text-xs px-2 py-1 rounded font-semibold inline-flex items-center gap-1 ${
                            a.active && !expired ? "bg-green-100 text-green-700" : "bg-gray-100 text-gray-600"
                          }`}
                        >
                          {a.active && !expired ? <Eye size={11} /> : <EyeOff size={11} />}
                          {a.active && !expired ? "Visible" : a.active && expired ? "Expirado" : "Inactivo"}
                        </button>
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-1 justify-end">
                          <button
                            onClick={() => startEdit(a)}
                            className="p-1.5 text-gray-mid hover:text-primary hover:bg-primary/5 rounded"
                            title="Editar"
                          >
                            <Edit3 size={15} />
                          </button>
                          <button
                            onClick={() => handleDelete(a)}
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
