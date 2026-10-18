'use client';

import { useEffect, useState } from 'react';
import { ArrowRight, Clock3, FileText, Wallet } from 'lucide-react';
import AppLink from '@/components/AppLink';
import { CitySelect, useCities } from '@/components/CitySelect';
import { OfficeCard } from '@/components/fiche/OfficesSection';
import { usePreferences } from '@/contexts/PreferencesContext';
import { API_V1_URL } from '@/lib/api';
import { formatFcfa } from '@/lib/format';
import { useLanguage } from '@/hooks/useLanguage';
import type { CostEstimate, Office } from '@/types';

export interface JourneyStep {
  position: number;
  slug: string;
  title: string;
  summary: string | null;
  cost: string;
  processing_time: string | null;
  required_documents: string[];
  location: string | null;
  cost_estimate: CostEstimate;
  offices: Office[];
}

export interface JourneyData {
  slug: string;
  title: string;
  description: string | null;
  icon: string;
  steps: JourneyStep[];
  total: { amount: number; complete: boolean };
}

/** Récupère un parcours dans la langue et pour la ville demandées. */
export async function fetchJourney(slug: string, language: string, city: string | null): Promise<JourneyData | null> {
  const params = new URLSearchParams({ lang: language });
  if (city) params.set('city', city);
  const res = await fetch(`${API_V1_URL}/life-events/${encodeURIComponent(slug)}?${params}`, { headers: { Accept: 'application/json' } });
  if (!res.ok) return null;
  return ((await res.json()) as { data: JourneyData }).data;
}

/** Parcours de vie : étapes dans l'ordre, budget total, pièces et bureaux de la ville choisie. */
export function JourneyView({ initial, showTitle = true }: { initial: JourneyData; showTitle?: boolean }) {
  const { language, t } = useLanguage();
  const { city, setCity } = usePreferences();
  const cities = useCities();
  const [journey, setJourney] = useState<JourneyData>(initial);

  useEffect(() => {
    let active = true;
    // La version serveur (français, sans ville) suffit tant que rien n'a changé.
    if (language === 'fr' && !city) {
      setJourney(initial);
      return;
    }
    fetchJourney(initial.slug, language, city).then((data) => active && data && setJourney(data)).catch(() => { /* version initiale conservée */ });
    return () => { active = false; };
  }, [initial, language, city]);

  const cityName = cities.find((c) => c.slug === city)?.name;

  return (
    <div>
      {showTitle && (
        <header>
          <h1 className="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{journey.title}</h1>
          {journey.description && <p className="mt-3 max-w-3xl text-lg text-slate-600">{journey.description}</p>}
        </header>
      )}

      <div className="mt-6 flex flex-col gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <p className="text-sm font-semibold text-emerald-900">{t('journeys.total')}</p>
          <p className="mt-1 text-2xl font-bold text-slate-950">
            {journey.total.complete ? formatFcfa(language, journey.total.amount) : t('journeys.at_least', { amount: formatFcfa(language, journey.total.amount) })}
          </p>
          {!journey.total.complete && <p className="mt-1 text-sm text-amber-800">{t('journeys.total_note')}</p>}
        </div>
        <CitySelect id="journey-city" value={city} onChange={setCity} label={t('offices.city_label')} emptyLabel={t('offices.all_cities')} />
      </div>

      <ol className="mt-8 space-y-6">
        {journey.steps.map((step) => (
          <li key={step.slug} className="relative rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div className="flex items-start gap-4">
              <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-900 font-bold text-white" aria-hidden="true">{step.position}</span>
              <div className="min-w-0 flex-1">
                <p className="text-xs font-bold uppercase tracking-wide text-blue-700">{t('journeys.step', { n: step.position })}</p>
                <h2 className="mt-1 text-xl font-bold text-slate-950">{step.title}</h2>
                {step.summary && <p className="mt-1 text-slate-600">{step.summary}</p>}
                <p className="mt-3 flex flex-wrap gap-4 text-sm font-medium text-slate-700">
                  <span className="inline-flex items-center gap-1.5"><Wallet size={16} aria-hidden="true" className="text-emerald-700" /> {step.cost}</span>
                  {step.processing_time && <span className="inline-flex items-center gap-1.5"><Clock3 size={16} aria-hidden="true" className="text-amber-700" /> {step.processing_time}</span>}
                </p>

                {step.required_documents.length > 0 && (
                  <div className="mt-4">
                    <p className="flex items-center gap-1.5 text-sm font-semibold text-slate-800"><FileText size={15} aria-hidden="true" /> {t('journeys.documents')}</p>
                    <ul className="mt-2 list-inside list-disc space-y-1 text-sm text-slate-700">
                      {step.required_documents.map((document) => <li key={document}>{document}</li>)}
                    </ul>
                  </div>
                )}

                {city && step.offices.length > 0 && (
                  <div className="mt-4">
                    <p className="text-sm font-semibold text-slate-800">{t('journeys.offices_in', { city: cityName ?? city })}</p>
                    <ul className="mt-2 grid gap-3 sm:grid-cols-2">{step.offices.map((office) => <OfficeCard key={office.id} office={office} />)}</ul>
                  </div>
                )}

                <AppLink href={`/demarches/${step.slug}`} className="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-800 hover:underline">
                  {t('journeys.open_fiche')} <ArrowRight size={15} aria-hidden="true" className="rtl:-scale-x-100" />
                </AppLink>
              </div>
            </div>
          </li>
        ))}
      </ol>

    </div>
  );
}
