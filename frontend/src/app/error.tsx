'use client';

import { useEffect } from 'react';
import { useLanguage } from '@/hooks/useLanguage';

export default function Error({ error, reset }: { error: Error & { digest?: string }; reset: () => void }) {
  const { t } = useLanguage();

  useEffect(() => {
    console.error(error);
  }, [error]);

  return (
    <main className="grid min-h-[60vh] place-items-center bg-slate-50 px-5 py-16 text-center">
      <div>
        <h1 className="text-3xl font-bold text-slate-950">{t('errors.title')}</h1>
        <p className="mt-3 text-slate-600">{t('errors.text')}</p>
        {error.digest && <p className="mt-2 text-xs text-slate-500">{t('errors.reference', { ref: error.digest })}</p>}
        <button type="button" onClick={reset} className="mt-6 rounded-xl bg-blue-800 px-5 py-3 font-semibold text-white transition hover:bg-blue-900">
          {t('common.retry')}
        </button>
      </div>
    </main>
  );
}
