'use client';

import { useEffect, useState } from 'react';
import { BellRing, CalendarCheck2, ExternalLink, FileText, Infinity as InfinityIcon, Link2, RefreshCw, ShieldAlert, ShieldCheck, ShieldQuestion } from 'lucide-react';
import AppLink from '@/components/AppLink';
import { ListSkeleton } from '@/components/ListSkeleton';
import { API_V1_URL } from '@/lib/api';
import { formatDate, formatDays, type Language } from '@/lib/i18n';
import { useLanguage } from '@/hooks/useLanguage';

export interface ValidityRule {
  key: string;
  name: string;
  country_code: string;
  category: 'identity' | 'travel' | 'transport' | 'civil_status' | 'justice' | 'other';
  validity_months: number | null;
  validity_start: 'issue_date' | 'not_applicable' | 'unknown';
  can_compute_expiry: boolean;
  renewal: string | null;
  notes: string | null;
  status: 'confirmed' | 'secondary' | 'to_verify' | 'business_requirement' | 'permanent' | 'status_based';
  source_label: string | null;
  source_url: string | null;
  source_date: string | null;
  verified_on: string | null;
  demarche_slug: string | null;
}

const LOCALES = { fr: 'fr-FR', en: 'en-GB', ar: 'ar-TD' } as const;

/** Statuts sans durée ni date d'expiration (acte permanent ou lié au statut). */
const OPEN_ENDED: ValidityRule['status'][] = ['permanent', 'status_based'];

const STATUS_STYLE: Record<ValidityRule['status'], { icon: typeof ShieldCheck; badge: string; border: string }> = {
  confirmed: { icon: ShieldCheck, badge: 'bg-emerald-100 text-emerald-900', border: 'border-s-emerald-500' },
  secondary: { icon: ShieldAlert, badge: 'bg-sky-100 text-sky-900', border: 'border-s-sky-500' },
  to_verify: { icon: ShieldQuestion, badge: 'bg-amber-100 text-amber-900', border: 'border-s-amber-500' },
  business_requirement: { icon: ShieldQuestion, badge: 'bg-violet-100 text-violet-900', border: 'border-s-violet-500' },
  permanent: { icon: InfinityIcon, badge: 'bg-emerald-100 text-emerald-900', border: 'border-s-emerald-500' },
  status_based: { icon: Link2, badge: 'bg-slate-200 text-slate-900', border: 'border-s-slate-500' },
};

/** Statut inconnu (nouveau côté serveur) : style neutre, jamais de plantage. */
const FALLBACK_STYLE = { icon: ShieldQuestion, badge: 'bg-slate-100 text-slate-800', border: 'border-s-slate-400' };

/** « 10 ans », « 4 years », « 18 mois » : durée lisible et accordée dans chaque langue. */
function formatDuration(language: Language, months: number): string {
  const unit = months % 12 === 0 ? { unit: 'year', value: months / 12 } : { unit: 'month', value: months };
  return new Intl.NumberFormat(LOCALES[language], { style: 'unit', unit: unit.unit, unitDisplay: 'long' }).format(unit.value);
}

/** Ajoute des mois sans déborder sur le mois suivant (29/02 + 10 ans → 28/02). */
export function addMonths(isoDate: string, months: number): Date {
  const [y, m, d] = isoDate.split('-').map(Number);
  const target = new Date(y, m - 1 + months, 1);
  const lastDay = new Date(target.getFullYear(), target.getMonth() + 1, 0).getDate();
  target.setDate(Math.min(d, lastDay));
  return target;
}

function toIso(date: Date): string {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function ExpiryCalculator({ rule }: { rule: ValidityRule }) {
  const { language, t } = useLanguage();
  const [issuedOn, setIssuedOn] = useState('');
  const inputId = `issued-${rule.key}`;

  // Actes sans date d'expiration : on le dit, sans inventer d'échéance.
  if (rule.status === 'permanent') return <p className="text-sm text-slate-600">{t('valx.expiry_permanent')}</p>;
  if (rule.status === 'status_based') return <p className="text-sm text-slate-600">{t('valx.expiry_status_based')}</p>;


  if (!rule.can_compute_expiry || rule.validity_months === null) {
    return <p className="text-sm text-slate-600">{t('valx.expiry_not_computable')}</p>;
  }

  const expiry = issuedOn ? addMonths(issuedOn, rule.validity_months) : null;
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const days = expiry ? Math.round((expiry.getTime() - today.getTime()) / 86_400_000) : 0;

  return (
    <div>
      <label htmlFor={inputId} className="block text-sm font-medium text-slate-700">{t('valx.issued_on_label')}</label>
      <input
        id={inputId}
        type="date"
        value={issuedOn}
        max={toIso(today)}
        onChange={(e) => setIssuedOn(e.target.value)}
        className="mt-1 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
      />
      {expiry && (
        <p role="status" className="mt-2 text-sm">
          <strong className="text-slate-900">{t('valx.expires_on', { date: formatDate(language, toIso(expiry)) })}</strong>{' '}
          <span className={days < 0 ? 'font-semibold text-red-700' : days <= 90 ? 'font-semibold text-amber-700' : 'text-slate-600'}>
            {days === 0 ? t('valx.expires_today') : days > 0 ? t('valx.remaining', { days: formatDays(language, days) }) : t('valx.expired_since', { days: formatDays(language, -days) })}
          </span>
        </p>
      )}
      {rule.status === 'secondary' && <p className="mt-1 text-xs text-slate-500">{t('valx.expiry_indicative')}</p>}
    </div>
  );
}

/** Validité des documents : durée, point de départ, expiration calculable, renouvellement, source et date de vérification. */
export function DocumentValidity() {
  const { language, t } = useLanguage();
  const [rules, setRules] = useState<ValidityRule[] | null>(null);
  const [failed, setFailed] = useState(false);
  const [attempt, setAttempt] = useState(0);

  useEffect(() => {
    let active = true;
    setFailed(false);
    fetch(`${API_V1_URL}/document-validity?lang=${language}`, { headers: { Accept: 'application/json' } })
      .then((res) => {
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        return res.json() as Promise<{ data: ValidityRule[] }>;
      })
      .then((json) => { if (active) setRules(json.data); })
      .catch(() => { if (active) setFailed(true); });
    return () => { active = false; };
  }, [language, attempt]);

  if (failed) {
    return (
      <div role="alert" className="mt-6 rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800">
        <p>{t('valx.error')}</p>
        <button type="button" onClick={() => setAttempt((n) => n + 1)} className="mt-3 inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
          <RefreshCw size={15} aria-hidden="true" /> {t('common.retry')}
        </button>
      </div>
    );
  }
  if (!rules) return <ListSkeleton rows={4} className="mt-6" />;
  if (rules.length === 0) return <p className="mt-6 rounded-2xl border border-dashed border-slate-300 p-6 text-slate-600">{t('valx.empty')}</p>;

  const field = 'text-xs font-semibold uppercase tracking-wide text-slate-500';

  return (
    <ul className="mt-6 space-y-5">
      {rules.map((rule) => {
        const style = STATUS_STYLE[rule.status] ?? FALLBACK_STYLE;
        const StatusIcon = style.icon;
        return (
          <li key={rule.key} className={`rounded-2xl border border-s-4 border-slate-200 bg-white p-5 shadow-sm sm:p-6 ${style.border}`}>
            <div className="flex flex-wrap items-center gap-2">
              <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold ${style.badge}`}>
                <StatusIcon size={13} aria-hidden="true" /> {t(`valx.status_${rule.status}`)}
              </span>
              <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-700">{t(`valx.cat_${rule.category}`)}</span>
              <span className="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-700">{t(`valx.country_${rule.country_code}`)}</span>
            </div>
            <h2 className="mt-2 flex items-center gap-2 text-lg font-bold text-slate-950">
              <FileText size={18} aria-hidden="true" className="text-blue-700" /> {rule.name}
            </h2>

            <dl className="mt-4 grid gap-4 sm:grid-cols-3">
              <div>
                <dt className={field}>{t('valx.duration')}</dt>
                <dd className={`mt-1 font-bold ${rule.validity_months || OPEN_ENDED.includes(rule.status) ? 'text-slate-900' : 'text-amber-800'}`}>
                  {rule.validity_months
                    ? formatDuration(language, rule.validity_months)
                    : OPEN_ENDED.includes(rule.status) ? t('valx.duration_none') : t('valx.duration_unknown')}
                </dd>
              </div>
              <div>
                <dt className={field}>{t('valx.start')}</dt>
                <dd className="mt-1 text-slate-800">{t(`valx.start_${rule.validity_start}`)}</dd>
              </div>
              <div>
                <dt className={field}>{t('valx.expiry')}</dt>
                <dd className="mt-1"><ExpiryCalculator rule={rule} /></dd>
              </div>
            </dl>

            {rule.notes && <p className="mt-4 rounded-xl bg-slate-50 p-3 text-sm text-slate-700">{rule.notes}</p>}
            {rule.status === 'secondary' && <p className="mt-2 text-xs text-sky-900">{t('valx.status_secondary_hint')}</p>}

            {rule.renewal && (
              <div className="mt-4">
                <p className={field}>{t('valx.renewal')}</p>
                <p className="mt-1 text-sm text-slate-700">{rule.renewal}</p>
              </div>
            )}

            <div className="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-slate-100 pt-3 text-sm">
              {rule.source_url ? (
                <a href={rule.source_url} target="_blank" rel="noreferrer noopener" className="inline-flex items-center gap-1.5 font-semibold text-blue-800 hover:underline">
                  <ExternalLink size={14} aria-hidden="true" />
                  {t('navx.label_value', { label: t('valx.source'), value: rule.source_date && rule.source_label ? t('valx.source_dated', { label: rule.source_label, date: formatDate(language, rule.source_date) }) : (rule.source_label ?? '') })}
                </a>
              ) : (
                <span className="text-amber-800">{t('valx.no_source')}</span>
              )}
              {rule.verified_on && (
                <span className="inline-flex items-center gap-1.5 text-slate-500">
                  <CalendarCheck2 size={14} aria-hidden="true" /> {t('valx.verified_on', { date: formatDate(language, rule.verified_on) })}
                </span>
              )}
              {rule.demarche_slug && (
                <AppLink href={`/demarches/${rule.demarche_slug}`} className="font-semibold text-blue-800 hover:underline">{t('valx.see_fiche')}</AppLink>
              )}
              {rule.can_compute_expiry && (
                <AppLink href="/dashboard/documents" className="inline-flex items-center gap-1.5 font-semibold text-emerald-800 hover:underline">
                  <BellRing size={14} aria-hidden="true" /> {t('valx.reminder')}
                </AppLink>
              )}
            </div>
          </li>
        );
      })}
    </ul>
  );
}
