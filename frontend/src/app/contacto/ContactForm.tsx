"use client";

import { useState, useRef } from "react";
import TurnstileWidget from "@/components/ui/TurnstileWidget";

export default function ContactForm() {
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
    // TODO fase 2: POST /api/contact con el formulario
    await new Promise((r) => setTimeout(r, 800));
    setStatus("sent");
    formRef.current?.reset();
    setTurnstileToken(null);
  };

  if (status === "sent") {
    return (
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
    );
  }

  return (
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
  );
}
