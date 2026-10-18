'use client';

import { HelpCircle } from 'lucide-react';
import { QuestionsSection } from '@/components/QuestionsSection';
import { useLanguage } from '@/hooks/useLanguage';

/** Questions-réponses publiques. */
export default function QuestionsPage() {
  const { t } = useLanguage();

  return (
    <main className="mx-auto max-w-4xl px-4 py-10">
      <h1 className="flex items-center gap-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
        <HelpCircle size={32} aria-hidden="true" className="text-blue-700" /> {t('qa.title')}
      </h1>
      <p className="mt-3 text-lg text-slate-600">{t('qa.page_intro')}</p>
      <div className="mt-6">
        <QuestionsSection full />
      </div>
    </main>
  );
}
