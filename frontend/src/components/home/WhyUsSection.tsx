import type { WhyUsItem } from "@/lib/whyUs";
import { getStatIcon } from "@/lib/statIcon";

interface Props {
  items: WhyUsItem[];
}

export default function WhyUsSection({ items }: Props) {
  if (items.length === 0) return null;

  return (
    <section className="py-14 bg-bg-light">
      <div className="container mx-auto">
        <div className="text-center mb-10">
          <h2 className="section-title">¿Por qué elegirnos?</h2>
          <p className="section-subtitle">
            Lo que nos hace diferentes en el mercado de repuestos para maquinaria pesada
          </p>
        </div>

        <div
          className="grid gap-6"
          style={{ gridTemplateColumns: `repeat(auto-fit, minmax(220px, 1fr))` }}
        >
          {items.map((it) => {
            const Icon = getStatIcon(it.icon);
            return (
              <article
                key={it.id}
                className="bg-white rounded-xl p-6 text-center hover:shadow-md transition-shadow border border-gray-100"
              >
                <div className="w-14 h-14 rounded-full bg-primary/10 mx-auto flex items-center justify-center mb-4 text-primary">
                  <Icon size={26} />
                </div>
                <h3 className="font-heading text-base font-semibold text-dark-2 uppercase mb-2">
                  {it.title}
                </h3>
                <p className="font-sans text-sm text-gray-mid leading-relaxed">
                  {it.description}
                </p>
              </article>
            );
          })}
        </div>
      </div>
    </section>
  );
}
