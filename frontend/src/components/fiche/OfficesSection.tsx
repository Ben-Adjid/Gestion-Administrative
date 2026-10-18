'use client';

import { Building2, Clock, ExternalLink, Landmark, Mail, MapPin, Navigation, Phone } from 'lucide-react';
import { CitySelect, useCities } from '@/components/CitySelect';
import { usePreferences } from '@/contexts/PreferencesContext';
import { useLanguage } from '@/hooks/useLanguage';
import type { Demarche, Office, PlaceForCity } from '@/types';

type Translate = (key: string, vars?: Record<string, string | number>) => string;

export function OfficeCard({ office }: { office: Office }) {
  const { t } = useLanguage();

  return (
    <li className="break-inside-avoid rounded-xl border border-slate-200 bg-white p-4">
      <p className="flex items-start gap-2 font-semibold text-slate-900">
        <Building2 size={18} aria-hidden="true" className="mt-0.5 shrink-0 text-blue-700" />
        <span>{office.name}{office.city && <span className="font-normal text-slate-500"> · {office.city.name}</span>}</span>
      </p>
      {office.service_status && (
        <p className={`mt-2 ps-6 text-xs font-semibold ${office.service_status === 'confirmed' ? 'text-emerald-800' : 'text-amber-800'}`}>
          {office.service_status === 'confirmed'
            ? t('placex.service_confirmed', { date: office.service_verified_on ?? '' })
            : t('placex.service_press')}
        </p>
      )}
      {office.address && <p className="mt-1 ps-6 text-sm text-slate-600">{office.address}</p>}
      {office.opening_hours && (
        <p className="mt-1 flex items-center gap-1.5 ps-6 text-sm text-slate-600">
          <Clock size={14} aria-hidden="true" /> {t('navx.label_value', { label: t('offices.hours'), value: office.opening_hours })}
        </p>
      )}
      <div className="mt-3 flex flex-wrap items-center gap-2 ps-6">
        {office.directions_url ? (
          <a href={office.directions_url} target="_blank" rel="noreferrer noopener" className="inline-flex items-center gap-1.5 rounded-lg bg-blue-700 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-800 print:hidden">
            <Navigation size={15} aria-hidden="true" className="rtl:-scale-x-100" /> {t('offices.directions')}
          </a>
        ) : (
          <span className="text-xs text-slate-500">{t('placex.no_directions')}</span>
        )}
        {office.phone && (
          <a href={`tel:${office.phone.replace(/\s+/g, '')}`} className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" dir="ltr">
            <Phone size={15} aria-hidden="true" /> {office.phone}
          </a>
        )}
        {office.source_url && (
          <a href={office.source_url} target="_blank" rel="noreferrer noopener" className="text-xs font-semibold text-slate-500 underline hover:text-slate-800">
            {t('offices.source')}
          </a>
        )}
      </div>
    </li>
  );
}

/** Phrase lue à voix haute pour le lieu : exactement ce qui est affiché. */
export function spokenPlace(place: PlaceForCity | null, cityName: string, t: Translate): string {
  if (!place) return '';
  if (!place.has_verified_place && place.city) return t('placex.spoken_none', { city: cityName });
  const names = place.offices.slice(0, 3).map((office) => [office.name, office.address].filter(Boolean).join(', '));
  if (names.length) return names.join(' ; ');
  return place.written_place ?? '';
}

const LEVEL_KEY = { local: 'placex.level_local', regional: 'placex.level_regional', national: 'placex.level_national' } as const;

/** Comparaison sans accents ni apostrophes typographiques (« N’Djaména » = « NDjamena »). */
const fold = (value: string) => value.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/['’`\s-]/g, '').toLowerCase();

/**
 * « Où s'adresser » selon la ville choisie. Toutes les données viennent du serveur (`place`) :
 * bureaux vérifiés de la ville, lieu écrit seulement s'il vaut pour cette ville, ou un message explicite.
 */
export function PlaceSection({ demarche, place }: { demarche: Demarche; place: PlaceForCity | null }) {
  const { t } = useLanguage();
  const { city, setCity } = usePreferences();
  const cities = useCities();

  const cityName = place?.city?.name ?? cities.find((c) => c.slug === city)?.name ?? city ?? '';
  const visible = place?.offices ?? [];
  const writtenPlace = place?.written_place ?? null;
  const writtenCityName = place?.written_place_city
    ? cities.find((c) => c.slug === place.written_place_city)?.name ?? place.written_place_city
    : null;
  const nothingVerified = !!place && !!place.city && !place.has_verified_place;
  const hasLocalOffice = place?.has_local_office ?? visible.length > 0;
  const elsewhere = place?.elsewhere_offices ?? [];
  const otherCities = place?.offices_available_in ?? [];
  const level = place?.authority_level ?? demarche.authority_level ?? null;
  // Organisme nommé d'après une autre ville que celle choisie (ex. « Tribunal … de N’Djaména » pour Moundou) : on le précise.
  const institutionElsewhere = !!city && !!writtenCityName && place?.written_place_city !== city && fold(demarche.institution).includes(fold(writtenCityName));
  const hasContacts = demarche.contact_phone || demarche.contact_email || demarche.online_url || demarche.official_source_url;

  return (
    <section className="break-inside-avoid rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:p-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h2 className="text-xl font-bold text-slate-950">{t('fiche.place')}</h2>
        <div className="print:hidden">
          <CitySelect id="fiche-city" value={city} onChange={setCity} label={t('offices.city_label')} emptyLabel={t('offices.all_cities')} />
        </div>
      </div>

      {/* Organisme compétent (local, régional ou national) */}
      <div className="mt-4 flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4">
        <Landmark size={20} aria-hidden="true" className="mt-0.5 shrink-0 text-blue-700" />
        <div>
          <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{t('placex.authority')}</p>
          {institutionElsewhere && <p className="mt-0.5 text-xs font-semibold text-amber-800">{t('placex.institution_for', { city: writtenCityName ?? '' })}</p>}
          <p className="mt-0.5 font-semibold text-slate-900">{demarche.institution}</p>
          <p className="mt-1 text-sm text-slate-600">{t(level ? LEVEL_KEY[level] : 'placex.level_unknown')}</p>
        </div>
      </div>

      <div aria-live="polite">
        {!city && visible.length === 0 && !writtenPlace && <p className="mt-4 text-sm text-slate-600">{t('placex.choose_city')}</p>}

        {visible.length > 0 && (
          <ul className="mt-4 grid gap-3 sm:grid-cols-2">{visible.map((office) => <OfficeCard key={office.id} office={office} />)}</ul>
        )}

        {!hasLocalOffice && elsewhere.length > 0 && (
          <div className="mt-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-950">
            <p className="font-semibold">{t('placex.elsewhere_title', { city: cityName })}</p>
            <p className="mt-1 text-sm">{t('placex.elsewhere_hint')}</p>
            <ul className="mt-3 grid gap-3 sm:grid-cols-2">
              {elsewhere.map((office) => (
                <li key={office.id}>
                  <p className="mb-1 text-xs font-semibold text-amber-900">{t('placex.competence_not_confirmed', { city: office.city?.name ?? '' })}</p>
                  <OfficeCard office={office} />
                </li>
              ))}
            </ul>
          </div>
        )}

        {writtenPlace && (
          <div className="mt-4 flex items-start gap-2 text-slate-700">
            <MapPin size={18} aria-hidden="true" className="mt-0.5 shrink-0 text-blue-700" />
            <p>
              <span className="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                {writtenCityName ? t('placex.written_place', { city: writtenCityName }) : t('placex.written_place_any')}
                {' · '}<span className="normal-case tracking-normal text-amber-700">{t('placex.unverified')}</span>
              </span>
              {writtenPlace}
            </p>
          </div>
        )}

        {nothingVerified && (
          <div className="mt-4 rounded-xl border border-dashed border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
            <p className="font-semibold">{t('placex.no_address', { city: cityName })}</p>
            <p className="mt-1">{t('placex.no_address_hint')}</p>
            {/* Lien officiel seulement s'il est renseigné pour la fiche : jamais un lien inventé. */}
            {demarche.official_source_url && (
              <a href={demarche.official_source_url} target="_blank" rel="noreferrer noopener" className="mt-2 inline-flex items-center gap-1.5 font-semibold text-blue-800 underline print:hidden">
                <ExternalLink size={14} aria-hidden="true" /> {t('placex.official_link')}
              </a>
            )}
            {otherCities.length > 0 && (
              <p className="mt-2 flex flex-wrap items-center gap-2 print:hidden">
                {t('offices.available_in')}
                {otherCities.map((c) => (
                  <button key={c.slug} type="button" onClick={() => setCity(c.slug)} className="rounded-full border border-amber-400 bg-white px-2.5 py-0.5 text-xs font-semibold hover:bg-amber-100">{c.name}</button>
                ))}
              </p>
            )}
          </div>
        )}
      </div>

      {hasContacts && (
        <div className="mt-5 grid gap-3 border-t border-slate-200 pt-4 sm:grid-cols-2">
          {demarche.contact_phone && (
            <a href={`tel:${demarche.contact_phone.replace(/\s+/g, '')}`} className="flex items-center gap-2 text-blue-800 hover:underline" dir="ltr">
              <Phone size={18} aria-hidden="true" />{demarche.contact_phone}
            </a>
          )}
          {demarche.contact_email && (
            <a href={`mailto:${demarche.contact_email}`} className="flex items-center gap-2 break-all text-blue-800 hover:underline" dir="ltr">
              <Mail size={18} aria-hidden="true" />{demarche.contact_email}
            </a>
          )}
          {demarche.online_url && (
            <a href={demarche.online_url} target="_blank" rel="noreferrer noopener" className="flex items-center gap-2 text-blue-800 hover:underline">
              <ExternalLink size={18} aria-hidden="true" />{t('fiche.do_online')}
            </a>
          )}
          {demarche.official_source_url && (
            <a href={demarche.official_source_url} target="_blank" rel="noreferrer noopener" className="flex items-center gap-2 text-blue-800 hover:underline">
              <ExternalLink size={18} aria-hidden="true" />{t('fichex.info_source')}
            </a>
          )}
        </div>
      )}
    </section>
  );
}
