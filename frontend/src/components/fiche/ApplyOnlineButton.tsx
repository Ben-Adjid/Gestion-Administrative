'use client';

import { useState } from 'react';
import { Send, Sparkles } from 'lucide-react';
import { useLanguage } from '@/hooks/useLanguage';

/**
 * Bouton « Postuler en ligne » : la soumission en ligne n'est pas encore ouverte.
 * Le bouton explique l'état du service ; il n'envoie aucun dossier. Il servira de point d'entrée
 * à un partenariat avec l'administration le jour où la soumission sera disponible.
 */
export function ApplyOnlineButton() {
  const { t } = useLanguage();
  const [open, setOpen] = useState(false);

  return (
    <div className="flex flex-col items-start gap-2 print:hidden">
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        aria-expanded={open}
        className="group relative inline-flex items-center gap-2 overflow-hidden rounded-full bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-orange-500/30 transition duration-300 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-orange-500/40 focus:outline-none focus-visible:ring-4 focus-visible:ring-orange-300"
      >
        <span aria-hidden="true" className="pointer-events-none absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/30 to-transparent transition-transform duration-700 group-hover:translate-x-full" />
        <Send aria-hidden="true" size={16} className="transition group-hover:translate-x-0.5" />
        {t('applyx.button')}
        <span className="ms-1 rounded-full bg-white/25 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide">{t('applyx.badge')}</span>
      </button>

      {open && (
        <div role="status" className="max-w-md transition-opacity duration-300 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 shadow-sm">
          <p className="flex items-center gap-2 font-bold">
            <Sparkles aria-hidden="true" size={16} className="text-amber-600" />
            {t('applyx.soon_title')}
          </p>
          <p className="mt-1">{t('applyx.soon_text')}</p>
        </div>
      )}
    </div>
  );
}
