'use client';

import { MapPin } from 'lucide-react';
import AppLink from '@/components/AppLink';
import { useCitiesState } from '@/components/CitySelect';
import { ListSkeleton } from '@/components/ListSkeleton';
import { useLanguage } from '@/hooks/useLanguage';

/** Les villes du Tchad et le nombre de bureaux enregistrés. */
export default function CitiesPage() {
  const { t } = useLanguage();
  const { cities, status, retry } = useCitiesState();

  return (
    <main className="mx-auto max-w-5xl px-4 py-10">
      <h1 className="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{t('cities.title')}</h1>
      <p className="mt-3 text-lg text-slate-600">{t('cities.intro')}</p>
      {status === 'loading' && <ListSkeleton grid rows={9} />}
      {status === 'error' && (
        <div role="alert" className="mt-8 rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800">
          <p>{t('common.error_generic')}</p>
          <button type="button" onClick={retry} className="mt-3 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">{t('common.retry')}</button>
        </div>
      )}
      {status === 'ready' && cities.length === 0 && <p className="mt-8 rounded-2xl border border-dashed border-slate-300 p-6 text-slate-600">{t('cityx.empty')}</p>}
      <ul className="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        {cities.map((city) => (
          <li key={city.slug}>
            <AppLink href={`/villes/${city.slug}`} className="flex h-full items-start gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-300 hover:shadow-md">
              <MapPin size={20} aria-hidden="true" className="mt-0.5 shrink-0 text-blue-700" />
              <span>
                <span className="block font-bold text-slate-900">{city.name}</span>
                {city.province && <span className="block text-sm text-slate-500">{city.province}</span>}
                <span className={`mt-1 block text-xs font-semibold ${city.offices_count ? 'text-emerald-700' : 'text-slate-400'}`}>
                  {city.offices_count ? t('cities.offices_count', { n: city.offices_count }) : t('cities.no_offices')}
                </span>
              </span>
            </AppLink>
          </li>
        ))}
      </ul>
    </main>
  );
}
