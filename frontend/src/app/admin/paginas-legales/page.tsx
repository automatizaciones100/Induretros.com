"use client";

import { useEffect, useState, FormEvent } from "react";
import Link from "next/link";
import {
  Plus,
  FileText,
  ExternalLink,
  Edit3,
  Trash2,
  Loader2,
  AlertCircle,
  X,
  Save,
} from "lucide-react";
import {
  listLegalPagesAdmin,
  createLegalPage,
  deleteLegalPage,
} from "@/lib/api/legalPagesAdmin";
import type { LegalPage } from "@/lib/api/types";

const SLUG_RE = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;

export default function AdminLegalPagesListPage() {
  const [list, setList] = useState<LegalPage[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [showForm, setShowForm] = useState(false);
  const [newSlug, setNewSlug] = useState("");
  const [creating, setCreating] = useState(false);
  const [createError, setCreateError] = useState<string | null>(null);

  const fetchAll = async () => {
    setLoading(true);
    setError(null);
    try {
      const data = await listLegalPagesAdmin();
      setList(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error cargando páginas");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchAll();
  }, []);

  const handleCreate = async (e: FormEvent) => {
    e.preventDefault();
    setCreateError(null);
    const slug = newSlug.trim().toLowerCase();
    if (!SLUG_RE.test(slug)) {
      setCreateError("El slug debe ser kebab-case (a-z, 0-9, '-'). Ej: 'cookies', 'envios-internacionales'.");
      return;
    }
    setCreating(true);
    try {
      await createLegalPage({
        slug,
        title: slug.replace(/-/g, " ").replace(/^./, (c) => c.toUpperCase()),
        content: "<p>Contenido pendiente. Edita esta página.</p>",
      });
      // Redirige al editor de la nueva página
      window.location.href = `/admin/paginas-legales/${slug}`;
    } catch (err) {
      setCreateError(err instanceof Error ? err.message : "Error al crear");
    } finally {
      setCreating(false);
    }
  };

  const handleDelete = async (p: LegalPage) => {
    if (!confirm(`¿Eliminar la página "${p.title}" (slug: ${p.slug})?\nEsta acción quedará registrada en el historial y se puede revertir desde allí.`)) return;
    try {
      await deleteLegalPage(p.slug);
      fetchAll();
    } catch (err) {
      alert(err instanceof Error ? err.message : "Error al eliminar");
    }
  };

  const formatDate = (iso: string | null) => {
    if (!iso) return "—";
    return new Date(iso).toLocaleString("es-CO", {
      year: "numeric", month: "short", day: "numeric", hour: "2-digit", minute: "2-digit",
    });
  };

  return (
    <div className="p-6 lg:p-8 max-w-5xl mx-auto">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
          <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">
            Páginas legales
          </h1>
          <p className="text-sm text-gray-mid font-sans mt-1">
            Garantía, términos, devoluciones, privacidad y cualquier otra página legal con contenido HTML editable.
          </p>
        </div>
        {!showForm && (
          <button onClick={() => setShowForm(true)} className="btn-primary">
            <Plus size={16} />
            Nueva página
          </button>
        )}
      </div>

      {showForm && (
        <form onSubmit={handleCreate} className="bg-white border border-gray-100 rounded-xl p-6 mb-6 space-y-3">
          <div className="flex items-center justify-between">
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
              Nueva página legal
            </h2>
            <button type="button" onClick={() => { setShowForm(false); setNewSlug(""); setCreateError(null); }} className="text-gray-light hover:text-red-500 p-1">
              <X size={18} />
            </button>
          </div>
          <div>
            <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
              Slug (URL pública: <code className="bg-bg-light px-1 rounded">/{newSlug || "..."}</code>)
            </label>
            <input
              type="text"
              required
              maxLength={50}
              value={newSlug}
              onChange={(e) => setNewSlug(e.target.value)}
              placeholder="cookies, envios-internacionales..."
              className="input-field font-mono text-sm"
            />
            <p className="text-xs text-gray-light font-sans mt-1">
              Solo a-z, 0-9 y guiones. La URL pública se construye con este slug.
            </p>
          </div>
          {createError && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700 font-sans flex items-start gap-2">
              <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
              {createError}
            </div>
          )}
          <div className="flex gap-3">
            <button type="submit" disabled={creating} className="btn-primary disabled:opacity-60">
              {creating ? <Loader2 size={14} className="animate-spin" /> : <Save size={14} />}
              Crear y editar
            </button>
            <button type="button" onClick={() => { setShowForm(false); setNewSlug(""); setCreateError(null); }} className="btn-secondary">
              Cancelar
            </button>
          </div>
        </form>
      )}

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
            <FileText size={32} className="text-gray-light mx-auto mb-3" />
            <p className="text-gray-mid font-sans">Sin páginas legales todavía.</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm font-sans">
              <thead className="bg-bg-light text-xs uppercase tracking-wide text-gray-mid">
                <tr>
                  <th className="text-left px-4 py-3">Página</th>
                  <th className="text-left px-4 py-3 hidden md:table-cell w-48">URL pública</th>
                  <th className="text-left px-4 py-3 hidden lg:table-cell w-48">Última edición</th>
                  <th className="text-right px-4 py-3 w-32">Acciones</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {list.map((p) => (
                  <tr key={p.id} className="hover:bg-bg-light/50">
                    <td className="px-4 py-3">
                      <p className="font-medium text-dark">{p.title}</p>
                      <p className="text-xs text-gray-light font-mono">slug: {p.slug}</p>
                    </td>
                    <td className="px-4 py-3 hidden md:table-cell">
                      <Link href={`/${p.slug}`} target="_blank" className="text-primary hover:underline inline-flex items-center gap-1 text-xs font-mono">
                        /{p.slug} <ExternalLink size={11} />
                      </Link>
                    </td>
                    <td className="px-4 py-3 hidden lg:table-cell text-xs text-gray-mid">
                      {formatDate(p.updated_at)}
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-1 justify-end">
                        <Link
                          href={`/admin/paginas-legales/${p.slug}`}
                          className="p-1.5 text-gray-mid hover:text-primary hover:bg-primary/5 rounded inline-flex"
                          title="Editar"
                        >
                          <Edit3 size={15} />
                        </Link>
                        <button
                          onClick={() => handleDelete(p)}
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
