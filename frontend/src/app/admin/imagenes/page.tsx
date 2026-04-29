"use client";

import { useEffect, useState, useRef, ChangeEvent, DragEvent } from "react";
import Image from "next/image";
import {
  Upload,
  Loader2,
  AlertCircle,
  CheckCircle2,
  XCircle,
  Image as ImageIcon,
  Info,
  RefreshCcw,
} from "lucide-react";
import { useAuthStore } from "@/stores/authStore";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

interface UploadResult {
  file: string;
  status: "ok" | "not_found" | "error";
  detail?: string;
  product_slug?: string;
  product_name?: string;
  image_url?: string;
}

interface UploadResponse {
  summary: { total: number; ok: number; errors: number };
  results: UploadResult[];
}

interface ImageItem {
  filename: string;
  url: string;
  size_kb: number;
}

const MAX_FILES = 50;

export default function AdminImagenesPage() {
  const token = useAuthStore((s) => s.token);

  const fileInputRef = useRef<HTMLInputElement>(null);
  const [dragOver, setDragOver] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [report, setReport] = useState<UploadResponse | null>(null);
  const [error, setError] = useState<string | null>(null);

  const [gallery, setGallery] = useState<ImageItem[]>([]);
  const [galleryLoading, setGalleryLoading] = useState(true);

  const fetchGallery = async () => {
    setGalleryLoading(true);
    try {
      const res = await fetch(`${API_URL}/api/images/list`, {
        headers: token ? { Authorization: `Bearer ${token}` } : {},
      });
      if (!res.ok) throw new Error(`Error ${res.status}`);
      const json = await res.json();
      setGallery(json.images || []);
    } catch {
      setGallery([]);
    } finally {
      setGalleryLoading(false);
    }
  };

  useEffect(() => {
    fetchGallery();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const handleFiles = async (files: FileList | File[]) => {
    const arr = Array.from(files);
    if (arr.length === 0) return;

    if (arr.length > MAX_FILES) {
      setError(`Máximo ${MAX_FILES} archivos por carga. Seleccionaste ${arr.length}.`);
      return;
    }

    setUploading(true);
    setError(null);
    setReport(null);

    const fd = new FormData();
    arr.forEach((f) => fd.append("files", f));

    try {
      const res = await fetch(`${API_URL}/api/images/upload`, {
        method: "POST",
        headers: token ? { Authorization: `Bearer ${token}` } : {},
        body: fd,
      });
      if (!res.ok && res.status !== 207) {
        const body = await res.json().catch(() => ({}));
        throw new Error(body.detail || `Error ${res.status}`);
      }
      const json: UploadResponse = await res.json();
      setReport(json);
      // Refrescar galería
      fetchGallery();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error subiendo archivos");
    } finally {
      setUploading(false);
      if (fileInputRef.current) fileInputRef.current.value = "";
    }
  };

  const onFileChange = (e: ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) handleFiles(e.target.files);
  };

  const onDrop = (e: DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    setDragOver(false);
    if (e.dataTransfer.files) handleFiles(e.dataTransfer.files);
  };

  return (
    <div className="p-6 lg:p-8 max-w-6xl mx-auto">
      {/* Encabezado */}
      <div className="mb-6">
        <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">
          Carga masiva de imágenes
        </h1>
        <p className="text-sm text-gray-mid font-sans mt-1">
          Sube imágenes de producto y se enlazarán automáticamente por nombre de archivo.
        </p>
      </div>

      {/* Instrucciones */}
      <div className="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6 flex gap-3">
        <Info size={18} className="text-blue-600 flex-shrink-0 mt-0.5" />
        <div className="text-sm font-sans text-blue-900 space-y-1.5">
          <p className="font-semibold">Convención de nombres</p>
          <ul className="list-disc list-inside space-y-0.5 text-blue-800">
            <li>
              <code className="bg-blue-100 px-1.5 py-0.5 rounded text-xs">FLT-001.jpg</code> → busca producto con SKU <strong>FLT-001</strong>
            </li>
            <li>
              <code className="bg-blue-100 px-1.5 py-0.5 rounded text-xs">filtro-aceite-komatsu.webp</code> → busca producto con slug <strong>filtro-aceite-komatsu</strong>
            </li>
          </ul>
          <p className="text-xs text-blue-700 mt-2">
            Formatos: jpg, jpeg, png, webp, avif · Máx. 5 MB por archivo · Hasta {MAX_FILES} archivos por carga
          </p>
        </div>
      </div>

      {/* Drop zone */}
      <div
        onDragOver={(e) => {
          e.preventDefault();
          setDragOver(true);
        }}
        onDragLeave={() => setDragOver(false)}
        onDrop={onDrop}
        onClick={() => fileInputRef.current?.click()}
        className={`bg-white border-2 border-dashed rounded-xl p-10 text-center cursor-pointer transition-colors ${
          dragOver
            ? "border-primary bg-primary/5"
            : "border-gray-300 hover:border-primary hover:bg-bg-light"
        } ${uploading ? "pointer-events-none opacity-60" : ""}`}
      >
        <input
          ref={fileInputRef}
          type="file"
          multiple
          accept=".jpg,.jpeg,.png,.webp,.avif,image/*"
          onChange={onFileChange}
          className="hidden"
        />
        {uploading ? (
          <>
            <Loader2 size={32} className="animate-spin text-primary mx-auto mb-3" />
            <p className="font-heading font-semibold text-dark-2">Subiendo imágenes…</p>
          </>
        ) : (
          <>
            <Upload size={32} className="text-gray-light mx-auto mb-3" />
            <p className="font-heading font-semibold text-dark-2 mb-1">
              Arrastra imágenes aquí o haz click para seleccionar
            </p>
            <p className="text-xs text-gray-light font-sans">
              Acepta hasta {MAX_FILES} archivos a la vez
            </p>
          </>
        )}
      </div>

      {/* Errores generales */}
      {error && (
        <div className="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-700 font-sans flex items-start gap-2 mt-6">
          <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
          {error}
        </div>
      )}

      {/* Reporte de subida */}
      {report && (
        <div className="bg-white border border-gray-100 rounded-xl p-6 mt-6">
          <div className="flex items-center gap-3 mb-4">
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
              Resultado de la carga
            </h2>
            <div className="flex gap-2 text-xs font-sans">
              <span className="bg-green-100 text-green-700 px-2 py-0.5 rounded font-semibold">
                {report.summary.ok} OK
              </span>
              {report.summary.errors > 0 && (
                <span className="bg-red-100 text-red-700 px-2 py-0.5 rounded font-semibold">
                  {report.summary.errors} con problemas
                </span>
              )}
            </div>
          </div>

          <ul className="divide-y divide-gray-100 -mx-2">
            {report.results.map((r, idx) => (
              <li key={idx} className="px-2 py-2.5 flex items-start gap-3 text-sm font-sans">
                {r.status === "ok" ? (
                  <CheckCircle2 size={16} className="text-green-600 flex-shrink-0 mt-0.5" />
                ) : (
                  <XCircle size={16} className="text-red-500 flex-shrink-0 mt-0.5" />
                )}
                <div className="flex-1 min-w-0">
                  <p className="font-mono text-xs text-dark">{r.file}</p>
                  {r.status === "ok" && r.product_name ? (
                    <p className="text-xs text-gray-mid mt-0.5 line-clamp-1">
                      → <span className="text-green-700">{r.product_name}</span>
                    </p>
                  ) : (
                    <p className="text-xs text-red-600 mt-0.5">{r.detail}</p>
                  )}
                </div>
              </li>
            ))}
          </ul>
        </div>
      )}

      {/* Galería existente */}
      <div className="bg-white border border-gray-100 rounded-xl p-6 mt-8">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-2">
            <ImageIcon size={16} className="text-primary" />
            <h2 className="font-heading text-base font-semibold text-dark-2 uppercase">
              Imágenes en el servidor ({gallery.length})
            </h2>
          </div>
          <button
            onClick={fetchGallery}
            className="text-xs text-gray-mid hover:text-primary flex items-center gap-1 font-sans"
          >
            <RefreshCcw size={12} />
            Refrescar
          </button>
        </div>

        {galleryLoading ? (
          <div className="py-8 text-center">
            <Loader2 size={20} className="animate-spin text-gray-light mx-auto" />
          </div>
        ) : gallery.length === 0 ? (
          <p className="text-sm text-gray-light font-sans py-6 text-center">
            Aún no hay imágenes subidas.
          </p>
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
            {gallery.map((img) => (
              <div
                key={img.filename}
                className="bg-bg-light rounded-lg p-2 border border-gray-100"
              >
                <div className="relative aspect-square mb-2 bg-white rounded overflow-hidden">
                  <Image
                    src={`${API_URL}${img.url}`}
                    alt={img.filename}
                    fill
                    className="object-contain p-1"
                    sizes="160px"
                    unoptimized
                  />
                </div>
                <p
                  className="text-xs font-mono text-dark line-clamp-1"
                  title={img.filename}
                >
                  {img.filename}
                </p>
                <p className="text-[10px] text-gray-light font-sans mt-0.5">
                  {img.size_kb} KB
                </p>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
