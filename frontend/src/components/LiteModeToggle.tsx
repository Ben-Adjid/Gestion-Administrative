'use client';

import { Gauge } from 'lucide-react';
import { usePreferences } from '@/contexts/PreferencesContext';
import { useLanguage } from '@/hooks/useLanguage';

/** Interrupteur du mode économie de données. */
export function LiteModeToggle({ inverted = false }: { inverted?: boolean }) {
  const { liteMode, liteModeAuto, setLiteMode } = usePreferences();
  const { t } = useLanguage();

  return (
    <div>
      <label className={`inline-flex cursor-pointer items-center gap-2 text-sm font-medium ${inverted ? 'text-slate-200' : 'text-slate-700'}`}>
        <input type="checkbox" role="switch" checked={liteMode} onChange={(e) => setLiteMode(e.target.checked)} className="h-4 w-4 accent-amber-500" />
        <Gauge size={16} aria-hidden="true" className={inverted ? 'text-amber-400' : 'text-amber-600'} />
        {t('lite.label')}
      </label>
      <p className={`mt-1 text-xs ${inverted ? 'text-slate-400' : 'text-slate-500'}`}>{t(liteModeAuto ? 'lite.auto' : 'lite.hint')}</p>
    </div>
  );
}
