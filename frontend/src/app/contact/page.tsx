'use client';

import Link from '@/components/AppLink';
import { AlertTriangle, Mail, PlusCircle } from 'lucide-react';
import { useLanguage } from '@/hooks/useLanguage';

const CONTACT_EMAIL = process.env.NEXT_PUBLIC_CONTACT_EMAIL || '';

export default function ContactPage() {
  const { t } = useLanguage();

  return (
    <main className="bg-slate-50">
      <div className="bg-gradient-to-r from-blue-800 via-blue-900 to-slate-900 py-16 text-white">
        <div className="mx-auto max-w-4xl px-4 text-center">
          <h1 className="text-4xl font-bold tracking-tight sm:text-5xl">{t('contact.title')}</h1>
          <p className="mt-4 text-lg text-blue-100">{t('contact.intro')}</p>
        </div>
      </div>

      <div className="mx-auto grid max-w-4xl gap-6 px-4 py-12 sm:grid-cols-2">
        <Link href="/signalement" className="group rounded-2xl border border-slate-200 bg-white p-6 transition hover:border-amber-300 hover:shadow-sm">
          <AlertTriangle aria-hidden="true" className="text-amber-600" size={28} />
          <h2 className="mt-4 text-xl font-bold text-slate-950 group-hover:text-amber-700">{t('contact.wrong_title')}</h2>
          <p className="mt-2 text-slate-600">{t('contact.wrong_text')}</p>
        </Link>

        <Link href="/signalement?type=missing" className="group rounded-2xl border border-slate-200 bg-white p-6 transition hover:border-blue-300 hover:shadow-sm">
          <PlusCircle aria-hidden="true" className="text-blue-700" size={28} />
          <h2 className="mt-4 text-xl font-bold text-slate-950 group-hover:text-blue-800">{t('contact.missing_title')}</h2>
          <p className="mt-2 text-slate-600">{t('contact.missing_text')}</p>
        </Link>

        {CONTACT_EMAIL && (
          <a href={`mailto:${CONTACT_EMAIL}`} className="group rounded-2xl border border-slate-200 bg-white p-6 transition hover:border-slate-400 hover:shadow-sm sm:col-span-2">
            <Mail aria-hidden="true" className="text-slate-700" size={28} />
            <h2 className="mt-4 text-xl font-bold text-slate-950">{t('contact.other_title')}</h2>
            <p className="mt-2 text-slate-600">
              {t('contact.other_text')} <span className="font-semibold text-blue-800 group-hover:underline" dir="ltr">{CONTACT_EMAIL}</span>
            </p>
          </a>
        )}

        <p className="text-sm text-slate-500 sm:col-span-2">{t('contact.note')}</p>
      </div>
    </main>
  );
}
