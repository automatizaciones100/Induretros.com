"use client";

import { TrendingUp, TrendingDown, Minus } from "lucide-react";

interface Props {
  label: string;
  current: number;
  previous: number;
  delta: number;
  format?: "number" | "currency";
  icon?: React.ComponentType<{ size?: number; className?: string }>;
}

/**
 * Card que muestra el valor actual + comparación con período anterior + delta % coloreado.
 */
export default function ComparisonCard({
  label,
  current,
  previous,
  delta,
  format = "number",
  icon: Icon,
}: Props) {
  const formatValue = (v: number) => {
    if (format === "currency") return `$${v.toLocaleString("es-CO")}`;
    return v.toLocaleString("es-CO");
  };

  // delta=0 con previous=0 = estable; con previous>0 = sin cambio
  const noChange = previous === 0 && current === 0;
  const isPositive = delta > 0;
  const isNegative = delta < 0;

  const deltaColor = noChange
    ? "text-gray-mid bg-gray-100"
    : isPositive
      ? "text-green-700 bg-green-100"
      : isNegative
        ? "text-red-700 bg-red-100"
        : "text-gray-mid bg-gray-100";

  const TrendIcon = noChange ? Minus : isPositive ? TrendingUp : isNegative ? TrendingDown : Minus;

  return (
    <div className="bg-white border border-gray-100 rounded-xl p-5 hover:shadow-sm transition-shadow">
      <div className="flex items-start justify-between mb-3">
        {Icon && (
          <div className="w-9 h-9 rounded-lg bg-bg-light flex items-center justify-center text-gray-mid">
            <Icon size={18} />
          </div>
        )}
        {!noChange && (
          <span className={`inline-flex items-center gap-1 text-xs px-2 py-1 rounded font-semibold ${deltaColor}`}>
            <TrendIcon size={11} />
            {isPositive ? "+" : ""}
            {delta}%
          </span>
        )}
      </div>
      <p className="text-xs text-gray-mid font-sans uppercase tracking-wide mb-1">{label}</p>
      <p className="font-heading text-2xl font-semibold text-dark-2">
        {formatValue(current)}
      </p>
      <p className="text-xs text-gray-light font-sans mt-1">
        Antes: <span className="text-gray-mid">{formatValue(previous)}</span>
      </p>
    </div>
  );
}
