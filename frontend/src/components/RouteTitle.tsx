'use client';

import { useEffect } from 'react';
import { usePathname } from 'next/navigation';
import { useLanguage } from '@/hooks/useLanguage';

// Pages dont le titre dépend de la langue choisie : le titre du navigateur suit la page affichée.
// L'ordre compte : les chemins les plus précis sont testés en premier.
const ROUTES: [string, string][] = [
  ['/auth/login', 'login'],
  ['/auth/register', 'register'],
  ['/auth/forgot-password', 'forgot'],
  ['/auth/reset-password', 'reset'],
  ['/signalement', 'signal'],
  ['/dashboard/documents', 'documents'],
  ['/dashboard/favorites', 'favorites'],
  ['/dashboard/profile', 'profile'],
  ['/dashboard/change-password', 'password'],
  ['/dashboard', 'dashboard'],
  ['/admin/login', 'admin_login'],
  ['/admin', 'admin'],
];

export function RouteTitle() {
  const pathname = usePathname();
  const { t } = useLanguage();

  useEffect(() => {
    const hit = ROUTES.find(([route]) => pathname === route || pathname.startsWith(`${route}/`));
    if (!hit) return;
    const title = `${t(`pagex.${hit[1]}`)} | DemarchesTchad`;
    // Next peut réécrire le titre juste après le chargement : on le réapplique jusqu'à stabilisation.
    const apply = () => { if (document.title !== title) document.title = title; };
    apply();
    const timers = [50, 250, 800].map((ms) => window.setTimeout(apply, ms));
    return () => timers.forEach((id) => window.clearTimeout(id));
  }, [pathname, t]);

  return null;
}
