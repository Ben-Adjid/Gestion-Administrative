'use client';

import Link from '@/components/AppLink';
import { ChevronDown } from 'lucide-react';
import { useLanguage } from '@/hooks/useLanguage';

const QUESTIONS = [1, 2, 3, 4, 5, 6, 7, 8];

export default function FAQPage() {
  const { t } = useLanguage();

  return (
    <main className="min-h-screen bg-gradient-to-b from-blue-50 to-white">
      <div className="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
        <div className="mb-12 text-center">
          <h1 className="mb-4 text-4xl font-bold text-slate-950 sm:text-5xl">{t('faq.title')}</h1>
          <p className="text-lg text-slate-600">{t('faq.subtitle')}</p>
        </div>

        <div className="space-y-4">
          {QUESTIONS.map((n) => (
            <details key={n} className="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
              <summary className="flex cursor-pointer list-none items-center justify-between gap-4 p-6 transition hover:bg-slate-50">
                <h2 className="text-lg font-bold text-slate-950">{t(`faq.q${n}`)}</h2>
                <ChevronDown aria-hidden="true" className="h-5 w-5 shrink-0 text-slate-600 transition group-open:rotate-180" />
              </summary>
              <p className="border-t border-slate-100 px-6 pb-6 pt-4 leading-relaxed text-slate-700">{t(`faq.a${n}`)}</p>
            </details>
          ))}
        </div>

        <div className="mt-14 rounded-2xl border border-blue-200 bg-blue-50 p-8 text-center">
          <h2 className="mb-3 text-2xl font-bold text-slate-950">{t('faq.not_found')}</h2>
          <p className="mb-6 text-slate-700">{t('faq.contact_text')}</p>
          <Link href="/contact" className="inline-block rounded-xl bg-blue-700 px-6 py-3 font-bold text-white transition hover:bg-blue-800">
            {t('faq.contact_button')}
          </Link>
        </div>
      </div>
    </main>
  );
}
