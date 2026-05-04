import Link from "next/link";
import Image from "next/image";
import { Phone, Mail, MapPin, Facebook, Instagram, Youtube } from "lucide-react";
import { getSiteSettings, telLink, mailtoLink, whatsappLink } from "@/lib/siteSettings";

const productLinks = [
  { name: "Accesorios", href: "/repuestos?categoria=accesorios" },
  { name: "Balineras", href: "/repuestos?categoria=balineras" },
  { name: "Filtros", href: "/repuestos?categoria=filtros" },
  { name: "Partes hidráulicas", href: "/repuestos?categoria=partes-hidraulicas" },
  { name: "Partes eléctricas", href: "/repuestos?categoria=partes-electricas" },
  { name: "Piezas de desgaste", href: "/repuestos?categoria=piezas-de-desgaste" },
];

const infoLinks = [
  { name: "Repuestos", href: "/repuestos" },
  { name: "Preguntas frecuentes", href: "/faq" },
  { name: "Política de garantía", href: "/garantia" },
  { name: "Devoluciones y cambios", href: "/devoluciones" },
  { name: "Términos y condiciones", href: "/terminos" },
  { name: "Política de privacidad", href: "/privacidad" },
  { name: "Contáctanos", href: "/contacto" },
];

export default async function Footer() {
  const s = await getSiteSettings();

  return (
    <footer className="bg-dark-2 text-gray-light">
      <div className="container mx-auto py-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        {/* Columna 1: Empresa */}
        <div>
          <Image
            src="/Logo-nuevo-pagina-web.png"
            alt={s.site_title || "Induretros"}
            width={180}
            height={55}
            className="h-12 w-auto object-contain mb-4"
          />
          <p className="text-sm leading-relaxed mb-4 text-gray-400">
            Importadores directos de repuestos para excavadoras hidráulicas. Más de 9 años trabajando con excelencia.
          </p>
          <div className="flex items-center gap-3">
            {s.facebook_url && (
              <a href={s.facebook_url} target="_blank" rel="noopener noreferrer"
                 className="hover:text-primary transition-colors" aria-label="Facebook">
                <Facebook size={18} />
              </a>
            )}
            {s.instagram_url && (
              <a href={s.instagram_url} target="_blank" rel="noopener noreferrer"
                 className="hover:text-primary transition-colors" aria-label="Instagram">
                <Instagram size={18} />
              </a>
            )}
            {s.youtube_url && (
              <a href={s.youtube_url} target="_blank" rel="noopener noreferrer"
                 className="hover:text-primary transition-colors" aria-label="YouTube">
                <Youtube size={18} />
              </a>
            )}
          </div>
        </div>

        {/* Columna 2: Productos */}
        <div>
          <h4 className="font-heading font-semibold text-white text-base uppercase mb-4">Productos</h4>
          <ul className="space-y-2">
            {productLinks.map((link) => (
              <li key={link.href}>
                <Link href={link.href} className="text-sm text-gray-400 hover:text-primary transition-colors">
                  {link.name}
                </Link>
              </li>
            ))}
          </ul>
        </div>

        {/* Columna 3: Información */}
        <div>
          <h4 className="font-heading font-semibold text-white text-base uppercase mb-4">Información</h4>
          <ul className="space-y-2">
            {infoLinks.map((link) => (
              <li key={link.href}>
                <Link href={link.href} className="text-sm text-gray-400 hover:text-primary transition-colors">
                  {link.name}
                </Link>
              </li>
            ))}
          </ul>
        </div>

        {/* Columna 4: Contacto */}
        <div>
          <h4 className="font-heading font-semibold text-white text-base uppercase mb-4">Contacto</h4>
          <ul className="space-y-3">
            {s.organization_phone && (
              <li>
                <a href={telLink(s.organization_phone)} className="flex items-start gap-2.5 text-sm text-gray-400 hover:text-primary transition-colors">
                  <Phone size={15} className="mt-0.5 flex-shrink-0 text-primary" />
                  <span>{s.organization_phone}</span>
                </a>
              </li>
            )}
            {s.contact_email && (
              <li>
                <a href={mailtoLink(s.contact_email)} className="flex items-start gap-2.5 text-sm text-gray-400 hover:text-primary transition-colors">
                  <Mail size={15} className="mt-0.5 flex-shrink-0 text-primary" />
                  <span>{s.contact_email}</span>
                </a>
              </li>
            )}
            {s.contact_address && (
              <li className="flex items-start gap-2.5 text-sm text-gray-400">
                <MapPin size={15} className="mt-0.5 flex-shrink-0 text-primary" />
                <span>{s.contact_address}</span>
              </li>
            )}
          </ul>

          {/* WhatsApp CTA */}
          {s.whatsapp_number && (
            <a
              href={whatsappLink(s.whatsapp_number)}
              target="_blank"
              rel="noopener noreferrer"
              className="mt-5 inline-flex items-center gap-2 bg-green text-white text-sm font-semibold px-4 py-2.5 rounded hover:opacity-90 transition-opacity"
            >
              <svg viewBox="0 0 24 24" fill="currentColor" className="w-4 h-4">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
              </svg>
              Escríbenos por WhatsApp
            </a>
          )}
        </div>
      </div>

      {/* Barra inferior */}
      <div className="border-t border-gray-700">
        <div className="container mx-auto py-4 flex flex-col sm:flex-row justify-between items-center gap-2 text-xs text-gray-500">
          <p>© {new Date().getFullYear()} {s.organization_name || "Induretros"}. Todos los derechos reservados.</p>
          <div className="flex gap-4">
            <Link href="/privacidad" className="hover:text-primary transition-colors">Política de privacidad</Link>
          </div>
        </div>
      </div>
    </footer>
  );
}
