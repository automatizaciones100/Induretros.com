"use client";

import { useEffect, useState, FormEvent } from "react";
import {
  Save,
  Loader2,
  AlertCircle,
  CheckCircle2,
  Globe,
  Building,
  Phone,
  Twitter,
  Image as ImageIcon,
  Mail,
  MapPin,
  Clock,
  MessageCircle,
  Facebook,
  Instagram,
  Youtube,
  Sparkles,
} from "lucide-react";
import { authFetch } from "@/lib/authFetch";
import GoogleSerpPreview from "@/components/admin/GoogleSerpPreview";
import HeroPreview from "@/components/admin/HeroPreview";

interface SiteSettings {
  site_title?: string | null;
  title_template?: string | null;
  default_description?: string | null;
  default_keywords?: string | null;
  default_og_image?: string | null;
  twitter_handle?: string | null;
  organization_name?: string | null;
  organization_phone?: string | null;
  contact_email?: string | null;
  contact_address?: string | null;
  contact_business_hours?: string | null;
  whatsapp_number?: string | null;
  facebook_url?: string | null;
  instagram_url?: string | null;
  youtube_url?: string | null;
  tiktok_url?: string | null;
  linkedin_url?: string | null;
  hero_label?: string | null;
  hero_title?: string | null;
  hero_subtitle?: string | null;
  hero_cta_text?: string | null;
  hero_cta_url?: string | null;
  hero_cta2_text?: string | null;
  hero_cta2_url?: string | null;
  hero_image_url?: string | null;
}

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || "https://induretros.com";

export default function AdminConfiguracionPage() {
  const [form, setForm] = useState<SiteSettings>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [savedAt, setSavedAt] = useState<Date | null>(null);

  useEffect(() => {
    authFetch("/api/admin/site-settings")
      .then((r) => (r.ok ? r.json() : Promise.reject(`Error ${r.status}`)))
      .then(setForm)
      .catch((err) =>
        setError(typeof err === "string" ? err : err instanceof Error ? err.message : "Error cargando settings")
      )
      .finally(() => setLoading(false));
  }, []);

  const update = <K extends keyof SiteSettings>(key: K, value: SiteSettings[K]) => {
    setForm((f) => ({ ...f, [key]: value }));
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    setSaving(true);

    const payload: Record<string, string | null> = {
      site_title: form.site_title?.trim() || null,
      title_template: form.title_template?.trim() || null,
      default_description: form.default_description?.trim() || null,
      default_keywords: form.default_keywords?.trim() || null,
      default_og_image: form.default_og_image?.trim() || null,
      twitter_handle: form.twitter_handle?.trim() || null,
      organization_name: form.organization_name?.trim() || null,
      organization_phone: form.organization_phone?.trim() || null,
      contact_email: form.contact_email?.trim() || null,
      contact_address: form.contact_address?.trim() || null,
      contact_business_hours: form.contact_business_hours?.trim() || null,
      whatsapp_number: form.whatsapp_number?.replace(/\D/g, "") || null,
      facebook_url: form.facebook_url?.trim() || null,
      instagram_url: form.instagram_url?.trim() || null,
      youtube_url: form.youtube_url?.trim() || null,
      tiktok_url: form.tiktok_url?.trim() || null,
      linkedin_url: form.linkedin_url?.trim() || null,
      hero_label: form.hero_label?.trim() || null,
      hero_title: form.hero_title?.trim() || null,
      hero_subtitle: form.hero_subtitle?.trim() || null,
      hero_cta_text: form.hero_cta_text?.trim() || null,
      hero_cta_url: form.hero_cta_url?.trim() || null,
      hero_cta2_text: form.hero_cta2_text?.trim() || null,
      hero_cta2_url: form.hero_cta2_url?.trim() || null,
      hero_image_url: form.hero_image_url?.trim() || null,
    };

    try {
      const res = await authFetch("/api/admin/site-settings", {
        method: "PUT",
        body: JSON.stringify(payload),
      });
      if (!res.ok) {
        const body = await res.json().catch(() => ({}));
        throw new Error(body.detail || `Error ${res.status}`);
      }
      const fresh = await res.json();
      setForm(fresh);
      setSavedAt(new Date());
      setTimeout(() => setSavedAt(null), 3000);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Error al guardar");
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="p-8 flex items-center gap-2 text-gray-mid font-sans">
        <Loader2 size={18} className="animate-spin" />
        Cargando configuración…
      </div>
    );
  }

  // Generar preview del título tal como saldría en Google (template aplicado)
  const previewTitle = form.title_template
    ? form.title_template.replace("%s", form.site_title || "")
    : form.site_title || "";

  return (
    <div className="p-6 lg:p-8 max-w-4xl mx-auto">
      <div className="mb-6">
        <h1 className="font-heading text-2xl font-semibold text-dark-2 uppercase">
          Configuración del sitio
        </h1>
        <p className="text-sm text-gray-mid font-sans mt-1">
          Valores por defecto que se aplican a toda la tienda. Los productos pueden sobrescribirlos individualmente.
        </p>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Hero del home */}
        <Section title="Hero del home (lo primero que ve el visitante)" icon={Sparkles}>
          <p className="text-xs text-gray-light font-sans -mt-2">
            Es la sección grande que aparece al entrar a induretros.com. Cambias acá → cambia el home.
          </p>

          <Field label="Etiqueta superior" hint="Mini-tag pequeño en mayúsculas. Ej. 'Importadores directos'">
            <input
              type="text"
              maxLength={80}
              value={form.hero_label ?? ""}
              onChange={(e) => update("hero_label", e.target.value)}
              placeholder="Importadores directos"
              className="input-field"
            />
          </Field>

          <Field label="Título principal" hint="El título grande del hero. Hasta 150 caracteres.">
            <textarea
              rows={2}
              maxLength={150}
              value={form.hero_title ?? ""}
              onChange={(e) => update("hero_title", e.target.value)}
              placeholder="Repuestos para Excavadoras Hidráulicas"
              className="input-field resize-none"
            />
          </Field>

          <Field label="Subtítulo / descripción" hint="Párrafo descriptivo bajo el título. Hasta 400 caracteres.">
            <textarea
              rows={3}
              maxLength={400}
              value={form.hero_subtitle ?? ""}
              onChange={(e) => update("hero_subtitle", e.target.value)}
              placeholder="Más de 9 años importando..."
              className="input-field resize-none"
            />
          </Field>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Botón principal — texto" hint="Ej. 'Ver catálogo'">
              <input
                type="text"
                maxLength={50}
                value={form.hero_cta_text ?? ""}
                onChange={(e) => update("hero_cta_text", e.target.value)}
                placeholder="Ver catálogo"
                className="input-field"
              />
            </Field>
            <Field label="Botón principal — destino" hint="Ruta interna ('/repuestos') o URL completa">
              <input
                type="text"
                maxLength={200}
                value={form.hero_cta_url ?? ""}
                onChange={(e) => update("hero_cta_url", e.target.value)}
                placeholder="/repuestos"
                className="input-field font-mono text-xs"
              />
            </Field>
            <Field label="Botón secundario — texto" hint="Ej. 'Cotizar por WhatsApp'">
              <input
                type="text"
                maxLength={50}
                value={form.hero_cta2_text ?? ""}
                onChange={(e) => update("hero_cta2_text", e.target.value)}
                placeholder="Cotizar por WhatsApp"
                className="input-field"
              />
            </Field>
            <Field
              label="Botón secundario — destino"
              hint="Usa 'whatsapp:default' para abrir el WhatsApp configurado en la sección Contacto"
            >
              <input
                type="text"
                maxLength={200}
                value={form.hero_cta2_url ?? ""}
                onChange={(e) => update("hero_cta2_url", e.target.value)}
                placeholder="whatsapp:default"
                className="input-field font-mono text-xs"
              />
            </Field>
          </div>

          <Field label="Imagen lateral del hero" hint="URL relativa (/imagen.webp) o absoluta (https://...)">
            <div className="relative">
              <ImageIcon size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none" />
              <input
                type="text"
                maxLength={500}
                value={form.hero_image_url ?? ""}
                onChange={(e) => update("hero_image_url", e.target.value)}
                placeholder="/noshadow-excabadora-768x576.webp"
                className="input-field pl-9 font-mono text-xs"
              />
            </div>
          </Field>

          {/* Vista previa del hero — antes de guardar */}
          <div>
            <p className="text-xs uppercase tracking-wide text-gray-mid font-sans mb-2">
              Vista previa (cómo se verá en el home antes de guardar)
            </p>
            <HeroPreview
              label={form.hero_label}
              title={form.hero_title}
              subtitle={form.hero_subtitle}
              ctaText={form.hero_cta_text}
              ctaUrl={form.hero_cta_url}
              cta2Text={form.hero_cta2_text}
              cta2Url={form.hero_cta2_url}
              imageUrl={form.hero_image_url}
            />
          </div>
        </Section>

        {/* SEO Global */}
        <Section title="SEO global" icon={Globe}>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Nombre del sitio" hint="Ej. 'Induretros'">
              <input
                type="text"
                maxLength={100}
                value={form.site_title ?? ""}
                onChange={(e) => update("site_title", e.target.value)}
                className="input-field"
              />
            </Field>
            <Field label="Plantilla de título" hint="Use %s para el título de página. Ej. '%s | Induretros'">
              <input
                type="text"
                maxLength={120}
                value={form.title_template ?? ""}
                onChange={(e) => update("title_template", e.target.value)}
                className="input-field font-mono text-xs"
              />
            </Field>
          </div>

          <Field
            label={`Descripción por defecto (${form.default_description?.length ?? 0}/200)`}
            hint="Se usa en el home y como fallback cuando una página no tiene description"
          >
            <textarea
              rows={3}
              maxLength={200}
              value={form.default_description ?? ""}
              onChange={(e) => update("default_description", e.target.value)}
              className="input-field resize-none"
            />
          </Field>

          <Field label="Palabras clave (separadas por comas)" hint="Ej. 'repuestos excavadoras, hidráulica, Colombia'">
            <input
              type="text"
              maxLength={500}
              value={form.default_keywords ?? ""}
              onChange={(e) => update("default_keywords", e.target.value)}
              className="input-field"
            />
          </Field>

          <Field
            label="Imagen Open Graph por defecto"
            hint="Aparece al compartir el sitio en WhatsApp, Facebook, etc. Tamaño recomendado: 1200×630"
          >
            <div className="relative">
              <ImageIcon
                size={14}
                className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none"
              />
              <input
                type="text"
                maxLength={500}
                value={form.default_og_image ?? ""}
                onChange={(e) => update("default_og_image", e.target.value)}
                placeholder="/static/images/og-default.jpg"
                className="input-field pl-9 font-mono text-xs"
              />
            </div>
          </Field>

          <Field label="Usuario de Twitter / X" hint="Sin la @ — ej. 'induretros'">
            <div className="relative">
              <Twitter
                size={14}
                className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none"
              />
              <input
                type="text"
                maxLength={50}
                value={form.twitter_handle ?? ""}
                onChange={(e) => update("twitter_handle", e.target.value.replace(/^@/, ""))}
                placeholder="induretros"
                className="input-field pl-9"
              />
            </div>
          </Field>
        </Section>

        {/* Datos de la organización (para JSON-LD) */}
        <Section title="Organización (para datos estructurados)" icon={Building}>
          <p className="text-xs text-gray-light font-sans -mt-2">
            Estos datos se usan en el Schema.org para que Google reconozca tu marca en los resultados.
          </p>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Razón social">
              <input
                type="text"
                maxLength={120}
                value={form.organization_name ?? ""}
                onChange={(e) => update("organization_name", e.target.value)}
                className="input-field"
              />
            </Field>
            <Field label="Teléfono">
              <div className="relative">
                <Phone
                  size={14}
                  className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none"
                />
                <input
                  type="tel"
                  maxLength={30}
                  value={form.organization_phone ?? ""}
                  onChange={(e) => update("organization_phone", e.target.value)}
                  className="input-field pl-9"
                />
              </div>
            </Field>
          </div>
        </Section>

        {/* Contacto público */}
        <Section title="Contacto público (toda la web)" icon={Phone}>
          <p className="text-xs text-gray-light font-sans -mt-2">
            Estos datos aparecen en el header, footer, página de contacto y página de producto. Cambias acá → cambia en todo el sitio.
          </p>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Email de contacto" hint="Aparece en footer y /contacto">
              <div className="relative">
                <Mail size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none" />
                <input
                  type="email"
                  maxLength={120}
                  value={form.contact_email ?? ""}
                  onChange={(e) => update("contact_email", e.target.value)}
                  placeholder="ventas@induretros.com"
                  className="input-field pl-9"
                />
              </div>
            </Field>
            <Field label="Número de WhatsApp" hint="Solo dígitos con código país. Ej. 573007192973">
              <div className="relative">
                <MessageCircle size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none" />
                <input
                  type="tel"
                  maxLength={30}
                  value={form.whatsapp_number ?? ""}
                  onChange={(e) => update("whatsapp_number", e.target.value.replace(/\D/g, ""))}
                  placeholder="573007192973"
                  className="input-field pl-9 font-mono text-xs"
                />
              </div>
            </Field>
          </div>
          <Field label="Dirección física" hint="Aparece en footer y /contacto">
            <div className="relative">
              <MapPin size={14} className="absolute left-3 top-3 text-gray-light pointer-events-none" />
              <input
                type="text"
                maxLength={300}
                value={form.contact_address ?? ""}
                onChange={(e) => update("contact_address", e.target.value)}
                placeholder="Centro Empresarial Promisión, Medellín, Colombia"
                className="input-field pl-9"
              />
            </div>
          </Field>
          <Field label="Horario de atención" hint="Aparece en /contacto">
            <div className="relative">
              <Clock size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none" />
              <input
                type="text"
                maxLength={150}
                value={form.contact_business_hours ?? ""}
                onChange={(e) => update("contact_business_hours", e.target.value)}
                placeholder="Lunes a Viernes: 7:00 am - 5:00 pm"
                className="input-field pl-9"
              />
            </div>
          </Field>
        </Section>

        {/* Redes sociales */}
        <Section title="Redes sociales" icon={Facebook}>
          <p className="text-xs text-gray-light font-sans -mt-2">
            URLs completas con https://. Si dejas vacío un campo, el ícono no aparece en el sitio.
          </p>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Field label="Facebook">
              <div className="relative">
                <Facebook size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none" />
                <input
                  type="url"
                  maxLength={300}
                  value={form.facebook_url ?? ""}
                  onChange={(e) => update("facebook_url", e.target.value)}
                  placeholder="https://www.facebook.com/induretros"
                  className="input-field pl-9 text-xs"
                />
              </div>
            </Field>
            <Field label="Instagram">
              <div className="relative">
                <Instagram size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none" />
                <input
                  type="url"
                  maxLength={300}
                  value={form.instagram_url ?? ""}
                  onChange={(e) => update("instagram_url", e.target.value)}
                  placeholder="https://www.instagram.com/induretros"
                  className="input-field pl-9 text-xs"
                />
              </div>
            </Field>
            <Field label="YouTube">
              <div className="relative">
                <Youtube size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-light pointer-events-none" />
                <input
                  type="url"
                  maxLength={300}
                  value={form.youtube_url ?? ""}
                  onChange={(e) => update("youtube_url", e.target.value)}
                  placeholder="https://www.youtube.com/@induretros"
                  className="input-field pl-9 text-xs"
                />
              </div>
            </Field>
            <Field label="TikTok">
              <input
                type="url"
                maxLength={300}
                value={form.tiktok_url ?? ""}
                onChange={(e) => update("tiktok_url", e.target.value)}
                placeholder="https://www.tiktok.com/@induretros"
                className="input-field text-xs"
              />
            </Field>
            <Field label="LinkedIn">
              <input
                type="url"
                maxLength={300}
                value={form.linkedin_url ?? ""}
                onChange={(e) => update("linkedin_url", e.target.value)}
                placeholder="https://www.linkedin.com/company/induretros"
                className="input-field text-xs"
              />
            </Field>
          </div>
        </Section>

        {/* Preview Google */}
        <Section title="Vista previa en Google (home)" icon={Globe}>
          <GoogleSerpPreview
            url={SITE_URL}
            title={previewTitle || "Induretros"}
            description={form.default_description || ""}
          />
        </Section>

        {/* Errores y confirmación */}
        {error && (
          <div className="bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-700 font-sans flex items-start gap-2">
            <AlertCircle size={16} className="flex-shrink-0 mt-0.5" />
            {error}
          </div>
        )}

        {savedAt && (
          <div className="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-700 font-sans flex items-center gap-2">
            <CheckCircle2 size={16} />
            Configuración guardada correctamente.
          </div>
        )}

        {/* Acciones */}
        <div className="flex gap-3 pt-2">
          <button
            type="submit"
            disabled={saving}
            className="btn-primary disabled:opacity-60 disabled:cursor-not-allowed"
          >
            {saving ? <Loader2 size={14} className="animate-spin" /> : <Save size={14} />}
            Guardar configuración
          </button>
        </div>
      </form>
    </div>
  );
}

function Section({
  title,
  icon: Icon,
  children,
}: {
  title: string;
  icon?: React.ComponentType<{ size?: number; className?: string }>;
  children: React.ReactNode;
}) {
  return (
    <section className="bg-white border border-gray-100 rounded-xl p-6 space-y-4">
      <h2 className="font-heading text-base font-semibold text-dark-2 uppercase flex items-center gap-2">
        {Icon && <Icon size={16} className="text-primary" />}
        {title}
      </h2>
      {children}
    </section>
  );
}

function Field({
  label,
  hint,
  children,
}: {
  label: string;
  hint?: string;
  children: React.ReactNode;
}) {
  return (
    <div>
      <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
        {label}
      </label>
      {children}
      {hint && <p className="text-xs text-gray-light font-sans mt-1">{hint}</p>}
    </div>
  );
}
