/**
 * API client para el usuario autenticado.
 * Endpoints en backend/app/presentation/routers/users.py.
 */
import { authFetch } from "@/lib/authFetch";
import { throwIfNotOk } from "./errors";
import type { UserMe } from "./types";

export async function getMe(): Promise<UserMe> {
  const res = await authFetch("/api/users/me");
  await throwIfNotOk(res);
  return res.json();
}

export async function changePassword(payload: {
  current_password: string;
  new_password: string;
}): Promise<void> {
  const res = await authFetch("/api/users/me/change-password", {
    method: "POST",
    body: JSON.stringify(payload),
  });
  await throwIfNotOk(res);
}

export async function deleteAccount(payload: { password: string }): Promise<void> {
  const res = await authFetch("/api/users/me", {
    method: "DELETE",
    body: JSON.stringify(payload),
  });
  await throwIfNotOk(res);
}
