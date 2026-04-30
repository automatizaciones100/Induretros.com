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
  BarChart3,
  Eye,
  EyeOff,
} from "lucide-react";
import { authFetch } from "@/lib/authFetch";
import { AVAILABLE_ICONS, getStatIcon } from "@/lib/statIcon";

interface HomeStat {
  id: number;
  position: number;
  value: string;
  label: string;
  icon?: string | null;
  active: boolean;
}

interface FormState {
  position: number;
  value: string;
  label: string;
  icon: string;
  active: boolean;
}

const empty: FormState = { position: 0, value: "", label: "", icon: "Award", active: true };

export default function AdminEstadisticasPage() {
  const [list, setList] = useState<HomeStat[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [editing, setEditing] = useState<HomeStat | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState<FormState>(empty);
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  const fetchAll = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await authFetch("/api/home-stats/admin/all");
      if (!res.ok) throw new Error(`Error ${res.status}`);
      setList(await res.json());
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error cargando estadísticas");
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

  const startEdit = (s: HomeStat) => {
    setEditing(s);
    setForm({
      position: s.position,
      value: s.value,
      label: s.label,
      icon: s.icon || "Award",
      active: s.active,
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
      position: form.position,
      value: form.value.trim(),
      label: form.label.trim(),
      icon: form.icon || null,
      active: form.active,
    };

    try {
      const url = editing ? `/api/home-stats/${editing.id}` : "/api/home-stats";
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

  const handleDelete = async (s: HomeStat) => {
    if (!confirm(`¿Eliminar la estadística "${s.value} ${s.label}"?`)) return;
    try {
      const res = await authFetch(`/api/home-stats/${s.id}`, { method: "DELETE" });
      if (!res.ok && res.status !== 204) throw new Error(`Error ${res.status}`);
      fetchAll();
    } catch (err) {
      alert(err instanceof Error ? err.message : "Error al eliminar");
    }
  };

  const toggleActive = async (s: HomeStat) => {
    try {
      const res = await authFetch(`/api/home-stats/${s.id}`, {
        method: "PUT",
        body: JSON.stringify({ ...s, icon: s.icon, active: !s.active }),
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
          <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">
            Estadísticas del home
          </h1>
          <p className="text-sm text-gray-mid font-sans mt-1">
            Los contadores con ícono que aparecen en la franja naranja del home.
          </p>
        </div>
        {!showForm && (
          <button onClick={startNew} className="btn-primary">
            <Plus size={16} />
            Nueva estadística
          </button>
        )}
      </div>

      {/* Form de crear/editar */}
      {showForm && (
        <form onSubmit={handleSubmit} className="bg-white border border-gray-100 rounded-xl p-6 mb-6 space-y-4">
          <div className="flex items-center justify-between mb-2">
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
              {editing ? `Editar: ${editing.value} ${editing.label}` : "Nueva estadística"}
            </h2>
            <button type="button" onClick={cancelForm} className="text-gray-light hover:text-red-500 p-1">
              <X size={18} />
            </button>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                Valor *
              </label>
              <input
                type="text"
                required
                maxLength={20}
                value={form.value}
                onChange={(e) => setForm((f) => ({ ...f, value: e.target.value }))}
                placeholder="+9"
                className="input-field text-center text-lg font-heading"
              />
              <p className="text-xs text-gray-light font-sans mt-1">Lo grande que se muestra (ej. &quot;+9&quot;, &quot;1200&quot;, &quot;100%&quot;)</p>
            </div>
            <div className="sm:col-span-2">
              <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                Etiqueta *
              </label>
              <input
                type="text"
                required
                maxLength={100}
                value={form.label}
                onChange={(e) => setForm((f) => ({ ...f, label: e.target.value }))}
                placeholder="Años de experiencia"
                className="input-field"
              />
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                Ícono
              </label>
              <select
                value={form.icon}
                onChange={(e) => setForm((f) => ({ ...f, icon: e.target.value }))}
                className="input-field"
              >
                {AVAILABLE_ICONS.map((name) => (
                  <option key={name} value={name}>{name}</option>
                ))}
              </select>
              <div className="mt-2 flex items-center gap-2 text-gray-mid">
                Vista previa:
                <span className="inline-flex w-9 h-9 rounded bg-primary text-white items-center justify-center">
                  {(() => {
                    const Icon = getStatIcon(form.icon);
                    return <Icon size={20} />;
                  })()}
                </span>
              </div>
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
              <p className="text-xs text-gray-light font-sans mt-1">Menor número = aparece primero</p>
              <label className="flex items-center gap-2 mt-3 cursor-pointer">
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

          {formError && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700 font-sans flex items-start gap-2">
              <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
              {formError}
            </div>
          )}

          <div className="flex gap-3 pt-2">
            <button type="submit" disabled={saving} className="btn-primary disabled:opacity-60">
              {saving ? <Loader2 size={14} className="animate-spin" /> : <Save size={14} />}
              {editing ? "Guardar cambios" : "Crear"}
            </button>
            <button type="button" onClick={cancelForm} className="btn-secondary">
              Cancelar
            </button>
          </div>
        </form>
      )}

      {/* Vista previa de cómo se ve en el home */}
      {list.filter((s) => s.active).length > 0 && (
        <div className="mb-6">
          <p className="text-xs text-gray-mid font-sans uppercase tracking-wide mb-2">Vista previa (cómo se ve en el home)</p>
          <div className="bg-primary py-6 rounded-xl">
            <div
              className="grid gap-6 text-center px-4"
              style={{ gridTemplateColumns: `repeat(auto-fit, minmax(140px, 1fr))` }}
            >
              {list
                .filter((s) => s.active)
                .sort((a, b) => a.position - b.position || a.id - b.id)
                .map((s) => {
                  const Icon = getStatIcon(s.icon);
                  return (
                    <div key={s.id} className="text-white">
                      <div className="flex justify-center mb-2 opacity-80">
                        <Icon size={24} />
                      </div>
                      <div className="font-heading text-2xl font-semibold">{s.value}</div>
                      <div className="font-sans text-xs opacity-80 mt-1">{s.label}</div>
                    </div>
                  );
                })}
            </div>
          </div>
        </div>
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
            <BarChart3 size={32} className="text-gray-light mx-auto mb-3" />
            <p className="text-gray-mid font-sans">Sin estadísticas. Crea la primera.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm font-sans">
              <thead className="bg-bg-light text-xs uppercase tracking-wide text-gray-mid">
                <tr>
                  <th className="text-center px-3 py-3 w-12">#</th>
                  <th className="text-center px-3 py-3 w-12">Ícono</th>
                  <th className="text-left px-4 py-3 w-32">Valor</th>
                  <th className="text-left px-4 py-3">Etiqueta</th>
                  <th className="text-center px-4 py-3 w-24">Estado</th>
                  <th className="text-right px-4 py-3 w-24">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {list.map((s) => {
                  const Icon = getStatIcon(s.icon);
                  return (
                    <tr key={s.id} className={`hover:bg-bg-light/50 ${!s.active ? "opacity-50" : ""}`}>
                      <td className="px-3 py-3 text-center text-gray-mid">{s.position}</td>
                      <td className="px-3 py-3 text-center">
                        <Icon size={18} className="mx-auto text-gray-mid" />
                      </td>
                      <td className="px-4 py-3 font-heading font-semibold text-dark text-base">{s.value}</td>
                      <td className="px-4 py-3 text-dark">{s.label}</td>
                      <td className="px-4 py-3 text-center">
                        <button
                          onClick={() => toggleActive(s)}
                          className={`text-xs px-2 py-1 rounded font-semibold inline-flex items-center gap-1 ${
                            s.active ? "bg-green-100 text-green-700" : "bg-gray-100 text-gray-600"
                          }`}
                          title="Click para cambiar"
                        >
                          {s.active ? <Eye size={11} /> : <EyeOff size={11} />}
                          {s.active ? "Visible" : "Oculto"}
                        </button>
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center gap-1 justify-end">
                          <button
                            onClick={() => startEdit(s)}
                            className="p-1.5 text-gray-mid hover:text-primary hover:bg-primary/5 rounded"
                            title="Editar"
                          >
                            <Edit3 size={15} />
                          </button>
                          <button
                            onClick={() => handleDelete(s)}
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
