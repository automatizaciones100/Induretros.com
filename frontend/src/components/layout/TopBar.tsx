import { Phone, Mail, MapPin, Facebook, Instagram, Youtube } from "lucide-react";

export default function TopBar() {
  return (
    <div className="bg-dark-2 text-gray-light text-sm py-2">
      <div className="container mx-auto flex flex-col sm:flex-row justify-between items-center gap-2">
        {/* Contacto */}
        <div className="flex flex-wrap items-center gap-4">
          <a href="tel:+576045602662" className="top-bar-link flex items-center gap-1.5">
            <Phone size={13} />
            <span>(604) 560-2662</span>
          </a>
          <a href="mailto:ventas@induretros.com" className="top-bar-link flex items-center gap-1.5">
            <Mail size={13} />
            <span>ventas@induretros.com</span>
          </a>
          <span className="top-bar-link hidden md:flex items-center gap-1.5">
            <MapPin size={13} />
            <span>Centro Empresarial Promisión, Medellín</span>
          </span>
        </div>

        {/* Redes sociales */}
        <div className="flex items-center gap-3">
          <a
            href="https://www.facebook.com/induretros"
            target="_blank"
            rel="noopener noreferrer"
            className="top-bar-link hover:text-primary"
            aria-label="Facebook"
          >
            <Facebook size={15} />
          </a>
          <a
            href="https://www.instagram.com/induretros"
            target="_blank"
            rel="noopener noreferrer"
            className="top-bar-link hover:text-primary"
            aria-label="Instagram"
          >
            <Instagram size={15} />
          </a>
          <a
            href="https://www.youtube.com/@induretros"
            target="_blank"
            rel="noopener noreferrer"
            className="top-bar-link hover:text-primary"
            aria-label="YouTube"
          >
            <Youtube size={15} />
          </a>
          <a
            href="/mi-cuenta"
            className="top-bar-link hover:text-primary ml-2 border-l border-gray-600 pl-3"
          >
            Mi cuenta
          </a>
        </div>
      </div>
    </div>
  );
}
