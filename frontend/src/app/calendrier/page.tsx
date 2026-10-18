'use client';

import { useEffect, useState } from 'react';
import { CalendarDays, CalendarRange, FileClock } from 'lucide-react';
import { DocumentValidity } from '@/components/calendar/DocumentValidity';
import { OfficialDates } from '@/components/calendar/OfficialDates';
import { useLanguage } from '@/hooks/useLanguage';

type Tab = 'validity' | 'dates';

/**
 * Calendrier administratif, organisé autour de la validité des documents :
 *  - « Validité des documents » : durée, point de départ, expiration calculable, renouvellement, source ;
 *  - « Dates officielles » : élections, examens, inscriptions… (périodes, dates limites, dates uniques).
 * L'onglet ouvert est reflété dans l'adresse (#dates) pour pouvoir partager le lien.
 */
export default function CalendarPage() {
  const { t } = useLanguage();
  const [tab, setTab] = useState<Tab>('validity');

  useEffect(() => {
    if (window.location.hash === '#dates') setTab('dates');
  }, []);

  const choose = (next: Tab) => {
    setTab(next);
    window.history.replaceState(null, '', next === 'dates' ? '#dates' : window.location.pathname);
  };

  const tabs: Array<{ id: Tab; label: string; icon: typeof FileClock }> = [
    { id: 'validity', label: t('valx.tab_validity'), icon: FileClock },
    { id: 'dates', label: t('valx.tab_dates'), icon: CalendarRange },
  ];

  return (
    <main className="mx-auto max-w-4xl px-4 py-10">
      <h1 className="flex items-center gap-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">
        <CalendarDays size={32} aria-hidden="true" className="text-blue-700" /> {t('calendar.title')}
      </h1>
      <p className="mt-3 text-lg text-slate-600">{tab === 'validity' ? t('valx.intro') : t('calendar.intro')}</p>

      <div role="tablist" aria-label={t('calendar.title')} className="mt-6 inline-flex w-full rounded-2xl bg-slate-100 p-1 sm:w-auto">
        {tabs.map(({ id, label, icon: Icon }) => (
          <button
            key={id}
            id={`tab-${id}`}
            type="button"
            role="tab"
            aria-selected={tab === id}
            aria-controls={`panel-${id}`}
            onClick={() => choose(id)}
            className={`inline-flex flex-1 items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition sm:flex-none ${tab === id ? 'bg-white text-blue-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'}`}
          >
            <Icon size={17} aria-hidden="true" /> {label}
          </button>
        ))}
      </div>

      <section id={`panel-${tab}`} role="tabpanel" aria-labelledby={`tab-${tab}`} className="mt-2">
        {tab === 'validity' ? <DocumentValidity /> : <div className="mt-4"><OfficialDates /></div>}
      </section>
    </main>
  );
}
