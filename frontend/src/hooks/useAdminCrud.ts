"use client";

/**
 * Hook genérico para los CRUD del panel admin.
 *
 * Cubre el patrón compartido en /admin/{testimonios,faq,por-que-elegirnos,
 * estadisticas,anuncios}: lista paginada, formulario inline para crear/editar,
 * toggle de active, eliminar con confirm.
 *
 * Cada página solo aporta:
 *   - el cliente API (instancia de AdminCrudApi)
 *   - el formulario en blanco (emptyForm)
 *   - cómo poblar el form al editar (toFormState)
 *   - cómo preparar el payload al enviar (toPayload)
 *   - opcionalmente un nombre legible para el confirm de eliminar
 *
 * Pedidos por las propias páginas:
 *   - el JSX (table + form)
 *   - errores de validación visuales en el form
 */
import { useCallback, useEffect, useState, FormEvent } from "react";
import type { AdminCrudApi } from "@/lib/api/adminCrud";

export interface UseAdminCrudOptions<T extends { id: number; active?: boolean }, F> {
  /** Cliente API tipado (testimonialsApi, faqApi, etc). */
  api: AdminCrudApi<T>;
  /** Estado inicial del formulario (al crear). */
  emptyForm: F;
  /** Cómo poblar el formulario al editar un item existente. */
  toFormState: (item: T) => F;
  /** Cómo construir el payload para POST/PUT a partir del form actual. */
  toPayload: (form: F, isEdit: boolean) => object;
  /** Defaults adicionales al iniciar un "Nuevo" (ej. position = list.length + 1). */
  startNewOverrides?: (list: T[]) => Partial<F>;
  /** Para el confirm de eliminar: cómo describir un item ("¿Eliminar X?"). */
  describeForDelete?: (item: T) => string;
  /** Para el toggle de active: por defecto envía el item completo con active negado.
   *  Override sólo si la API requiere otro shape. */
  togglePayload?: (item: T) => object;
  /** Mensajes de error humanos. */
  loadErrorMessage?: string;
  saveErrorMessage?: string;
  deleteErrorMessage?: string;
}

export interface UseAdminCrudReturn<T, F> {
  // Datos
  list: T[];
  loading: boolean;
  error: string | null;

  // Form state
  editing: T | null;
  showForm: boolean;
  form: F;
  setForm: React.Dispatch<React.SetStateAction<F>>;
  saving: boolean;
  formError: string | null;

  // Acciones
  startNew: () => void;
  startEdit: (item: T) => void;
  cancelForm: () => void;
  handleSubmit: (e: FormEvent) => Promise<void>;
  handleDelete: (item: T) => Promise<void>;
  toggleActive: (item: T) => Promise<void>;
  refetch: () => Promise<void>;
}

export function useAdminCrud<T extends { id: number; active?: boolean }, F>(
  options: UseAdminCrudOptions<T, F>,
): UseAdminCrudReturn<T, F> {
  const {
    api,
    emptyForm,
    toFormState,
    toPayload,
    startNewOverrides,
    describeForDelete,
    togglePayload,
    loadErrorMessage = "Error cargando los datos",
    saveErrorMessage = "Error al guardar",
    deleteErrorMessage = "Error al eliminar",
  } = options;

  const [list, setList] = useState<T[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [editing, setEditing] = useState<T | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState<F>(emptyForm);
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  const refetch = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const items = await api.listAll();
      setList(items);
    } catch (err) {
      setError(err instanceof Error ? err.message : loadErrorMessage);
    } finally {
      setLoading(false);
    }
  }, [api, loadErrorMessage]);

  useEffect(() => {
    refetch();
  }, [refetch]);

  const startNew = useCallback(() => {
    setEditing(null);
    const overrides = startNewOverrides ? startNewOverrides(list) : {};
    setForm({ ...emptyForm, ...overrides });
    setFormError(null);
    setShowForm(true);
  }, [emptyForm, list, startNewOverrides]);

  const startEdit = useCallback(
    (item: T) => {
      setEditing(item);
      setForm(toFormState(item));
      setFormError(null);
      setShowForm(true);
    },
    [toFormState],
  );

  const cancelForm = useCallback(() => {
    setShowForm(false);
    setEditing(null);
    setForm(emptyForm);
    setFormError(null);
  }, [emptyForm]);

  const handleSubmit = useCallback(
    async (e: FormEvent) => {
      e.preventDefault();
      setFormError(null);
      setSaving(true);
      try {
        const payload = toPayload(form, editing !== null);
        if (editing) {
          await api.update(editing.id, payload);
        } else {
          await api.create(payload);
        }
        cancelForm();
        await refetch();
      } catch (err) {
        setFormError(err instanceof Error ? err.message : saveErrorMessage);
      } finally {
        setSaving(false);
      }
    },
    [api, form, editing, toPayload, cancelForm, refetch, saveErrorMessage],
  );

  const handleDelete = useCallback(
    async (item: T) => {
      const description = describeForDelete ? describeForDelete(item) : `#${item.id}`;
      if (!confirm(`¿Eliminar ${description}?`)) return;
      try {
        await api.remove(item.id);
        await refetch();
      } catch (err) {
        alert(err instanceof Error ? err.message : deleteErrorMessage);
      }
    },
    [api, describeForDelete, refetch, deleteErrorMessage],
  );

  const toggleActive = useCallback(
    async (item: T) => {
      const payload = togglePayload
        ? togglePayload(item)
        : { ...item, active: !item.active };
      try {
        await api.update(item.id, payload);
        await refetch();
      } catch (err) {
        alert(err instanceof Error ? err.message : "Error al cambiar estado");
      }
    },
    [api, togglePayload, refetch],
  );

  return {
    list,
    loading,
    error,
    editing,
    showForm,
    form,
    setForm,
    saving,
    formError,
    startNew,
    startEdit,
    cancelForm,
    handleSubmit,
    handleDelete,
    toggleActive,
    refetch,
  };
}
