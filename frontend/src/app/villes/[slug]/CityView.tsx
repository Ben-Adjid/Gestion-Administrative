'use client';

import { useEffect, useState } from 'react';
import { ArrowLeft } from 'lucide-react';
import AppLink from '@/components/AppLink';
import { OfficeCard } from '@/components/fiche/OfficesSection';
import { usePreferences } from '@/contexts/PreferencesContext';
import { API_V1_URL } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';
import type { Office } from '@/types';

export interface CityData {
  id: number;
  slug: string;
  name: string;
  province: string | null;
  offices: Array<Office & { demarches: Array<{ slug: string; title: string }> }>;
}

/** Une ville : ses bureaux et les démarches qu'on peut y faire. */
export default function CityView({ initial }: { initial: CityData }) {
  const { language, t } = useLanguage();
  const { city: preferred, setCity } = usePreferences();
  const [city, setCityData] = useState<CityData>(initial);

  useEffect(() => {
    if (language === 'fr') {
      setCityData(initial);
      return;
    }
    let active = true;
    fetch(`${API_V1_URL}/cities/${encodeURIComponent(initial.slug)}?lang=${language}`, { headers: { Accept: 'application/json' } })
      .then((res) => (res.ok ? res.json() : null))
      .then((json: { data: CityData } | null) => active && json && setCityData(json.data))
      .catch(() => { /* version française conservée */ });
    return () => { active = false; };
  }, [initial, language]);

  const demarches = Array.from(new Map(city.offices.flatMap((office) => office.demarches).map((d) => [d.slug, d])).values());

  return (
    <main className="mx-auto max-w-5xl px-4 py-10">
      <AppLink href="/villes" className="mb-6 inline-flex items-center gap-2 font-semibold text-blue-800 hover:underline">
        <ArrowLeft size={18} aria-hidden="true" className="rtl:-scale-x-100" /> {t('cities.all_cities')}
      </AppLink>
      <h1 className="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{t('cities.city_title', { city: city.name })}</h1>
      {city.province && <p className="mt-2 text-slate-600">{t('cities.province', { name: city.province })}</p>}
      {preferred !== city.slug && (
        <button type="button" onClick={() => setCity(city.slug)} className="mt-4 rounded-lg border border-blue-300 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-900 hover:bg-blue-100">
          {t('navx.label_value', { label: t('offices.city_label'), value: city.name })}
        </button>
      )}

      {city.offices.length === 0 ? (
        <p className="mt-8 rounded-2xl border border-dashed border-slate-300 p-6 text-slate-600">{t('cities.empty')}</p>
      ) : (
        <>
          {demarches.length > 0 && (
            <section className="mt-8">
              <h2 className="text-xl font-bold text-slate-950">{t('cities.demarches')}</h2>
              <ul className="mt-3 flex flex-wrap gap-2">
                {demarches.map((demarche) => (
                  <li key={demarche.slug}>
                    <AppLink href={`/demarches/${demarche.slug}`} className="inline-block rounded-full border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:border-blue-400 hover:text-blue-800">
                      {demarche.title}
                    </AppLink>
                  </li>
                ))}
              </ul>
            </section>
          )}
          <section className="mt-8">
            <h2 className="text-xl font-bold text-slate-950">{t('offices.title')}</h2>
            <ul className="mt-4 grid gap-3 sm:grid-cols-2">{city.offices.map((office) => <OfficeCard key={office.id} office={office} />)}</ul>
          </section>
        </>
      )}
    </main>
  );
}
