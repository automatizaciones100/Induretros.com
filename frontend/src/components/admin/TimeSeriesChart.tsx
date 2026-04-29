"use client";

import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  Tooltip,
  ResponsiveContainer,
  CartesianGrid,
  Legend,
} from "recharts";

interface Point {
  date: string;
  visitors: number;
  pageviews: number;
  orders: number;
  revenue: number;
}

interface Props {
  data: Point[];
  /** Qué métricas mostrar como líneas separadas. */
  metrics: { key: keyof Omit<Point, "date">; label: string; color: string }[];
}

const formatDate = (raw: string) => {
  const d = new Date(raw + "T00:00:00");
  return d.toLocaleDateString("es-CO", { day: "2-digit", month: "short" });
};

export default function TimeSeriesChart({ data, metrics }: Props) {
  return (
    <ResponsiveContainer width="100%" height={260}>
      <LineChart data={data} margin={{ top: 5, right: 10, left: -10, bottom: 0 }}>
        <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" vertical={false} />
        <XAxis
          dataKey="date"
          tickFormatter={formatDate}
          tick={{ fontSize: 11, fill: "#888" }}
          tickLine={false}
          axisLine={{ stroke: "#e5e5e5" }}
          minTickGap={30}
        />
        <YAxis
          tick={{ fontSize: 11, fill: "#888" }}
          tickLine={false}
          axisLine={{ stroke: "#e5e5e5" }}
          allowDecimals={false}
        />
        <Tooltip
          contentStyle={{
            backgroundColor: "#fff",
            border: "1px solid #e5e5e5",
            borderRadius: "8px",
            fontSize: "12px",
            fontFamily: "system-ui",
          }}
          labelFormatter={(label: string) => formatDate(label)}
        />
        <Legend
          wrapperStyle={{ fontSize: "12px", paddingTop: "10px" }}
          iconType="circle"
        />
        {metrics.map((m) => (
          <Line
            key={m.key}
            type="monotone"
            dataKey={m.key}
            name={m.label}
            stroke={m.color}
            strokeWidth={2}
            dot={{ r: 3 }}
            activeDot={{ r: 5 }}
          />
        ))}
      </LineChart>
    </ResponsiveContainer>
  );
}
