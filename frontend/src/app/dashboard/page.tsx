'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { AlertTriangle, Bell, CalendarClock, Heart } from 'lucide-react';
import CitizenNav from '@/components/CitizenNav';
import { fetchApi } from '@/lib/api';
import { formatDate, formatDays } from '@/lib/i18n';
import { useLanguage } from '@/hooks/useLanguage';

interface Alert {
  id: number;
  type: string;
  label: string | null;
  expiration_date: string;
  days_remaining: number;
  state: 'expired' | 'urgent' | 'soon' | 'ok';
}

interface DashboardData {
  user: { name: string; email: string };
  counters: { documents: number; alerts: number; expiring_soon: number; favorites: number };
  alerts: Alert[];
  recent_favorites: Array<{ title: string; slug: string; cost: string | null; processing_time: string | null }>;
}

const STATE_STYLE: Record<Alert['state'], string> = {
  expired: 'border-red-200 bg-red-50 text-red-800',
  urgent: 'border-orange-200 bg-orange-50 text-orange-800',
  soon: 'border-amber-200 bg-amber-50 text-amber-900',
  ok: 'border-slate-200 bg-white text-slate-700',
};

export default function CitizenDashboardPage() {
  const { language, t } = useLanguage();
  const [data, setData] = useState<DashboardData | null>(null);
  const [error, setError] = useState(false);

  useEffect(() => {
    fetchApi<{ data: DashboardData }>(`/me/dashboard?lang=${language}`)
      .then((res) => setData(res.data))
      .catch(() => setError(true));
  }, [language]);

  const describe = (alert: Alert) => {
    if (alert.days_remaining < 0) return t('citizen.expired_since', { days: formatDays(language, Math.abs(alert.days_remaining)) });
    if (alert.days_remaining === 0) return t('citizen.expires_today');
    return t('citizen.expires_in', { days: formatDays(language, alert.days_remaining) });
  };

  return (
    <main className="mx-auto max-w-5xl px-4 py-10">
      <h1 className="text-3xl font-bold tracking-tight text-slate-950">
        {data ? t('citizen.hello', { name: data.user.name }) : t('citizen.my_space')}
      </h1>
      <p className="mt-2 text-slate-600">{t('citizen.intro')}</p>
      <div className="mt-6"><CitizenNav /></div>

      {error && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">{t('citizen.unavailable')}</p>}

      {!data && !error && (
        <div role="status" aria-label={t('common.loading')} className="grid gap-4 sm:grid-cols-3">
          {[0, 1, 2].map((i) => <div key={i} className="h-28 animate-pulse rounded-2xl bg-slate-100" />)}
        </div>
      )}

      {data && (
        <>
          <section className="grid gap-4 sm:grid-cols-3">
            <Link href="/dashboard/documents" className="rounded-2xl border border-slate-200 bg-white p-5 hover:border-blue-300">
              <Bell aria-hidden="true" className="text-blue-700" />
              <p className="mt-3 text-3xl font-bold text-slate-950">{data.counters.documents}</p>
              <p className="text-sm text-slate-600">{t('citizen.docs_count', { n: data.counters.documents })}</p>
            </Link>
            <Link href="/dashboard/documents" className="rounded-2xl border border-slate-200 bg-white p-5 hover:border-orange-300">
              <AlertTriangle aria-hidden="true" className="text-orange-600" />
              <p className="mt-3 text-3xl font-bold text-slate-950">{data.counters.alerts}</p>
              <p className="text-sm text-slate-600">{t('citizen.renew_count')}</p>
            </Link>
            <Link href="/dashboard/favorites" className="rounded-2xl border border-slate-200 bg-white p-5 hover:border-red-300">
              <Heart aria-hidden="true" className="text-red-600" />
              <p className="mt-3 text-3xl font-bold text-slate-950">{data.counters.favorites}</p>
              <p className="text-sm text-slate-600">{t('citizen.favs_count', { n: data.counters.favorites })}</p>
            </Link>
          </section>

          <section className="mt-10">
            <h2 className="flex items-center gap-2 text-xl font-bold text-slate-950">
              <CalendarClock aria-hidden="true" size={20} /> {t('citizen.alerts_title')}
            </h2>
            {data.alerts.length ? (
              <ul className="mt-4 space-y-3">
                {data.alerts.map((alert) => (
                  <li key={alert.id} className={`flex flex-wrap items-center justify-between gap-2 rounded-xl border p-4 ${STATE_STYLE[alert.state]}`}>
                    <span className="font-semibold">{alert.label || t(`documents.type_${alert.type}`)}</span>
                    <span className="text-sm">{describe(alert)} · {formatDate(language, alert.expiration_date)}</span>
                  </li>
                ))}
              </ul>
            ) : (
              <p className="mt-3 rounded-xl border border-dashed border-slate-300 p-6 text-slate-600">
                {t('citizen.no_alerts')}{' '}
                <Link href="/dashboard/documents" className="font-semibold text-blue-800 underline">{t('citizen.add_doc')}</Link>
              </p>
            )}
          </section>

          <section className="mt-10">
            <h2 className="flex items-center gap-2 text-xl font-bold text-slate-950">
              <Heart aria-hidden="true" size={20} /> {t('citizen.recent_favs')}
            </h2>
            {data.recent_favorites.length ? (
              <ul className="mt-4 grid gap-3 sm:grid-cols-2">
                {data.recent_favorites.map((fav) => (
                  <li key={fav.slug}>
                    <Link href={`/demarches/${fav.slug}`} className="block rounded-xl border border-slate-200 bg-white p-4 hover:border-blue-300">
                      <span className="font-semibold text-slate-900">{fav.title}</span>
                      <span className="mt-1 block text-sm text-slate-600">
                        {[fav.cost, fav.processing_time].filter(Boolean).join(' · ') || t('citizen.see_fiche')}
                      </span>
                    </Link>
                  </li>
                ))}
              </ul>
            ) : (
              <p className="mt-3 rounded-xl border border-dashed border-slate-300 p-6 text-slate-600">
                {t('citizen.no_favs')}{' '}
                <Link href="/demarches" className="font-semibold text-blue-800 underline">{t('citizen.browse')}</Link>
              </p>
            )}
          </section>
        </>
      )}
    </main>
  );
}
