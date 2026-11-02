'use client';

import { FormEvent, useEffect, useMemo, useState } from 'react';
import { MapPin } from 'lucide-react';
import { API_V1_URL } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';
import type { City } from '@/types';

// Une seule requête par langue pour toute la session.
const cache = new Map<string, Promise<City[]>>();

/** Échec réseau ou serveur : la promesse est rejetée (et retirée du cache) pour ne pas confondre erreur et liste vide. */
export function loadCities(language: string): Promise<City[]> {
  if (!cache.has(language)) {
    const request = fetch(`${API_V1_URL}/cities?lang=${language}`, { headers: { Accept: 'application/json' } })
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json() as Promise<{ data: City[] }>;
      })
      .then((json) => json.data);
    request.catch(() => cache.delete(language));
    cache.set(language, request);
  }
  return cache.get(language)!;
}

export type CitiesStatus = 'loading' | 'ready' | 'error';

/** Villes + état du chargement ; `retry` relance la requête après une erreur. */
export function useCitiesState(): { cities: City[]; status: CitiesStatus; retry: () => void } {
  const { language } = useLanguage();
  const [cities, setCities] = useState<City[]>([]);
  const [status, setStatus] = useState<CitiesStatus>('loading');
  const [attempt, setAttempt] = useState(0);
  useEffect(() => {
    let active = true;
    setStatus('loading');
    loadCities(language)
      .then((list) => { if (active) { setCities(list); setStatus('ready'); } })
      .catch(() => { if (active) setStatus('error'); });
    return () => { active = false; };
  }, [language, attempt]);
  return { cities, status, retry: () => setAttempt((n) => n + 1) };
}

/** Liste simple des villes pour les composants qui n'ont pas besoin de piloter le chargement. */
export function useCities(): City[] {
  return useCitiesState().cities;
}

/** Comparaison sans accents ni apostrophes (« N’Djaména » = « Ndjamena »). */
const fold = (value: string) => value.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/['’`\s-]/g, '').toLowerCase();

interface Props {
  value: string | null;
  onChange: (slug: string | null) => void;
  label: string;
  emptyLabel: string;
  id?: string;
  /** Libellé de la province des villes sans province renseignée. */
  otherGroupLabel?: string;
  /** Libellé du champ de recherche par nom. */
  searchLabel?: string;
}

/**
 * Sélecteur de localité en cascade : la province filtre les localités, et la recherche par nom retrouve
 * une localité dans tout le pays (la province se règle alors automatiquement).
 * La valeur est le slug de la localité. Une localité absente se signale par le formulaire proposé en dessous.
 */
export function CitySelect({ value, onChange, label, emptyLabel, id = 'city-select', otherGroupLabel, searchLabel }: Props) {
  const { t, language } = useLanguage();
  const { cities, status, retry } = useCitiesState();
  const otherLabel = otherGroupLabel ?? t('placex.city_other');
  const searchText = searchLabel ?? t('placex.city_search');

  const selected = cities.find((c) => c.slug === value) ?? null;
  const [province, setProvince] = useState<string>(selected?.province ?? '');
  const [query, setQuery] = useState('');

  // Quand la localité change de l'extérieur, la province suit la localité.
  useEffect(() => { setProvince(selected?.province ?? ''); }, [selected?.province]);

  const provinces = useMemo(() => {
    const names = new Set(cities.map((c) => c.province ?? ''));
    return Array.from(names).sort((a, b) => a.localeCompare(b, 'fr'));
  }, [cities]);

  const needle = fold(query.trim());
  const localities = useMemo(() => {
    // Sans province ni recherche, toutes les localités sont proposées ; la province les restreint.
    const list = needle
      ? cities.filter((c) => fold(c.name).includes(needle))
      : province ? cities.filter((c) => (c.province ?? '') === province) : cities;
    return [...list].sort((a, b) => a.name.localeCompare(b.name, 'fr'));
  }, [cities, province, needle]);

  const pickLocality = (slug: string | null) => {
    onChange(slug);
    const picked = cities.find((c) => c.slug === slug);
    if (picked) setProvince(picked.province ?? '');
  };

  const changeProvince = (name: string) => {
    setProvince(name);
    if (selected && (selected.province ?? '') !== name) onChange(null);
  };

  const selectClass = 'rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200';

  return (
    <div className="flex flex-col gap-2">
      <div className="flex flex-wrap items-center gap-2">
        <span className="flex items-center gap-2 text-sm font-semibold text-slate-700">
          <MapPin size={16} aria-hidden="true" className="text-blue-700" />
          {label}
        </span>
        <label htmlFor={`${id}-province`} className="sr-only">{t('placex.province')}</label>
        <select id={`${id}-province`} value={province} onChange={(e) => changeProvince(e.target.value)} disabled={status !== 'ready' || !!needle} className={selectClass}>
          <option value="">{t('placex.all_provinces')}</option>
          {provinces.map((name) => <option key={name || 'none'} value={name}>{name || otherLabel}</option>)}
        </select>
        {status === 'ready' && cities.length > 8 && (
          <>
            <label htmlFor={`${id}-search`} className="sr-only">{searchText}</label>
            <input
              id={`${id}-search`}
              type="search"
              placeholder={searchText}
              value={query}
              onChange={(e) => setQuery(e.target.value)}
              className="w-40 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
            />
          </>
        )}
        <label htmlFor={id} className="sr-only">{label}</label>
        <select id={id} value={value ?? ''} onChange={(e) => pickLocality(e.target.value || null)} disabled={status !== 'ready'} className={selectClass}>
          <option value="">{emptyLabel}</option>
          {localities.map((city) => (
            <option key={city.slug} value={city.slug}>{city.name}{needle && city.province ? ` (${city.province})` : ''}</option>
          ))}
        </select>
      </div>
      {status === 'loading' && <p role="status" className="text-xs text-slate-600">{t('common.loading')}</p>}
      {status === 'error' && (
        <div role="alert" className="flex flex-wrap items-center gap-2 text-xs text-red-800">
          <span>{t('common.error_generic')}</span>
          <button type="button" onClick={retry} className="font-semibold underline hover:text-red-950">{t('common.retry')}</button>
        </div>
      )}
      {status === 'ready' && cities.length === 0 && <p role="status" className="text-xs text-slate-600">{t('cityx.empty')}</p>}
      {needle && localities.length === 0 && <p className="text-xs text-slate-600">{t('placex.no_locality_found')}</p>}
      <MissingLocalityForm language={language} defaultProvince={province} />
    </div>
  );
}

/** Signalement d'une localité absente : un visiteur la propose, un administrateur la vérifie avant de l'ajouter. */
function MissingLocalityForm({ language, defaultProvince }: { language: string; defaultProvince: string }) {
  const { t } = useLanguage();
  const [open, setOpen] = useState(false);
  const [name, setName] = useState('');
  const [comment, setComment] = useState('');
  const [status, setStatus] = useState<'idle' | 'sending' | 'sent' | 'error'>('idle');

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setStatus('sending');
    try {
      const res = await fetch(`${API_V1_URL}/geo/suggestions?lang=${language}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ name, province: defaultProvince || null, comment: comment || null }),
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      setStatus('sent');
      setName('');
      setComment('');
    } catch {
      setStatus('error');
    }
  };

  if (!open) {
    return (
      <button type="button" onClick={() => setOpen(true)} className="w-fit text-xs font-semibold text-blue-800 underline hover:text-blue-950 print:hidden">
        {t('placex.missing_locality')}
      </button>
    );
  }

  return (
    <form onSubmit={submit} className="flex flex-wrap items-end gap-2 rounded-lg border border-slate-200 bg-white p-3 print:hidden">
      <label className="text-xs font-semibold text-slate-700">
        {t('placex.missing_name')}
        <input required minLength={2} maxLength={120} value={name} onChange={(e) => setName(e.target.value)} className="mt-1 block w-48 rounded-lg border border-slate-300 px-2 py-1.5 text-sm" />
      </label>
      <label className="text-xs font-semibold text-slate-700">
        {t('placex.missing_comment')}
        <input maxLength={500} value={comment} onChange={(e) => setComment(e.target.value)} className="mt-1 block w-56 rounded-lg border border-slate-300 px-2 py-1.5 text-sm" />
      </label>
      <button type="submit" disabled={status === 'sending'} className="rounded-lg bg-blue-700 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-50">
        {t('placex.missing_send')}
      </button>
      {status === 'sent' && <p role="status" className="text-xs text-emerald-800">{t('placex.missing_sent')}</p>}
      {status === 'error' && <p role="alert" className="text-xs text-red-700">{t('common.error_generic')}</p>}
    </form>
  );
}
