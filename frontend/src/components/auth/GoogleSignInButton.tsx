"use client";

import { useEffect, useRef, useState } from "react";
import Script from "next/script";
import { loginWithGoogle } from "@/lib/api/auth";

const GOOGLE_CLIENT_ID = process.env.NEXT_PUBLIC_GOOGLE_CLIENT_ID || "";

declare global {
  interface Window {
    google?: {
      accounts: {
        id: {
          initialize: (config: {
            client_id: string;
            callback: (response: { credential: string }) => void;
            ux_mode?: "popup" | "redirect";
            auto_select?: boolean;
            cancel_on_tap_outside?: boolean;
          }) => void;
          renderButton: (
            parent: HTMLElement,
            options: {
              type?: "standard" | "icon";
              theme?: "outline" | "filled_blue" | "filled_black";
              size?: "large" | "medium" | "small";
              text?: "signin_with" | "signup_with" | "continue_with" | "signin";
              shape?: "rectangular" | "pill" | "circle" | "square";
              logo_alignment?: "left" | "center";
              width?: number | string;
              locale?: string;
            },
          ) => void;
          prompt: () => void;
          cancel: () => void;
        };
      };
    };
  }
}

interface Props {
  /** Texto del botón. Para login → "signin_with"; para registro → "signup_with". */
  text?: "signin_with" | "signup_with" | "continue_with";
  /** Llamado cuando el backend confirma el login con nuestro JWT. */
  onSuccess: (accessToken: string) => void;
  /** Llamado si algo falla (token rechazado, red, etc.). */
  onError?: (message: string) => void;
}

/**
 * Botón "Continuar con Google" usando Google Identity Services.
 * Se oculta automáticamente si NEXT_PUBLIC_GOOGLE_CLIENT_ID no está configurado.
 *
 * Flujo:
 *   1. GSI obtiene un ID token firmado por Google
 *   2. Se envía a POST /api/auth/google
 *   3. El backend lo verifica y emite NUESTRO JWT
 *   4. Llamamos onSuccess con ese JWT
 */
export default function GoogleSignInButton({
  text = "continue_with",
  onSuccess,
  onError,
}: Props) {
  const ref = useRef<HTMLDivElement>(null);
  const [scriptReady, setScriptReady] = useState(false);
  const [exchanging, setExchanging] = useState(false);

  // No hay client ID configurado → no renderiza nada
  if (!GOOGLE_CLIENT_ID) {
    return null;
  }

  useEffect(() => {
    if (!scriptReady || !ref.current || !window.google) return;

    window.google.accounts.id.initialize({
      client_id: GOOGLE_CLIENT_ID,
      callback: async ({ credential }) => {
        if (!credential) {
          onError?.("Google no devolvió credencial");
          return;
        }
        setExchanging(true);
        try {
          const data = await loginWithGoogle(credential);
          onSuccess(data.access_token);
        } catch (err) {
          onError?.(err instanceof Error ? err.message : "Error al iniciar sesión con Google");
        } finally {
          setExchanging(false);
        }
      },
      ux_mode: "popup",
      auto_select: false,
      cancel_on_tap_outside: true,
    });

    window.google.accounts.id.renderButton(ref.current, {
      type: "standard",
      theme: "outline",
      size: "large",
      text,
      shape: "rectangular",
      logo_alignment: "left",
      width: 320,
      locale: "es",
    });
  }, [scriptReady, text, onSuccess, onError]);

  return (
    <>
      <Script
        src="https://accounts.google.com/gsi/client"
        strategy="afterInteractive"
        onReady={() => setScriptReady(true)}
      />
      <div className="flex flex-col items-center gap-2">
        <div ref={ref} className="min-h-[44px] flex items-center justify-center">
          {!scriptReady && (
            <div className="text-xs text-gray-light font-sans">Cargando Google…</div>
          )}
        </div>
        {exchanging && (
          <p className="text-xs text-gray-mid font-sans">Iniciando sesión…</p>
        )}
      </div>
    </>
  );
}
