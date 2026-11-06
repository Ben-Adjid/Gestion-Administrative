'use client';

import { useEffect, useState } from 'react';
import Link from '@/components/AppLink';
import { AlertTriangle, ArrowLeft, Clock3, FileText, Info, MessageCircle } from 'lucide-react';
import { CostEstimate } from '@/components/fiche/CostEstimate';
import { DocumentChecklist } from '@/components/fiche/DocumentChecklist';
import { ListenButton } from '@/components/fiche/ListenButton';
import { ApplyOnlineButton } from '@/components/fiche/ApplyOnlineButton';
import { PlaceSection, spokenPlace } from '@/components/fiche/OfficesSection';
import { useCities } from '@/components/CitySelect';
import { usePreferences } from '@/contexts/PreferencesContext';
import { ReviewsSection } from '@/components/fiche/ReviewsSection';
import { ShareImageButton } from '@/components/fiche/ShareImageButton';
import { QuestionsSection } from '@/components/QuestionsSection';
import { whatsappLink } from '@/lib/whatsapp';
import HeartButton from '@/components/HeartButton';
import ShareMenu from '@/components/ShareMenu';
import { PrintButton } from '@/components/PrintButton';
import { API_V1_URL } from '@/lib/api';
import { formatDate, translate, type Language } from '@/lib/i18n';
import { useLanguage } from '@/hooks/useLanguage';
import { Demarche, PlaceForCity } from '@/types';

type LocalizedDemarche = Demarche & { language?: string; is_translated?: boolean };

function asStringList(value: unknown): string[] {
  if (Array.isArray(value)) return value.filter((item): item is string => typeof item === 'string');
  return [];
}

/** Affiche une fiche ; la version française vient du serveur (SEO), les autres langues sont chargées à la demande. */
export default function DemarcheView({ initial }: { initial: LocalizedDemarche }) {
  const { language, t } = useLanguage();
  const { city } = usePreferences();
  const cities = useCities();
  const [demarche, setDemarche] = useState<LocalizedDemarche>(initial);
  const [translationFailed, setTranslationFailed] = useState(false);

  // Lieu compétent renvoyé par le serveur pour la ville choisie (source unique, identique pour le site et l'API).
  const [place, setPlace] = useState<PlaceForCity | null>(null);
  const [placeLoading, setPlaceLoading] = useState(false);
  const [placeFailed, setPlaceFailed] = useState(false);

  useEffect(() => {
    setTranslationFailed(false);
    setPlaceFailed(false);
    setPlace(null);
    setPlaceLoading(Boolean(city));
    const controller = new AbortController();
    const query = new URLSearchParams({ lang: language });
    if (city) query.set('city', city);
    fetch(`${API_V1_URL}/demarches/${encodeURIComponent(initial.slug)}?${query}`, {
      signal: controller.signal,
      headers: { Accept: 'application/json' },
    })
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json() as Promise<{ data: LocalizedDemarche; place: PlaceForCity }>;
      })
      .then((json) => {
        setDemarche(json.data);
        setPlace(json.place);
        setPlaceLoading(false);
      })
      .catch((error: unknown) => {
        if (error instanceof DOMException && error.name === 'AbortError') return;
        if (language !== 'fr') setTranslationFailed(true);
        if (city) setPlaceFailed(true);
        setPlaceLoading(false);
      });
    return () => controller.abort();
  }, [language, initial, city]);

  // Langue réellement affichée par le contenu (le français tant que la traduction n'est pas arrivée).
  const contentLanguage = demarche.language ?? 'fr';
  const loadingTranslation = language !== 'fr' && contentLanguage !== language && !translationFailed;

  const documents = asStringList(demarche.required_documents);
  const steps = asStringList(demarche.procedure_steps);
  const conditions = asStringList(demarche.conditions);
  const reportHref = `/signalement?${new URLSearchParams({ demarche_id: String(demarche.id), title: demarche.title })}`;
  const showUntranslated = language !== 'fr' && demarche.is_translated === false;
  const whatsapp = whatsappLink(demarche.title);

  // Fiche jamais vérifiée, ou vérifiée il y a plus de 6 mois : le citoyen en est averti.
  const verification: 'never' | 'stale' | 'ok' = !demarche.last_verified_at
    ? 'never'
    : Date.now() - new Date(demarche.last_verified_at).getTime() > 183 * 24 * 3600 * 1000 ? 'stale' : 'ok';

  // Texte lu à voix haute : construit à partir du contenu affiché, dans sa langue (jamais l'ancien texte),
  // avec le lieu correspondant à la ville choisie.
  // Libellés lus dans la langue du contenu affiché (le français si la traduction a échoué) : jamais de mélange.
  const tc = (key: string, vars?: Record<string, string | number>) => translate(contentLanguage as Language, key, vars);
  const cityName = cities.find((c) => c.slug === city)?.name ?? city ?? '';
  const spoken = spokenPlace(place, cityName, tc);
  const spokenText = [
    `${demarche.title}.`,
    demarche.summary ?? '',
    `${tc('fiche.cost')} : ${demarche.cost || tc('catalog.cost_unknown')}.`,
    `${tc('fiche.delay')} : ${demarche.processing_time || tc('catalog.delay_unknown')}.`,
    demarche.validity ? `${tc('fiche.validity')} : ${demarche.validity}.` : '',
    documents.length ? `${tc('fiche.documents')} : ${documents.join(' ; ')}.` : '',
    steps.length ? `${tc('fiche.steps')} : ${steps.map((step, index) => `${index + 1}. ${step}`).join(' ')}` : '',
    conditions.length ? `${tc('fiche.conditions')} : ${conditions.join(' ; ')}.` : '',
    `${tc('placex.authority')} : ${demarche.institution}.`,
    spoken ? `${tc('fiche.place')} : ${spoken}.` : '',
  ].filter(Boolean).join('\n');

  const printUrl = `${(process.env.NEXT_PUBLIC_APP_URL || '').replace(/\/+$/, '')}/demarches/${demarche.slug}`;

  return (
    <div className="mx-auto max-w-5xl px-4 py-8 sm:px-8 print:max-w-none print:p-0">
      <Link href="/demarches" className="inline-flex items-center gap-2 rounded-sm font-semibold text-blue-800 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 print:hidden">
        <ArrowLeft size={18} aria-hidden="true" className="rtl:-scale-x-100" /> {t('fiche.back')}
      </Link>

      {/* En-tête réservé à l'impression : nom du site, adresse de la fiche et date d'impression. */}
      <div className="hidden print:mb-4 print:flex print:items-end print:justify-between print:border-b-2 print:border-slate-900 print:pb-2">
        <p className="text-lg font-extrabold" dir="ltr">Demarches<span className="text-blue-800">Tchad</span></p>
        <p className="text-end text-xs text-slate-600" dir="ltr">{printUrl || `/demarches/${demarche.slug}`} · {t('printx.printed_on', { date: formatDate(language, new Date()) })}</p>
      </div>

      {showUntranslated && (
        <p role="status" className="mt-4 flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 print:hidden">
          <Info size={17} aria-hidden="true" /> {t('fiche.translation_missing')}
        </p>
      )}
      {translationFailed && (
        <p role="alert" className="mt-4 flex items-center gap-2 rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-950 print:hidden">
          <AlertTriangle size={17} aria-hidden="true" /> {t('printx.translation_failed')}
        </p>
      )}

      {loadingTranslation ? (
        <div role="status" aria-label={t('common.loading')} className="mt-6 space-y-4 rounded-3xl border border-slate-200 bg-white p-6 sm:p-10">
          <div className="h-6 w-32 animate-pulse rounded-full bg-slate-100" />
          <div className="h-10 w-3/4 animate-pulse rounded-xl bg-slate-100" />
          <div className="h-20 animate-pulse rounded-xl bg-slate-100" />
          <div className="grid gap-4 sm:grid-cols-3">{[0, 1, 2].map((i) => <div key={i} className="h-24 animate-pulse rounded-xl bg-slate-100" />)}</div>
          <div className="h-40 animate-pulse rounded-xl bg-slate-100" />
        </div>
      ) : (
      <article lang={contentLanguage} dir={contentLanguage === 'ar' ? 'rtl' : 'ltr'} className="fiche-print mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm print:mt-0 print:overflow-visible print:rounded-none print:border-0 print:shadow-none">
        <header className="border-b border-slate-200 bg-gradient-to-b from-blue-50/60 to-white p-6 sm:p-10 print:bg-none print:p-0 print:pb-4">
          <p className="inline-flex rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-900 print:bg-transparent print:p-0 print:text-xs print:uppercase print:tracking-wide">
            {demarche.categorie?.name || t('catalog.default_category')}
          </p>
          <h1 className="mt-4 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl print:mt-1 print:text-2xl">{demarche.title}</h1>
          <p className="mt-4 max-w-3xl whitespace-pre-line leading-7 text-slate-600 print:mt-2 print:text-sm print:leading-6">{demarche.description || demarche.summary}</p>

          {/* Barre d'actions : mêmes boutons, même style, regroupés sous le titre. */}
          <div className="mt-6 flex flex-wrap items-start gap-2 border-t border-slate-200/80 pt-5 print:hidden" role="toolbar" aria-label={t('printx.actions')}>
            <PrintButton />
            <ShareMenu demarcheId={demarche.id} demarcheTitle={demarche.title} demarcheSlug={demarche.slug} />
            <ShareImageButton slug={demarche.slug} title={demarche.title} cost={demarche.cost} delay={demarche.processing_time} documents={documents} language={contentLanguage as Language} />
            <ListenButton text={spokenText} language={contentLanguage as Language} />
            <ApplyOnlineButton />
            <HeartButton demarcheId={demarche.id} withLabel />
            {whatsapp && (
              <a href={whatsapp} target="_blank" rel="noreferrer noopener" className="inline-flex items-center gap-2 rounded-full border border-emerald-300 bg-emerald-50 px-3.5 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-100">
                <MessageCircle size={18} aria-hidden="true" /> {t('wa.ask')}
              </a>
            )}
          </div>

          {/* Date de vérification visible sur chaque fiche (cahier des charges §13.4) */}
          <p className={`mt-5 rounded-lg px-3 py-2 text-sm print:mt-3 print:border print:border-slate-400 print:bg-transparent print:text-xs ${verification === 'ok' ? 'bg-emerald-50 text-emerald-900' : 'bg-amber-50 text-amber-900'}`}>
            <strong>
              {verification === 'never'
                ? t('fichex.verif_never')
                : verification === 'stale'
                  ? t('fichex.verif_stale', { date: formatDate(language, demarche.last_verified_at!) })
                  : t('fiche.verified_on', { date: formatDate(language, demarche.last_verified_at!) })}
            </strong>{' '}
            {t('fiche.disclaimer')}
          </p>
        </header>

        <div className="grid gap-4 border-b border-slate-200 bg-slate-50 p-5 sm:grid-cols-2 sm:p-7 lg:grid-cols-4 print:grid-cols-4 print:gap-2 print:border-y print:border-slate-300 print:bg-transparent print:px-0 print:py-3">
          <div className="rounded-xl border border-slate-200 bg-white p-4 print:p-2">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{t('fiche.cost')}</p>
            <p className="mt-2 text-lg font-bold text-slate-900 print:mt-1 print:text-base">{demarche.cost || t('catalog.cost_unknown')}</p>
          </div>
          <div className="rounded-xl border border-slate-200 bg-white p-4 print:p-2">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{t('fiche.delay')}</p>
            <p className="mt-2 flex items-center gap-2 text-lg font-bold text-slate-900 print:mt-1 print:text-base">
              <Clock3 size={18} aria-hidden="true" className="print:hidden" /> {demarche.processing_time || t('catalog.delay_unknown')}
            </p>
          </div>
          <div className="rounded-xl border border-slate-200 bg-white p-4 print:p-2">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{t('fiche.validity')}</p>
            <p className="mt-2 text-lg font-bold text-slate-900 print:mt-1 print:text-base">{demarche.validity || t('catalog.validity_unknown')}</p>
          </div>
          <div className="rounded-xl border border-slate-200 bg-white p-4 print:p-2">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{t('fiche.institution')}</p>
            <p className="mt-2 text-lg font-bold text-slate-900 print:mt-1 print:text-base">{demarche.institution}</p>
          </div>
        </div>

        <div className="space-y-9 p-6 sm:p-10 print:space-y-5 print:px-0 print:py-4">
          <section className="break-inside-avoid-page">
            <h2 className="flex items-center gap-2 text-xl font-bold text-slate-950 print:text-lg">
              <FileText size={20} aria-hidden="true" className="text-blue-700 print:hidden" /> {t('fiche.documents')}
            </h2>
            {documents.length ? (
              <DocumentChecklist slug={demarche.slug} documents={documents} />
            ) : <p className="mt-3 text-slate-600">{t('fiche.no_documents')}</p>}
          </section>

          <CostEstimate cost={demarche.cost} estimate={demarche.cost_estimate} extras={demarche.extra_costs} />

          <section>
            <h2 className="text-xl font-bold text-slate-950 print:text-lg">{t('fiche.steps')}</h2>
            {steps.length ? (
              <ol className="mt-4 space-y-3 print:mt-2 print:space-y-1.5">
                {steps.map((step, index) => (
                  <li key={`${index}-${step}`} id={`step-${index + 1}`} className="flex break-inside-avoid items-start gap-4 rounded-xl border border-slate-200 p-4 print:gap-3 print:border-0 print:p-0">
                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-900 text-sm font-bold text-white print:h-6 print:w-6 print:border print:border-slate-900 print:bg-transparent print:text-xs print:text-slate-900">{index + 1}</span>
                    <span className="pt-1 leading-6 text-slate-700 print:pt-0 print:text-sm">{step}</span>
                  </li>
                ))}
              </ol>
            ) : <p className="mt-3 text-slate-600">{t('fiche.no_steps')}</p>}
          </section>

          {conditions.length > 0 && (
            <section className="break-inside-avoid">
              <h2 className="text-xl font-bold text-slate-950 print:text-lg">{t('fiche.conditions')}</h2>
              <ul className="mt-4 list-inside list-disc space-y-2 text-slate-700 print:mt-2 print:space-y-1 print:text-sm">
                {conditions.map((condition, index) => <li key={`${index}-${condition}`}>{condition}</li>)}
              </ul>
            </section>
          )}

          {placeLoading && <p role="status" className="rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">{t('common.loading')}</p>}
          {placeFailed && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{t('common.network_error')}</p>}
          <PlaceSection demarche={demarche} place={placeLoading ? null : place} />

          <div className="space-y-9 print:hidden">
            <ReviewsSection slug={demarche.slug} />

            <QuestionsSection demarcheSlug={demarche.slug} />

            <section className="flex flex-col gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:flex-row sm:items-center sm:justify-between">
              <p className="flex items-start gap-3 text-amber-950">
                <AlertTriangle size={20} aria-hidden="true" className="mt-0.5 shrink-0" />
                {t('fiche.report_text')}
              </p>
              <Link href={reportHref} className="shrink-0 rounded-lg bg-amber-600 px-4 py-2.5 text-center font-semibold text-white hover:bg-amber-700">
                {t('fiche.report_button')}
              </Link>
            </section>
          </div>
        </div>
      </article>
      )}
    </div>
  );
}
