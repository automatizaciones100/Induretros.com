"use client";

import { useEffect, useState } from "react";
import {
  History,
  RotateCcw,
  Loader2,
  AlertCircle,
  CheckCircle2,
  ChevronDown,
  ChevronRight,
  Plus,
  Pencil,
  Trash2,
  Settings,
  Sparkles,
  HelpCircle,
  MessageSquareQuote,
  BarChart3,
  Megaphone,
  ShieldCheck,
  RefreshCw,
} from "lucide-react";
import { authFetch } from "@/lib/authFetch";

interface Change {
  id: number;
  entity_type: string;
  entity_id: string;
  entity_label: string;
  action: "create" | "update" | "delete" | "restore";
  before: Record<string, unknown> | null;
  after: Record<string, unknown> | null;
  user_id: number | null;
  user_email: string | null;
  ip: string | null;
  restored: boolean;
  restored_at: string | null;
  created_at: string | null;
  restorable: boolean;
}

interface ListResponse {
  total: number;
  limit: number;
  offset: number;
  items: Change[];
}

const ENTITY_LABEL: Record<string, { label: string; icon: React.ComponentType<{ size?: number; className?: string }> }> = {
  why_us: { label: "Por qué elegirnos", icon: Sparkles },
  faq: { label: "FAQ", icon: HelpCircle },
  testimonial: { label: "Testimonio", icon: MessageSquareQuote },
  home_stat: { label: "Estadística", icon: BarChart3 },
  announcement: { label: "Anuncio", icon: Megaphone },
  legal_page: { label: "Página legal", icon: ShieldCheck },
  site_settings: { label: "Configuración", icon: Settings },
};

const ACTION_STYLES: Record<string, { label: string; class: string; Icon: React.ComponentType<{ size?: number; className?: string }> }> = {
  create: { label: "Creado", class: "bg-green-100 text-green-700", Icon: Plus },
  update: { label: "Actualizado", class: "bg-blue-100 text-blue-700", Icon: Pencil },
  delete: { label: "Eliminado", class: "bg-red-100 text-red-700", Icon: Trash2 },
  restore: { label: "Restaurado", class: "bg-amber-100 text-amber-700", Icon: RotateCcw },
};

const PAGE_SIZE = 25;

export default function AdminHistorialPage() {
  const [list, setList] = useState<Change[]>([]);
  const [total, setTotal] = useState(0);
  const [offset, setOffset] = useState(0);
  const [filterEntity, setFilterEntity] = useState<string>("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [expanded, setExpanded] = useState<Set<number>>(new Set());
  const [restoring, setRestoring] = useState<number | null>(null);
  const [actionMsg, setActionMsg] = useState<{ kind: "ok" | "err"; text: string } | null>(null);

  const fetchList = async (newOffset = offset, newEntity = filterEntity) => {
    setLoading(true);
    setError(null);
    try {
      const params = new URLSearchParams({ limit: String(PAGE_SIZE), offset: String(newOffset) });
      if (newEntity) params.set("entity_type", newEntity);
      const res = await authFetch(`/api/admin/changes?${params}`);
      if (!res.ok) throw new Error(`Error ${res.status}`);
      const data: ListResponse = await res.json();
      setList(data.items);
      setTotal(data.total);
      setOffset(data.offset);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error cargando historial");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchList(0, filterEntity);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [filterEntity]);

  const toggleExpand = (id: number) => {
    setExpanded((s) => {
      const next = new Set(s);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  const handleRestore = async (c: Change) => {
    const verb =
      c.action === "create" ? "deshacer la creación de" :
      c.action === "delete" ? "restaurar la entidad eliminada" :
      "revertir los cambios sobre";
    if (!confirm(`¿Seguro que deseas ${verb} "${c.entity_label}"?\nEsta acción quedará registrada.`)) return;
    setRestoring(c.id);
    setActionMsg(null);
    try {
      const res = await authFetch(`/api/admin/changes/${c.id}/restore`, { method: "POST" });
      if (!res.ok) {
        const body = await res.json().catch(() => ({}));
        throw new Error(body.detail || `Error ${res.status}`);
      }
      setActionMsg({ kind: "ok", text: "Cambio restaurado correctamente." });
      fetchList(offset, filterEntity);
    } catch (err) {
      setActionMsg({ kind: "err", text: err instanceof Error ? err.message : "Error al restaurar" });
    } finally {
      setRestoring(null);
      setTimeout(() => setActionMsg(null), 4000);
    }
  };

  const formatDate = (iso: string | null) => {
    if (!iso) return "—";
    return new Date(iso).toLocaleString("es-CO", {
      year: "numeric", month: "short", day: "numeric", hour: "2-digit", minute: "2-digit",
    });
  };

  return (
    <div className="p-6 lg:p-8 max-w-6xl mx-auto">
      <div className="mb-6">
        <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">
          Historial de cambios
        </h1>
        <p className="text-sm text-gray-mid font-sans mt-1">
          Cada modificación realizada desde el panel admin queda registrada con un snapshot del estado anterior. Puedes deshacerla desde aquí.
        </p>
      </div>

      {/* Filtros + reload */}
      <div className="flex flex-wrap gap-3 items-center mb-4">
        <select
          value={filterEntity}
          onChange={(e) => setFilterEntity(e.target.value)}
          className="input-field max-w-xs"
        >
          <option value="">Todas las entidades</option>
          {Object.entries(ENTITY_LABEL).map(([key, { label }]) => (
            <option key={key} value={key}>{label}</option>
          ))}
        </select>
        <button
          type="button"
          onClick={() => fetchList(offset, filterEntity)}
          className="btn-secondary inline-flex items-center gap-1.5 text-sm"
        >
          <RefreshCw size={14} />
          Actualizar
        </button>
        <span className="text-sm text-gray-mid font-sans ml-auto">
          {total} cambio{total === 1 ? "" : "s"} registrado{total === 1 ? "" : "s"}
        </span>
      </div>

      {actionMsg && (
        <div
          className={`mb-4 rounded-lg p-3 text-sm font-sans flex items-start gap-2 ${
            actionMsg.kind === "ok"
              ? "bg-green-50 border border-green-200 text-green-700"
              : "bg-red-50 border border-red-200 text-red-700"
          }`}
        >
          {actionMsg.kind === "ok" ? <CheckCircle2 size={16} /> : <AlertCircle size={16} />}
          {actionMsg.text}
        </div>
      )}

      {/* Lista */}
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
            <History size={32} className="text-gray-light mx-auto mb-3" />
            <p className="text-gray-mid font-sans">Sin cambios registrados todavía.</p>
          </div>
        ) : (
          <ul className="divide-y divide-gray-100">
            {list.map((c) => {
              const entitySpec = ENTITY_LABEL[c.entity_type] ?? { label: c.entity_type, icon: History };
              const EntityIcon = entitySpec.icon;
              const actionSpec = ACTION_STYLES[c.action] ?? ACTION_STYLES.update;
              const ActionIcon = actionSpec.Icon;
              const isExpanded = expanded.has(c.id);

              return (
                <li key={c.id} className={`${c.restored ? "bg-bg-light/40" : ""}`}>
                  <div className="flex items-start gap-3 p-4">
                    <button
                      type="button"
                      onClick={() => toggleExpand(c.id)}
                      className="text-gray-light hover:text-dark-2 mt-1"
                      aria-label="Expandir detalle"
                    >
                      {isExpanded ? <ChevronDown size={16} /> : <ChevronRight size={16} />}
                    </button>

                    <div className="flex-shrink-0 w-9 h-9 rounded-full bg-bg-light flex items-center justify-center text-gray-mid">
                      <EntityIcon size={16} />
                    </div>

                    <div className="flex-1 min-w-0">
                      <div className="flex flex-wrap items-center gap-2 mb-0.5">
                        <span className={`text-[11px] uppercase px-2 py-0.5 rounded font-semibold inline-flex items-center gap-1 ${actionSpec.class}`}>
                          <ActionIcon size={10} />
                          {actionSpec.label}
                        </span>
                        <span className="text-xs uppercase tracking-wide text-gray-mid font-sans">
                          {entitySpec.label}
                        </span>
                        {c.restored && (
                          <span className="text-[11px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded font-semibold uppercase">
                            Ya restaurado
                          </span>
                        )}
                      </div>
                      <p className="font-sans text-sm font-medium text-dark line-clamp-1">
                        {c.entity_label}
                      </p>
                      <p className="text-xs text-gray-light font-sans mt-0.5">
                        {formatDate(c.created_at)} · {c.user_email || `user #${c.user_id ?? "?"}`}
                        {c.ip && c.ip !== "unknown" && <span className="ml-1.5 text-gray-300">{c.ip}</span>}
                      </p>
                    </div>

                    {c.restorable ? (
                      <button
                        type="button"
                        onClick={() => handleRestore(c)}
                        disabled={restoring === c.id}
                        className="btn-secondary text-xs px-3 py-1.5 inline-flex items-center gap-1 disabled:opacity-60"
                        title="Restaurar este cambio"
                      >
                        {restoring === c.id ? (
                          <Loader2 size={12} className="animate-spin" />
                        ) : (
                          <RotateCcw size={12} />
                        )}
                        {c.action === "create" ? "Deshacer" : c.action === "delete" ? "Restaurar" : "Revertir"}
                      </button>
                    ) : (
                      <span className="text-xs text-gray-light font-sans italic px-2">
                        {c.action === "restore" ? "Restauración" : "—"}
                      </span>
                    )}
                  </div>

                  {/* Detalle: diff before/after */}
                  {isExpanded && (
                    <div className="px-12 pb-5 grid grid-cols-1 md:grid-cols-2 gap-3">
                      <DiffPane title="Antes" data={c.before} highlight="red" />
                      <DiffPane title="Después" data={c.after} highlight="green" />
                    </div>
                  )}
                </li>
              );
            })}
          </ul>
        )}
      </div>

      {/* Paginación simple */}
      {total > PAGE_SIZE && (
        <div className="flex items-center justify-between mt-4 text-sm font-sans">
          <span className="text-gray-mid">
            Mostrando {offset + 1}–{Math.min(offset + PAGE_SIZE, total)} de {total}
          </span>
          <div className="flex gap-2">
            <button
              type="button"
              onClick={() => fetchList(Math.max(0, offset - PAGE_SIZE), filterEntity)}
              disabled={offset === 0 || loading}
              className="btn-secondary text-xs px-3 py-1.5 disabled:opacity-50"
            >
              Anterior
            </button>
            <button
              type="button"
              onClick={() => fetchList(offset + PAGE_SIZE, filterEntity)}
              disabled={offset + PAGE_SIZE >= total || loading}
              className="btn-secondary text-xs px-3 py-1.5 disabled:opacity-50"
            >
              Siguiente
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

function DiffPane({
  title,
  data,
  highlight,
}: {
  title: string;
  data: Record<string, unknown> | null;
  highlight: "red" | "green";
}) {
  const tone =
    highlight === "red"
      ? "border-red-200 bg-red-50/50"
      : "border-green-200 bg-green-50/50";
  return (
    <div className={`border rounded-lg p-3 ${tone}`}>
      <p className="text-[11px] uppercase tracking-wide text-gray-mid font-sans font-semibold mb-2">
        {title}
      </p>
      {data === null ? (
        <p className="text-xs text-gray-light italic font-sans">— (sin datos)</p>
      ) : (
        <pre className="text-[11px] text-dark-2 font-mono whitespace-pre-wrap break-all max-h-64 overflow-y-auto leading-relaxed">
          {JSON.stringify(data, null, 2)}
        </pre>
      )}
    </div>
  );
}
