import type { Metadata } from "next";
import Link from "next/link";

export const metadata: Metadata = {
  title: "Política de Privacidad",
  description: "Política de privacidad y tratamiento de datos personales de Induretros conforme a la Ley 1581 de 2012 e ISO/IEC 27001:2022.",
};

export default function PrivacidadPage() {
  return (
    <div className="py-12">
      <div className="container mx-auto max-w-3xl">
        <h1 className="section-title mb-2">Política de Privacidad</h1>
        <p className="text-xs text-gray-light font-sans mb-10">
          Última actualización: abril 2026 · Versión 1.0
        </p>

        <div className="prose prose-sm max-w-none font-sans text-dark-2 space-y-8">

          <section>
            <h2 className="font-heading text-lg font-semibold uppercase mb-3">1. Responsable del tratamiento</h2>
            <p>
              <strong>Induretros S.A.S.</strong>, con domicilio en el Centro Empresarial Promisión, Medellín, Colombia,
              es el responsable del tratamiento de los datos personales que usted nos suministra a través de este sitio web.
            </p>
            <p className="mt-2">
              Correo de contacto para asuntos de privacidad:{" "}
              <a href="mailto:ventas@induretros.com" className="text-primary hover:underline">
                ventas@induretros.com
              </a>
            </p>
          </section>

          <section>
            <h2 className="font-heading text-lg font-semibold uppercase mb-3">2. Datos que recopilamos</h2>
            <ul className="list-disc pl-5 space-y-1">
              <li><strong>Datos de registro:</strong> nombre, correo electrónico, teléfono (opcional).</li>
              <li><strong>Datos de pedido:</strong> nombre, correo, teléfono, dirección de envío, productos solicitados.</li>
              <li><strong>Datos de navegación:</strong> dirección IP (usada únicamente para seguridad y rate limiting).</li>
            </ul>
            <p className="mt-2">No recopilamos datos de pago directamente. No usamos cookies de rastreo.</p>
          </section>

          <section>
            <h2 className="font-heading text-lg font-semibold uppercase mb-3">3. Finalidad del tratamiento</h2>
            <ul className="list-disc pl-5 space-y-1">
              <li>Gestionar pedidos y cotizaciones de repuestos.</li>
              <li>Comunicarnos con usted sobre su solicitud.</li>
              <li>Prevenir fraude, accesos no autorizados y abusos (seguridad informática).</li>
              <li>Cumplir obligaciones legales y contables.</li>
            </ul>
          </section>

          <section>
            <h2 className="font-heading text-lg font-semibold uppercase mb-3">4. Base legal</h2>
            <p>
              El tratamiento se realiza con base en: (i) la ejecución del contrato de compraventa,
              (ii) el cumplimiento de obligaciones legales (Ley 1581 de 2012, Decreto 1377 de 2013),
              y (iii) el interés legítimo en la seguridad de nuestros sistemas.
            </p>
          </section>

          <section>
            <h2 className="font-heading text-lg font-semibold uppercase mb-3">5. Sus derechos (ARCO)</h2>
            <p>Conforme a la Ley 1581 de 2012 y al RGPD (cuando aplique), usted tiene derecho a:</p>
            <ul className="list-disc pl-5 space-y-1 mt-2">
              <li><strong>Acceso:</strong> conocer qué datos suyos tenemos.</li>
              <li><strong>Rectificación:</strong> corregir datos incorrectos.</li>
              <li><strong>Cancelación / Supresión:</strong> solicitar el borrado de sus datos (derecho al olvido). Puede eliminar su cuenta directamente desde la plataforma o enviando un correo a ventas@induretros.com.</li>
              <li><strong>Oposición:</strong> oponerse a determinados tratamientos.</li>
            </ul>
            <p className="mt-2">
              Para ejercer sus derechos escriba a{" "}
              <a href="mailto:ventas@induretros.com" className="text-primary hover:underline">
                ventas@induretros.com
              </a>{" "}
              indicando su solicitud. Responderemos en un plazo máximo de 10 días hábiles.
            </p>
          </section>

          <section>
            <h2 className="font-heading text-lg font-semibold uppercase mb-3">6. Seguridad de los datos</h2>
            <p>
              Aplicamos controles técnicos conforme a <strong>ISO/IEC 27001:2022</strong>, incluyendo:
              cifrado de contraseñas con bcrypt, comunicaciones cifradas con TLS, control de acceso
              basado en roles, registro de eventos de seguridad, y verificación CAPTCHA en formularios.
            </p>
          </section>

          <section>
            <h2 className="font-heading text-lg font-semibold uppercase mb-3">7. Retención de datos</h2>
            <p>
              Los datos de pedidos se conservan durante 5 años por obligaciones contables.
              Los datos de cuentas de usuario se eliminan inmediatamente cuando el titular solicita
              la supresión. Los registros de seguridad se conservan 12 meses.
            </p>
          </section>

          <section>
            <h2 className="font-heading text-lg font-semibold uppercase mb-3">8. Transferencias internacionales</h2>
            <p>
              No transferimos sus datos a terceros países, salvo el uso de Cloudflare Turnstile
              (Estados Unidos) para verificación CAPTCHA. Cloudflare cumple el EU-U.S. Data Privacy Framework.
            </p>
          </section>

          <section>
            <h2 className="font-heading text-lg font-semibold uppercase mb-3">9. Cambios a esta política</h2>
            <p>
              Notificaremos cambios sustanciales por correo electrónico a los usuarios registrados.
              La versión vigente siempre estará disponible en esta página.
            </p>
          </section>

          <div className="border-t border-gray-100 pt-6">
            <Link href="/contacto" className="btn-primary">
              Contactar para asuntos de privacidad
            </Link>
          </div>

        </div>
      </div>
    </div>
  );
}
