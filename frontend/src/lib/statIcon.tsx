/**
 * Mapea un nombre de ícono (string editable desde admin) a un componente de
 * lucide-react. Si el nombre no existe, usa Award por defecto.
 *
 * Set seleccionado de íconos relevantes para stats de e-commerce de
 * maquinaria pesada.
 */
import {
  Award,
  Clock,
  Package,
  Users,
  Truck,
  ShieldCheck,
  Star,
  ThumbsUp,
  TrendingUp,
  Wrench,
  Settings,
  CheckCircle2,
  Globe,
  Trophy,
  HardHat,
  Hammer,
} from "lucide-react";

const ICON_MAP: Record<string, React.ComponentType<{ size?: number; className?: string }>> = {
  Award,
  Clock,
  Package,
  Users,
  Truck,
  ShieldCheck,
  Star,
  ThumbsUp,
  TrendingUp,
  Wrench,
  Settings,
  CheckCircle2,
  Globe,
  Trophy,
  HardHat,
  Hammer,
};

/** Lista de íconos disponibles para usar en el selector del admin. */
export const AVAILABLE_ICONS = Object.keys(ICON_MAP).sort();

export function getStatIcon(name?: string | null): React.ComponentType<{ size?: number; className?: string }> {
  if (!name) return Award;
  return ICON_MAP[name] || Award;
}
