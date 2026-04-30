"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { X } from "lucide-react";
import type { Announcement, AnnouncementTheme } from "@/lib/announcements";

const THEME_CLASSES: Record<AnnouncementTheme, string> = {
  info:    "bg-blue-600 text-white",
  promo:   "bg-primary text-white",
  warning: "bg-yellow-500 text-dark-2",
  success: "bg-green-600 text-white",
  alert:   "bg-red-600 text-white",
  dark:    "bg-dark-2 text-white",
};

const STORAGE_KEY = "induretros-dismissed-announcements";

function getDismissed(): Set<number> {
  if (typeof window === "undefined") return new Set();
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return new Set();
    const arr = JSON.parse(raw);
    return new Set(Array.isArray(arr) ? arr.filter((x) => typeof x === "number") : []);
  } catch {
    return new Set();
  }
}

function addDismissed(id: number): void {
  const ids = getDismissed();
  ids.add(id);
  localStorage.setItem(STORAGE_KEY, JSON.stringify([...ids]));
}

interface Props {
  announcements: Announcement[];
}

export default function AnnouncementBar({ announcements }: Props) {
  const [hydrated, setHydrated] = useState(false);
  const [dismissed, setDismissed] = useState<Set<number>>(new Set());

  useEffect(() => {
    setHydrated(true);
    setDismissed(getDismissed());
  }, []);

  // Antes de hidratar, renderizamos el primer anuncio (no rompe SSR);
  // después de hidratar, filtramos por dismissed.
  const visible = hydrated
    ? announcements.filter((a) => !dismissed.has(a.id))
    : announcements;

  const top = visible[0];
  if (!top) return null;

  const handleDismiss = () => {
    addDismissed(top.id);
    setDismissed(new Set([...dismissed, top.id]));
  };

  const themeClass = THEME_CLASSES[top.theme] || THEME_CLASSES.dark;
  const linkClass = top.theme === "warning"
    ? "underline hover:no-underline ml-2 font-semibold"
    : "underline hover:no-underline ml-2 font-semibold opacity-90 hover:opacity-100";

  return (
    <div className={`${themeClass} text-sm font-sans relative`}>
      <div className="container mx-auto py-2 px-4 pr-10 flex items-center justify-center text-center gap-1">
        <span className="leading-snug">{top.text}</span>
        {top.link_url && top.link_text && (
          top.link_url.startsWith("/") ? (
            <Link href={top.link_url} className={linkClass}>{top.link_text}</Link>
          ) : (
            <a href={top.link_url} target="_blank" rel="noopener noreferrer" className={linkClass}>
              {top.link_text}
            </a>
          )
        )}
      </div>
      {top.dismissible && (
        <button
          onClick={handleDismiss}
          className="absolute right-3 top-1/2 -translate-y-1/2 hover:bg-black/10 rounded p-1"
          aria-label="Cerrar anuncio"
        >
          <X size={16} />
        </button>
      )}
    </div>
  );
}
