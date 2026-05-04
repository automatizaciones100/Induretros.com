/**
 * Helpers compartidos para parseo de errores de FastAPI/Pydantic.
 * `detail` puede ser string o array de errores de validación.
 */
interface ValidationDetail {
  msg: string;
  loc: string[];
}

export function extractErrorMessage(body: unknown, fallback: string): string {
  if (typeof body === "object" && body !== null && "detail" in body) {
    const detail = (body as { detail: unknown }).detail;
    if (Array.isArray(detail)) {
      return (detail as ValidationDetail[])
        .map((e) => `${e.loc.slice(-1)[0]}: ${e.msg}`)
        .join(" · ");
    }
    if (typeof detail === "string") return detail;
  }
  return fallback;
}

export async function readJsonOrEmpty(res: Response): Promise<unknown> {
  try {
    return await res.json();
  } catch {
    return {};
  }
}

/** Lanza Error si la respuesta no fue ok, leyendo `detail` del body. */
export async function throwIfNotOk(res: Response, fallback?: string): Promise<void> {
  if (res.ok) return;
  const body = await readJsonOrEmpty(res);
  throw new Error(extractErrorMessage(body, fallback || `Error ${res.status}`));
}
