'use client';

import { ArrowRight, MapPin, Route, Sparkles } from 'lucide-react';
import AppLink from '@/components/AppLink';
import { CitySelect } from '@/components/CitySelect';
import { LifeEventIcon } from '@/components/LifeEventIcon';
import { ListSkeleton } from '@/components/ListSkeleton';
import { usePreferences } from '@/contexts/PreferencesContext';
import { useLanguage } from '@/hooks/useLanguage';
import { useLifeEvents } from '@/hooks/useLifeEvents';

/**
 * « Mon cas et mes parcours » : une seule rubrique (anciennement « Mon cas » + « Parcours »).
 * 1. ville (facultative, mémorisée) → 2. situation → 3. parcours détaillé avec les bureaux de la ville.
 */
export default function MyCaseAndJourneysPage() {
  const { t } = useLanguage();
  const { city, setCity } = usePreferences();
  const { events, loaded } = useLifeEvents();

  const stepBadge = 'grid h-8 w-8 shrink-0 place-items-center rounded-full bg-blue-900 text-sm font-bold text-white';

  return (
    <main className="mx-auto max-w-5xl px-4 py-10">
      <p className="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-900">
        <Sparkles size={15} aria-hidden="true" /> {t('casx.nav')}
      </p>
      <h1 className="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{t('casx.title')}</h1>
      <p className="mt-3 max-w-3xl text-lg text-slate-600">{t('casx.intro')}</p>

      <section aria-labelledby="step-city" className="mt-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 id="step-city" className="flex items-center gap-3 text-lg font-bold text-slate-900">
          <span className={stepBadge} aria-hidden="true">1</span> {t('casx.step_city')}
        </h2>
        <p className="mt-1 text-sm text-slate-600">{t('casx.step_city_hint')}</p>
        <div className="mt-3">
          <CitySelect id="case-city" value={city} onChange={setCity} label={t('offices.city_label')} emptyLabel={t('offices.all_cities')} />
        </div>
      </section>

      <section aria-labelledby="step-situation" className="mt-6">
        <h2 id="step-situation" className="flex items-center gap-3 text-lg font-bold text-slate-900">
          <span className={stepBadge} aria-hidden="true">2</span> {t('casx.step_situation')}
        </h2>
        <p className="mt-1 flex items-center gap-2 text-sm text-slate-600">
          <span className={`${stepBadge} h-6 w-6 text-xs`} aria-hidden="true">3</span> {t('casx.result_hint')}
        </p>

        {!loaded && <ListSkeleton grid rows={6} className="mt-4" />}
        <ul className="mt-4 grid gap-4 sm:grid-cols-2">
          {events.map((event) => (
            <li key={event.slug}>
              <AppLink href={`/parcours/${event.slug}`} className="group flex h-full gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md">
                <span className="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-blue-100 text-blue-800"><LifeEventIcon name={event.icon} /></span>
                <span className="min-w-0 flex-1">
                  <span className="block text-lg font-bold text-slate-900 group-hover:text-blue-800">{event.title}</span>
                  {event.description && <span className="mt-1 block text-sm text-slate-600">{event.description}</span>}
                  <span className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <span className="inline-flex items-center gap-1"><Route size={13} aria-hidden="true" /> {t('casx.steps_n', { n: event.steps_count })}</span>
                    {city && <span className="inline-flex items-center gap-1 normal-case tracking-normal text-emerald-700"><MapPin size={13} aria-hidden="true" /> {t('casx.step_result')}</span>}
                  </span>
                </span>
                <ArrowRight size={18} aria-hidden="true" className="mt-1 shrink-0 text-slate-400 transition group-hover:text-blue-700 rtl:-scale-x-100" />
              </AppLink>
            </li>
          ))}
        </ul>

        <p className="mt-6 text-sm text-slate-600">
          {t('mycase.no_situation')}{' '}
          <AppLink href="/demarches" className="font-semibold text-blue-800 underline">{t('mycase.open_catalog')}</AppLink>
        </p>
      </section>
    </main>
  );
}
