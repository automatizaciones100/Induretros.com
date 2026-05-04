"use client";

/**
 * Hook genérico para páginas de edición tipo singleton del panel admin.
 *
 * Cubre el patrón compartido en /admin/configuracion y /admin/paginas-legales/
 * [slug]: cargar UN solo registro, editarlo en un form grande, guardar
 * (PUT/POST). No hay lista, no hay create separado del edit.
 *
 * Diferencias con useAdminCrud:
 *   - Sólo trabaja con un único registro (no hay list).
 *   - El form se inicializa al cargar; no hay "Nuevo / Editar".
 *   - Maneja el caso de "no existe todavía" (404 → form vacío para crear).
 *
 * Cada página solo aporta:
 *   - cómo cargar el registro (loader)
 *   - cómo guardarlo (saver — recibe payload + isCreating booleano)
 *   - cómo poblar el form al recibir datos (toFormState)
 *   - cómo construir el payload al enviar (toPayload)
 *   - el formulario vacío inicial
 */
import { useCallback, useEffect, useState, FormEvent } from "react";

export interface UseAdminSingletonOptions<T, F> {
  /** Carga el registro. Debe retornar `null` si todavía no existe (404). */
  loader: () => Promise<T | null>;
  /** Guarda el form. `isCreating` true cuando el record no existía. */
  saver: (payload: object, isCreating: boolean) => Promise<T>;
  /** Estado inicial del formulario (para crear). */
  emptyForm: F;
  /** Cómo poblar el formulario tras cargar el registro. */
  toFormState: (item: T) => F;
  /** Cómo construir el payload del save. */
  toPayload: (form: F) => object;
  /** Mensaje de error humano para load fail. */
  loadErrorMessage?: string;
  /** Mensaje de error humano para save fail. */
  saveErrorMessage?: string;
}

export interface UseAdminSingletonReturn<T, F> {
  // Datos
  record: T | null;
  loading: boolean;
  loadError: string | null;

  // Form state
  form: F;
  setForm: React.Dispatch<React.SetStateAction<F>>;
  saving: boolean;
  saveError: string | null;
  saveOk: boolean;

  // Computed
  /** True si todavía no se ha cargado nada O todavía no existe en BD. */
  isCreating: boolean;
  /** True si el formulario tiene cambios sin guardar respecto al record cargado. */
  dirty: boolean;

  // Acciones
  handleSubmit: (e: FormEvent) => Promise<void>;
  refetch: () => Promise<void>;
}

export function useAdminSingleton<T, F>(
  options: UseAdminSingletonOptions<T, F>,
): UseAdminSingletonReturn<T, F> {
  const {
    loader,
    saver,
    emptyForm,
    toFormState,
    toPayload,
    loadErrorMessage = "Error cargando los datos",
    saveErrorMessage = "Error al guardar",
  } = options;

  const [record, setRecord] = useState<T | null>(null);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);

  const [form, setForm] = useState<F>(emptyForm);
  const [saving, setSaving] = useState(false);
  const [saveError, setSaveError] = useState<string | null>(null);
  const [saveOk, setSaveOk] = useState(false);

  const refetch = useCallback(async () => {
    setLoading(true);
    setLoadError(null);
    try {
      const data = await loader();
      if (data) {
        setRecord(data);
        setForm(toFormState(data));
      } else {
        setRecord(null);
        setForm(emptyForm);
      }
    } catch (err) {
      setLoadError(err instanceof Error ? err.message : loadErrorMessage);
    } finally {
      setLoading(false);
    }
  }, [loader, toFormState, emptyForm, loadErrorMessage]);

  useEffect(() => {
    refetch();
  }, [refetch]);

  const isCreating = record === null;

  const dirty = (() => {
    if (record === null) {
      // sin record: dirty si el form difiere del emptyForm
      return JSON.stringify(form) !== JSON.stringify(emptyForm);
    }
    return JSON.stringify(form) !== JSON.stringify(toFormState(record));
  })();

  const handleSubmit = useCallback(
    async (e: FormEvent) => {
      e.preventDefault();
      setSaveError(null);
      setSaveOk(false);
      setSaving(true);
      try {
        const payload = toPayload(form);
        const updated = await saver(payload, isCreating);
        setRecord(updated);
        setForm(toFormState(updated));
        setSaveOk(true);
        setTimeout(() => setSaveOk(false), 3000);
      } catch (err) {
        setSaveError(err instanceof Error ? err.message : saveErrorMessage);
      } finally {
        setSaving(false);
      }
    },
    [form, saver, isCreating, toFormState, toPayload, saveErrorMessage],
  );

  return {
    record,
    loading,
    loadError,
    form,
    setForm,
    saving,
    saveError,
    saveOk,
    isCreating,
    dirty,
    handleSubmit,
    refetch,
  };
}
