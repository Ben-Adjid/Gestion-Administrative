'use client';

import { ArrowLeft } from 'lucide-react';
import AppLink from '@/components/AppLink';
import { JourneyView, type JourneyData } from '@/components/JourneyView';
import { useLanguage } from '@/hooks/useLanguage';

export default function JourneyPageView({ initial }: { initial: JourneyData }) {
  const { t } = useLanguage();

  return (
    <main className="mx-auto max-w-4xl px-4 py-10">
      <AppLink href="/parcours" className="mb-6 inline-flex items-center gap-2 font-semibold text-blue-800 hover:underline">
        <ArrowLeft size={18} aria-hidden="true" className="rtl:-scale-x-100" /> {t('casx.back')}
      </AppLink>
      <JourneyView initial={initial} />
    </main>
  );
}
