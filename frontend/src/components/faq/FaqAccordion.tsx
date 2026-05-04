"use client";

import { useState } from "react";
import { ChevronDown } from "lucide-react";
import type { FaqItem } from "@/lib/faq";

interface Props {
  items: FaqItem[];
}

export default function FaqAccordion({ items }: Props) {
  // Set de IDs abiertos. Permite varios abiertos a la vez.
  const [open, setOpen] = useState<Set<number>>(new Set());

  const toggle = (id: number) => {
    setOpen((s) => {
      const next = new Set(s);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  };

  return (
    <div className="space-y-3">
      {items.map((item) => {
        const isOpen = open.has(item.id);
        return (
          <article
            key={item.id}
            className={`bg-white border rounded-xl transition-colors ${
              isOpen ? "border-primary shadow-sm" : "border-gray-200"
            }`}
          >
            <button
              type="button"
              onClick={() => toggle(item.id)}
              aria-expanded={isOpen}
              className="w-full flex items-center justify-between gap-4 px-5 py-4 text-left hover:bg-bg-light/40 rounded-xl transition-colors"
            >
              <span className="font-heading font-semibold text-dark-2 text-base leading-snug">
                {item.question}
              </span>
              <ChevronDown
                size={20}
                className={`text-primary flex-shrink-0 transition-transform duration-200 ${
                  isOpen ? "rotate-180" : ""
                }`}
              />
            </button>
            {isOpen && (
              <div className="px-5 pb-5 -mt-1">
                <div
                  className="font-sans text-sm text-gray-mid leading-relaxed prose prose-sm max-w-none"
                  // El backend ya pasó answer por bleach con tags whitelisted (p, br, strong, em, ul, ol, li, a)
                  dangerouslySetInnerHTML={{ __html: item.answer.replace(/\n/g, "<br/>") }}
                />
              </div>
            )}
          </article>
        );
      })}
    </div>
  );
}
