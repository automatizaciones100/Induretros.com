"use client";

import { Globe } from "lucide-react";

interface Props {
  url: string;
  title: string;
  description: string;
}

const TITLE_MAX = 60; // Google muestra ~580px ≈ 60 chars
const DESC_MAX = 160; // Google trunca alrededor de 155-160 chars

function truncate(s: string, max: number): string {
  if (s.length <= max) return s;
  return s.slice(0, max - 1).trimEnd() + "…";
}

/**
 * Imita el aspecto de un resultado en Google SERP.
 * Útil para que el admin vea cómo aparecerá su página en buscadores.
 */
export default function GoogleSerpPreview({ url, title, description }: Props) {
  const displayTitle = title || "(sin título)";
  const displayDesc = description || "(sin descripción)";
  const titleTooLong = displayTitle.length > TITLE_MAX;
  const descTooLong = displayDesc.length > DESC_MAX;

  // Formatear URL como el breadcrumb de Google: dominio > ruta
  let breadcrumb = url;
  try {
    const u = new URL(url);
    const parts = u.pathname.split("/").filter(Boolean);
    breadcrumb = `${u.host}${parts.length ? " › " + parts.join(" › ") : ""}`;
  } catch {
    // url inválida — usar como string
  }

  return (
    <div className="bg-white border border-gray-200 rounded-lg p-4 font-sans">
      <p className="text-xs text-gray-light mb-2 flex items-center gap-1">
        <Globe size={11} />
        Vista previa en Google
      </p>

      <div className="bg-bg-light rounded p-4 space-y-1">
        {/* URL breadcrumb (Google muestra esto arriba del título) */}
        <p className="text-xs text-[#202124] flex items-center gap-1">
          <span className="inline-block w-4 h-4 rounded-full bg-gray-300 mr-1" />
          {breadcrumb}
        </p>

        {/* Título azul (estilo Google) */}
        <h3
          className="text-[#1a0dab] text-lg leading-tight hover:underline cursor-pointer"
          style={{ fontFamily: "arial, sans-serif" }}
        >
          {truncate(displayTitle, TITLE_MAX)}
        </h3>

        {/* Descripción gris */}
        <p
          className="text-sm text-[#4d5156] leading-snug"
          style={{ fontFamily: "arial, sans-serif" }}
        >
          {truncate(displayDesc, DESC_MAX)}
        </p>
      </div>

      {/* Avisos de longitud */}
      {(titleTooLong || descTooLong) && (
        <div className="mt-2 space-y-1">
          {titleTooLong && (
            <p className="text-xs text-amber-700">
              ⚠ Título muy largo ({displayTitle.length}/{TITLE_MAX}). Google lo cortará.
            </p>
          )}
          {descTooLong && (
            <p className="text-xs text-amber-700">
              ⚠ Descripción muy larga ({displayDesc.length}/{DESC_MAX}). Google la cortará.
            </p>
          )}
        </div>
      )}
    </div>
  );
}
