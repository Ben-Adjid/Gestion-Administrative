'use client';

import { Calculator } from 'lucide-react';
import { formatFcfa } from '@/lib/format';
import { useLanguage } from '@/hooks/useLanguage';
import type { CostEstimate as Estimate, ExtraCost } from '@/types';

/** Coût total à prévoir : prix officiel + frais annexes. Affiché seulement s'il y a des frais annexes. */
export function CostEstimate({ cost, estimate, extras }: { cost: string; estimate?: Estimate; extras?: ExtraCost[] }) {
  const { language, t } = useLanguage();
  if (!estimate || !extras || extras.length === 0) return null;

  return (
    <section className="break-inside-avoid rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 sm:p-6 print:border-slate-300 print:bg-transparent print:p-3">
      <h2 className="flex items-center gap-2 text-xl font-bold text-slate-950">
        <Calculator size={20} aria-hidden="true" className="text-emerald-700" /> {t('estimate.title')}
      </h2>
      <dl className="mt-4 space-y-2 text-slate-700">
        <div className="flex justify-between gap-4">
          <dt>{t('estimate.official')}</dt>
          <dd className="font-semibold">{estimate.complete && estimate.official !== null ? formatFcfa(language, estimate.official) : `${cost} (${t('estimate.variable')})`}</dd>
        </div>
        {extras.map((extra, index) => (
          <div key={`${index}-${extra.label}`} className="flex justify-between gap-4 text-sm">
            <dt className="ps-3">+ {extra.label}</dt>
            <dd>{formatFcfa(language, extra.amount)}</dd>
          </div>
        ))}
        <div className="flex justify-between gap-4 border-t border-emerald-200 pt-3 text-lg font-bold text-slate-950">
          <dt>{t('estimate.total')}</dt>
          <dd>{formatFcfa(language, estimate.total)}{!estimate.complete && ' +'}</dd>
        </div>
      </dl>
      {!estimate.complete && <p className="mt-2 text-sm text-amber-800">{t('estimate.incomplete')}</p>}
      <p className="mt-3 text-xs text-slate-500">{t('estimate.disclaimer')}</p>
    </section>
  );
}
