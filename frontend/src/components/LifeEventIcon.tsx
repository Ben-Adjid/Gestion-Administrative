import { Baby, Car, Contact, Flower2, GraduationCap, Heart, Home, Plane, Sparkles, Vote, type LucideIcon } from 'lucide-react';

// Icônes des parcours (le nom est choisi dans l'administration).
const ICONS: Record<string, LucideIcon> = {
  plane: Plane, 'id-card': Contact, baby: Baby, heart: Heart, flower: Flower2, vote: Vote,
  'graduation-cap': GraduationCap, car: Car, home: Home, sparkles: Sparkles,
};

export const LIFE_EVENT_ICONS = Object.keys(ICONS);

export function LifeEventIcon({ name, size = 24, className }: { name: string; size?: number; className?: string }) {
  const Icon = ICONS[name] ?? Sparkles;
  return <Icon size={size} aria-hidden="true" className={className} />;
}
