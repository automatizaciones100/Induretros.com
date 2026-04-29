"use client";

import { useState, useRef } from "react";
import { Phone, Mail, MapPin, Clock } from "lucide-react";
import TurnstileWidget from "@/components/ui/TurnstileWidget";

export default function ContactoPage() {
  const [turnstileToken, setTurnstileToken] = useState<string | null>(null);
  const [status, setStatus] = useState<"idle" | "sending" | "sent" | "error">("idle");
  const formRef = useRef<HTMLFormElement>(null);

  const handleSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!turnstileToken) {
      alert("Por favor espera a que se complete la verificación de seguridad.");
      return;
    }
    setStatus("sending");
    // Aquí iría la llamada al API de contacto cuando esté implementada.
    // Por ahora simulamos éxito tras 800 ms.
    await new Promise((r) => setTimeout(r, 800));
    setStatus("sent");
    formRef.current?.reset();
    setTurnstileToken(null);
  };

  return (
    <div className="py-12">
      <div className="container mx-auto">
        <div className="text-center mb-12">
          <h1 className="section-title">Contáctanos</h1>
          <p className="section-subtitle">Estamos listos para ayudarte a encontrar el repuesto que necesitas</p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-12">
          {/* Información de contacto */}
          <div>
            <h2 className="font-heading text-xl font-semibold text-dark-2 uppercase mb-6">Información de contacto</h2>

            <div className="space-y-5 mb-8">
              {[
                { icon: <Phone size={20} />, label: "Teléfono", value: "300 719 2973", href: "tel:+573007192973" },
                { icon: <Mail size={20} />, label: "Correo", value: "ventas@induretros.com", href: "mailto:ventas@induretros.com" },
                { icon: <MapPin size={20} />, label: "Dirección", value: "Centro Empresarial Promisión, Medellín, Colombia", href: null },
                { icon: <Clock size={20} />, label: "Horario", value: "Lunes a Viernes: 7:00 am - 5:00 pm", href: null },
              ].map((item) => (
                <div key={item.label} className="flex items-start gap-4">
                  <div className="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center text-primary flex-shrink-0">
                    {item.icon}
                  </div>
                  <div>
                    <p className="text-xs text-gray-light font-sans uppercase tracking-wide">{item.label}</p>
                    {item.href ? (
                      <a href={item.href} className="font-sans text-dark-2 font-medium hover:text-primary transition-colors">
                        {item.value}
                      </a>
                    ) : (
                      <p className="font-sans text-dark-2 font-medium">{item.value}</p>
                    )}
                  </div>
                </div>
              ))}
            </div>

            <a
              href="https://wa.me/573007192973"
              target="_blank"
              rel="noopener noreferrer"
              className="btn-primary w-full justify-center py-4 text-base"
            >
              <svg viewBox="0 0 24 24" fill="currentColor" className="w-5 h-5">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
              </svg>
              Escribir por WhatsApp
            </a>
          </div>

          {/* Formulario */}
          <div className="bg-white rounded-xl border border-gray-100 p-8 shadow-sm">
            <h2 className="font-heading text-xl font-semibold text-dark-2 uppercase mb-6">Envíanos un mensaje</h2>

            {status === "sent" ? (
              <div className="flex flex-col items-center justify-center py-12 text-center gap-4">
                <div className="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center">
                  <svg className="w-7 h-7 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <p className="font-sans text-dark-2 font-medium">¡Mensaje enviado!</p>
                <p className="text-sm text-gray-mid">Te responderemos a la brevedad.</p>
                <button onClick={() => setStatus("idle")} className="btn-secondary mt-2">
                  Enviar otro mensaje
                </button>
              </div>
            ) : (
              <form ref={formRef} onSubmit={handleSubmit} className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                      Nombre
                    </label>
                    <input type="text" name="nombre" placeholder="Tu nombre" className="input-field" required />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                      Teléfono
                    </label>
                    <input type="tel" name="telefono" placeholder="Tu teléfono" className="input-field" />
                  </div>
                </div>
                <div>
                  <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                    Correo electrónico
                  </label>
                  <input type="email" name="email" placeholder="tu@correo.com" className="input-field" required />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                    Asunto
                  </label>
                  <input type="text" name="asunto" placeholder="¿En qué podemos ayudarte?" className="input-field" />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-gray-mid uppercase tracking-wide mb-1.5 font-sans">
                    Mensaje
                  </label>
                  <textarea
                    name="mensaje"
                    rows={4}
                    placeholder="Describe el repuesto que necesitas, marca del equipo, modelo, etc."
                    className="input-field resize-none"
                    required
                  />
                </div>

                {/* Cloudflare Turnstile — verifica que es un humano antes de enviar */}
                <TurnstileWidget
                  onVerify={(token) => setTurnstileToken(token)}
                  onExpire={() => setTurnstileToken(null)}
                  onError={() => setTurnstileToken(null)}
                />

                <button
                  type="submit"
                  disabled={status === "sending"}
                  className="btn-primary w-full justify-center py-3.5 disabled:opacity-60 disabled:cursor-not-allowed"
                >
                  {status === "sending" ? "Enviando…" : "Enviar mensaje"}
                </button>
              </form>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
