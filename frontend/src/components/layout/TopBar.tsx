import { Phone, Mail, MapPin, Facebook, Instagram, Youtube } from "lucide-react";
import { getSiteSettings, telLink, mailtoLink } from "@/lib/siteSettings";

export default async function TopBar() {
  const s = await getSiteSettings();

  return (
    <div className="bg-dark-2 text-gray-light text-sm py-2">
      <div className="container mx-auto flex flex-col sm:flex-row justify-between items-center gap-2">
        {/* Contacto */}
        <div className="flex flex-wrap items-center gap-4">
          {s.organization_phone && (
            <a href={telLink(s.organization_phone)} className="top-bar-link flex items-center gap-1.5">
              <Phone size={13} />
              <span>{s.organization_phone}</span>
            </a>
          )}
          {s.contact_email && (
            <a href={mailtoLink(s.contact_email)} className="top-bar-link flex items-center gap-1.5">
              <Mail size={13} />
              <span>{s.contact_email}</span>
            </a>
          )}
          {s.contact_address && (
            <span className="top-bar-link hidden md:flex items-center gap-1.5">
              <MapPin size={13} />
              <span>{s.contact_address.split(",")[0]}</span>
            </span>
          )}
        </div>

        {/* Redes sociales */}
        <div className="flex items-center gap-3">
          {s.facebook_url && (
            <a href={s.facebook_url} target="_blank" rel="noopener noreferrer" className="top-bar-link hover:text-primary" aria-label="Facebook">
              <Facebook size={15} />
            </a>
          )}
          {s.instagram_url && (
            <a href={s.instagram_url} target="_blank" rel="noopener noreferrer" className="top-bar-link hover:text-primary" aria-label="Instagram">
              <Instagram size={15} />
            </a>
          )}
          {s.youtube_url && (
            <a href={s.youtube_url} target="_blank" rel="noopener noreferrer" className="top-bar-link hover:text-primary" aria-label="YouTube">
              <Youtube size={15} />
            </a>
          )}
          <a href="/login" className="top-bar-link hover:text-primary ml-2 border-l border-gray-600 pl-3">
            Iniciar sesión
          </a>
        </div>
      </div>
    </div>
  );
}
