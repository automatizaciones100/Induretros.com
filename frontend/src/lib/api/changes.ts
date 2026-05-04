/**
 * API client del historial de cambios admin (rollback / undo).
 * Endpoints en backend/app/presentation/routers/changes.py.
 */
import { authFetch } from "@/lib/authFetch";
import { throwIfNotOk } from "./errors";
import type { ChangeLogPage } from "./types";

export async function listChanges(params: {
  entity_type?: string;
  limit?: number;
  offset?: number;
} = {}): Promise<ChangeLogPage> {
  const search = new URLSearchParams();
  if (params.entity_type) search.set("entity_type", params.entity_type);
  if (params.limit !== undefined) search.set("limit", String(params.limit));
  if (params.offset !== undefined) search.set("offset", String(params.offset));
  const qs = search.toString();
  const res = await authFetch(`/api/admin/changes${qs ? `?${qs}` : ""}`);
  await throwIfNotOk(res);
  return res.json();
}

export async function restoreChange(changeId: number): Promise<{
  ok: boolean;
  change_id: number;
  entity_type: string;
  entity_id: string;
}> {
  const res = await authFetch(`/api/admin/changes/${changeId}/restore`, {
    method: "POST",
  });
  await throwIfNotOk(res);
  return res.json();
}
