"use client";

import { use } from "react";
import Link from "next/link";
import {
  Save,
  Loader2,
  AlertCircle,
  ShieldCheck,
  Eye,
  Code,
  ExternalLink,
  CheckCircle2,
  Bold,
  Italic,
  List,
  ListOrdered,
  Heading2,
  Heading3,
  Link2,
  ArrowLeft,
} from "lucide-react";
import { useState } from "react";
import type { LegalPage } from "@/lib/api/types";
import {
  getLegalPageBySlug,
  createLegalPage,
  updateLegalPage,
} from "@/lib/api/legalPagesAdmin";
import { useAdminSingleton } from "@/hooks/useAdminSingleton";

interface FormState {
  title: string;
  content: string;
}

const EMPTY: FormState = { title: "", content: "" };

interface PageProps {
  params: Promise<{ slug: string }>;
}

export default function EditLegalPage({ params }: PageProps) {
  const { slug } = use(params);
  const [view, setView] = useState<"editor" | "preview" | "split">("split");

  const single = useAdminSingleton<LegalPage, FormState>({
    loader: () => getLegalPageBySlug(slug),
    saver: async (payload, isCreating) => {
      const f = payload as FormState;
      if (isCreating) {
        return await createLegalPage({ slug, title: f.title, content: f.content });
      }
      return await updateLegalPage(slug, f);
    },
    emptyForm: EMPTY,
    toFormState: (p) => ({ title: p.title, content: p.content }),
    toPayload: (f) => ({ title: f.title.trim(), content: f.content }),
    loadErrorMessage: "Error cargando la página",
  });

  const {
    record: page, loading, loadError,
    form, setForm, saving, saveError, saveOk, isCreating, dirty,
    handleSubmit,
  } = single;

  const title = form.title;
  const content = form.content;
  const setTitle = (v: string) => setForm((f) => ({ ...f, title: v }));
  const setContent = (v: string) => setForm((f) => ({ ...f, content: v }));

  const wrap = (open: string, close: string) => {
    const ta = document.getElementById("legal-content-textarea") as HTMLTextAreaElement | null;
    if (!ta) return;
    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    const selected = content.slice(start, end);
    const next = content.slice(0, start) + open + (selected || "") + close + content.slice(end);
    setContent(next);
    requestAnimationFrame(() => {
      ta.focus();
      ta.setSelectionRange(start + open.length, start + open.length + selected.length);
    });
  };

  const insertBlock = (snippet: string) => {
    const ta = document.getElementById("legal-content-textarea") as HTMLTextAreaElement | null;
    if (!ta) return;
    const start = ta.selectionStart;
    const next = content.slice(0, start) + snippet + content.slice(start);
    setContent(next);
    requestAnimationFrame(() => {
      ta.focus();
      ta.setSelectionRange(start + snippet.length, start + snippet.length);
    });
  };

  const insertLink = () => {
    const url = prompt("URL del enlace (https://…):");
    if (!url) return;
    wrap(`<a href="${url}" target="_blank" rel="noopener noreferrer">`, "</a>");
  };

  const publicUrl = `/${slug}`;

  return (
    <div className="p-6 lg:p-8 max-w-6xl mx-auto">
      <div className="mb-6">
        <Link
          href="/admin/paginas-legales"
          className="inline-flex items-center gap-1 text-xs text-gray-mid hover:text-primary font-sans mb-2"
        >
          <ArrowLeft size={12} />
          Todas las páginas legales
        </Link>
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">
              {page?.title || (loading ? "Cargando…" : `Nueva página: ${slug}`)}
            </h1>
            <p className="text-sm text-gray-mid font-sans mt-1">
              Slug: <code className="bg-bg-light px-1.5 py-0.5 rounded text-xs">{slug}</code> · URL pública:{" "}
              <Link href={publicUrl} target="_blank" className="text-primary hover:underline inline-flex items-center gap-1">
                {publicUrl} <ExternalLink size={11} />
              </Link>
            </p>
          </div>
        </div>
      </div>

      {loading ? (
        <div className="bg-white border border-gray-100 rounded-xl p-12 text-center">
          <Loader2 size={24} className="animate-spin text-gray-light mx-auto mb-3" />
          <p className="text-gray-mid font-sans text-sm">Cargando…</p>
        </div>
      ) : loadError ? (
        <div className="bg-red-50 border border-red-200 rounded-xl p-6 flex items-start gap-2 text-red-700 text-sm font-sans">
          <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
          {loadError}
        </div>
      ) : (
        <form onSubmit={handleSubmit} className="space-y-4">
          {isCreating && (
            <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800 font-sans flex items-start gap-2">
              <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
              Esta página todavía no existe. Al guardar la crearemos en el sistema con el slug <strong>{slug}</strong>.
            </div>
          )}

          {/* Título */}
          <div className="bg-white border border-gray-100 rounded-xl p-6">
            <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
              Título de la página *
            </label>
            <input
              type="text"
              required
              maxLength={200}
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              className="input-field"
              placeholder="Ej: Términos y condiciones"
            />
          </div>

          {/* Toolbar de formato + selector de vista */}
          <div className="bg-white border border-gray-100 rounded-xl p-3 flex flex-wrap gap-2 items-center justify-between">
            <div className="flex flex-wrap gap-1">
              <button type="button" onClick={() => insertBlock("\n<h2>Título de sección</h2>\n")} className="px-2 py-1.5 rounded text-gray-mid hover:bg-bg-light hover:text-dark-2 inline-flex items-center gap-1 text-xs font-sans" title="Encabezado H2">
                <Heading2 size={14} /> H2
              </button>
              <button type="button" onClick={() => insertBlock("\n<h3>Subtítulo</h3>\n")} className="px-2 py-1.5 rounded text-gray-mid hover:bg-bg-light hover:text-dark-2 inline-flex items-center gap-1 text-xs font-sans" title="Encabezado H3">
                <Heading3 size={14} /> H3
              </button>
              <span className="w-px bg-gray-200 mx-1" />
              <button type="button" onClick={() => wrap("<strong>", "</strong>")} className="px-2 py-1.5 rounded text-gray-mid hover:bg-bg-light hover:text-dark-2" title="Negrita">
                <Bold size={14} />
              </button>
              <button type="button" onClick={() => wrap("<em>", "</em>")} className="px-2 py-1.5 rounded text-gray-mid hover:bg-bg-light hover:text-dark-2" title="Cursiva">
                <Italic size={14} />
              </button>
              <button type="button" onClick={insertLink} className="px-2 py-1.5 rounded text-gray-mid hover:bg-bg-light hover:text-dark-2" title="Enlace">
                <Link2 size={14} />
              </button>
              <span className="w-px bg-gray-200 mx-1" />
              <button type="button" onClick={() => insertBlock("\n<p>Párrafo de texto.</p>\n")} className="px-2 py-1.5 rounded text-gray-mid hover:bg-bg-light hover:text-dark-2 text-xs font-sans" title="Insertar párrafo">
                P
              </button>
              <button type="button" onClick={() => insertBlock("\n<ul>\n  <li>Elemento</li>\n  <li>Elemento</li>\n</ul>\n")} className="px-2 py-1.5 rounded text-gray-mid hover:bg-bg-light hover:text-dark-2" title="Lista con viñetas">
                <List size={14} />
              </button>
              <button type="button" onClick={() => insertBlock("\n<ol>\n  <li>Paso 1</li>\n  <li>Paso 2</li>\n</ol>\n")} className="px-2 py-1.5 rounded text-gray-mid hover:bg-bg-light hover:text-dark-2" title="Lista numerada">
                <ListOrdered size={14} />
              </button>
            </div>

            <div className="inline-flex rounded-lg border border-gray-200 overflow-hidden text-xs font-sans">
              <button type="button" onClick={() => setView("editor")} className={`px-3 py-1.5 inline-flex items-center gap-1 ${view === "editor" ? "bg-primary text-white" : "bg-white text-gray-mid hover:bg-bg-light"}`}>
                <Code size={12} /> Editor
              </button>
              <button type="button" onClick={() => setView("split")} className={`px-3 py-1.5 inline-flex items-center gap-1 border-l border-gray-200 ${view === "split" ? "bg-primary text-white" : "bg-white text-gray-mid hover:bg-bg-light"}`}>
                Split
              </button>
              <button type="button" onClick={() => setView("preview")} className={`px-3 py-1.5 inline-flex items-center gap-1 border-l border-gray-200 ${view === "preview" ? "bg-primary text-white" : "bg-white text-gray-mid hover:bg-bg-light"}`}>
                <Eye size={12} /> Vista
              </button>
            </div>
          </div>

          {/* Editor + Preview */}
          <div className={`grid gap-4 ${view === "split" ? "lg:grid-cols-2" : "grid-cols-1"}`}>
            {(view === "editor" || view === "split") && (
              <div className="bg-white border border-gray-100 rounded-xl p-4">
                <p className="text-xs uppercase tracking-wide text-gray-mid font-sans mb-2">HTML editable</p>
                <textarea
                  id="legal-content-textarea"
                  required
                  rows={view === "editor" ? 28 : 22}
                  maxLength={50000}
                  value={content}
                  onChange={(e) => setContent(e.target.value)}
                  className="w-full font-mono text-xs text-dark border border-gray-200 rounded p-3 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary resize-y leading-relaxed"
                  placeholder="<h2>Título</h2><p>Contenido…</p>"
                />
                <p className="text-xs text-gray-light font-sans mt-2">
                  Tags permitidas: h2, h3, h4, p, ul, ol, li, strong, em, u, a, blockquote, hr, br. El resto se elimina automáticamente.
                </p>
              </div>
            )}

            {(view === "preview" || view === "split") && (
              <div className="bg-bg-light border border-gray-100 rounded-xl p-4">
                <p className="text-xs uppercase tracking-wide text-gray-mid font-sans mb-2">Vista previa</p>
                <div className="bg-white rounded-lg p-6 max-h-[640px] overflow-y-auto">
                  <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase tracking-wide mb-6">
                    {title || "Sin título"}
                  </h1>
                  <article className="legal-content" dangerouslySetInnerHTML={{ __html: content }} />
                </div>
              </div>
            )}
          </div>

          {/* Estado y acciones */}
          {saveError && (
            <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700 font-sans flex items-start gap-2">
              <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
              {saveError}
            </div>
          )}
          {saveOk && (
            <div className="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-700 font-sans flex items-center gap-2">
              <CheckCircle2 size={16} />
              Cambios guardados. Pueden tardar hasta 5 minutos en aparecer en la página pública (caché).
            </div>
          )}

          <div className="flex flex-wrap items-center gap-3 pt-2">
            <button type="submit" disabled={saving || !dirty} className="btn-primary disabled:opacity-60 disabled:cursor-not-allowed">
              {saving ? <Loader2 size={14} className="animate-spin" /> : <Save size={14} />}
              {isCreating ? "Crear página" : dirty ? "Guardar cambios" : "Sin cambios"}
            </button>
            {page?.updated_at && (
              <p className="text-xs text-gray-mid font-sans inline-flex items-center gap-1">
                <ShieldCheck size={12} className="text-primary" />
                Última actualización:{" "}
                {new Date(page.updated_at).toLocaleString("es-CO", {
                  year: "numeric", month: "short", day: "numeric", hour: "2-digit", minute: "2-digit",
                })}
              </p>
            )}
          </div>
        </form>
      )}
    </div>
  );
}
