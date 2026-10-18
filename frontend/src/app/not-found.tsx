'use client';

import Link from '@/components/AppLink';
import { useLanguage } from '@/hooks/useLanguage';

export default function NotFound() {
  const { t } = useLanguage();

  return (
    <main className="grid min-h-[60vh] place-items-center bg-slate-50 px-5 py-16 text-center">
      <div>
        <p className="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">{t('errors.not_found_kicker')}</p>
        <h1 className="mt-3 text-3xl font-bold text-slate-950">{t('errors.not_found_title')}</h1>
        <p className="mt-3 text-slate-600">{t('errors.not_found_text')}</p>
        <div className="mt-6 flex flex-wrap justify-center gap-3">
          <Link href="/" className="rounded-xl bg-blue-800 px-5 py-3 font-semibold text-white transition hover:bg-blue-900">{t('common.back_home')}</Link>
          <Link href="/demarches" className="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700 transition hover:bg-white">{t('nav.catalog_full')}</Link>
        </div>
      </div>
    </main>
  );
}
