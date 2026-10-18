'use client';

import { Printer } from 'lucide-react';
import { useLanguage } from '@/hooks/useLanguage';

/** Imprime la fiche (mise en page dédiée : pièces, coûts, étapes et lieu, sans menus ni boutons). */
export function PrintButton() {
  const { t } = useLanguage();

  return (
    <button
      type="button"
      onClick={() => window.print()}
      aria-label={t('printx.print')}
      title={t('printx.print')}
      className="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700 print:hidden"
    >
      <Printer aria-hidden="true" size={18} />
      <span>{t('actions.print')}</span>
    </button>
  );
}
