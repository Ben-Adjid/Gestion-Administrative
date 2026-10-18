'use client';

import { useCallback, useEffect, useState } from 'react';
import { Activity, AlertTriangle, BarChart3, Clock, Eye, Globe, Percent, RefreshCw, Search, Users } from 'lucide-react';
import { AdminSidebar, useCurrentAdmin } from '@/components/AdminSidebar';
import { fetchApi } from '@/lib/api';
import { formatDate } from '@/lib/i18n';
import { useLanguage } from '@/hooks/useLanguage';

interface AnalyticsData {
  active_sessions: number;
  sessions_today: number;
  views_today: number;
  top_pages: Array<{ path: string; views: number }>;
  daily_visitors: Array<{ day: string; visitors: number }>;
  top_searches: Array<{ term: string; count: number; last_results: number }>;
  monthly_signalements: Array<{ month: string; received: number; handled: number }>;
  conversion: { days: number; visitors: number; signalements: number; rate: number | null };
}

const LOCALES = { fr: 'fr-FR', en: 'en-GB', ar: 'ar-TD' } as const;

export default function AdminAnalyticsPage() {
  const { language, t } = useLanguage();
  const currentUser = useCurrentAdmin();
  const [data, setData] = useState<AnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      setData(await fetchApi<AnalyticsData>('/admin/analytics'));
    } catch (err) {
      setError(err instanceof Error ? err.message : t('adm.load_error'));
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => { void load(); }, [load]);

  const max = Math.max(1, ...(data?.daily_visitors.map((d) => d.visitors) ?? [1]));
  const monthMax = Math.max(1, ...(data?.monthly_signalements.flatMap((m) => [m.received, m.handled]) ?? [1]));
  const monthLabel = (month: string) => {
    const [year, index] = month.split('-').map(Number);
    return new Date(year, index - 1, 1).toLocaleDateString(LOCALES[language], { month: 'short', year: '2-digit' });
  };
  const conversion = data?.conversion;
  const cards = [
    { label: 'adm.a_live', value: data?.active_sessions ?? 0, icon: Activity, tone: 'bg-emerald-50 text-emerald-700' },
    { label: 'adm.a_unique_today', value: data?.sessions_today ?? 0, icon: Users, tone: 'bg-blue-50 text-blue-700' },
    { label: 'adm.a_views_today', value: data?.views_today ?? 0, icon: Eye, tone: 'bg-purple-50 text-purple-700' },
  ];

  return (
    <div className="flex min-h-screen bg-slate-50">
      <AdminSidebar user={currentUser} />
      <main className="min-w-0 flex-1 px-4 py-8 sm:px-8">
        <div className="mx-auto max-w-6xl">
          <div className="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
              <p className="flex items-center gap-2 text-sm font-semibold text-blue-700"><BarChart3 className="h-4 w-4" aria-hidden="true" /> {t('adm.a_kicker')}</p>
              <h1 className="mt-1 text-3xl font-bold tracking-tight text-slate-950">{t('adm.a_title')}</h1>
              <p className="mt-2 text-sm text-slate-600">{t('adm.a_intro')}</p>
            </div>
            <button type="button" onClick={() => void load()} className="inline-flex items-center gap-2 self-start rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
              <RefreshCw className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} aria-hidden="true" /> {t('adm.a_refresh')}
            </button>
          </div>

          {error && <p role="alert" className="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{error}</p>}

          {loading && !data ? (
            <p role="status" className="rounded-2xl border border-slate-200 bg-white py-20 text-center text-slate-600">{t('adm.loading')}</p>
          ) : (
            <div className="space-y-8">
              <div className="grid gap-4 sm:grid-cols-3">
                {cards.map(({ label, value, icon: Icon, tone }) => (
                  <div key={label} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="flex items-center justify-between gap-3">
                      <p className="text-sm font-medium text-slate-600">{t(label)}</p>
                      <span className={`rounded-xl p-2.5 ${tone}`}><Icon className="h-5 w-5" aria-hidden="true" /></span>
                    </div>
                    <p className="mt-3 text-3xl font-bold tracking-tight text-slate-950">{value}</p>
                  </div>
                ))}
              </div>

              <div className="grid gap-6 lg:grid-cols-2">
                <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                  <h2 className="mb-4 flex items-center gap-2 text-lg font-bold text-slate-950"><Globe className="h-5 w-5 text-blue-700" aria-hidden="true" /> {t('adm.a_top_pages')}</h2>
                  {data?.top_pages.length ? (
                    <ol className="divide-y divide-slate-100">
                      {data.top_pages.map((p, index) => (
                        <li key={p.path} className="flex items-center justify-between gap-3 py-3 text-sm">
                          <span className="truncate font-medium text-slate-700" dir="ltr"><span className="me-2 font-mono text-slate-400">#{index + 1}</span>{p.path}</span>
                          <span className="shrink-0 rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-800">{t('adm.a_views', { n: p.views })}</span>
                        </li>
                      ))}
                    </ol>
                  ) : <p className="py-6 text-center text-sm text-slate-500">{t('adm.a_no_pages')}</p>}
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                  <h2 className="mb-4 flex items-center gap-2 text-lg font-bold text-slate-950"><Clock className="h-5 w-5 text-purple-700" aria-hidden="true" /> {t('adm.a_trend')}</h2>
                  {data?.daily_visitors.length ? (
                    <ul className="space-y-3">
                      {data.daily_visitors.map((day) => (
                        <li key={day.day} className="flex items-center justify-between gap-3 text-sm">
                          <span className="font-medium text-slate-600">{formatDate(language, day.day)}</span>
                          <span className="flex items-center gap-3">
                            <span className="h-2 w-32 overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                              <span className="block h-full rounded-full bg-blue-800" style={{ width: `${Math.max(6, (day.visitors / max) * 100)}%` }} />
                            </span>
                            <span className="w-10 text-end font-bold text-slate-900">{day.visitors}</span>
                          </span>
                        </li>
                      ))}
                    </ul>
                  ) : <p className="py-6 text-center text-sm text-slate-500">{t('adm.a_no_trend')}</p>}
                </section>
              </div>

              <div className="grid gap-6 lg:grid-cols-2">
                <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                  <h2 className="mb-4 flex items-center gap-2 text-lg font-bold text-slate-950"><Search className="h-5 w-5 text-blue-700" aria-hidden="true" /> {t('anx.top_searches')}</h2>
                  {data?.top_searches.length ? (
                    <ol className="divide-y divide-slate-100">
                      {data.top_searches.map((s, index) => (
                        <li key={s.term} className="flex items-center justify-between gap-3 py-3 text-sm">
                          <span className="truncate font-medium text-slate-700"><span className="me-2 font-mono text-slate-400">#{index + 1}</span>{s.term}</span>
                          <span className="flex shrink-0 items-center gap-2">
                            {s.last_results === 0 && <span className="rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-800">{t('anx.no_result')}</span>}
                            <span className="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-800">{t('anx.searches', { n: s.count })}</span>
                          </span>
                        </li>
                      ))}
                    </ol>
                  ) : <p className="py-6 text-center text-sm text-slate-500">{t('anx.no_searches')}</p>}
                </section>

                <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                  <h2 className="mb-4 flex items-center gap-2 text-lg font-bold text-slate-950"><Percent className="h-5 w-5 text-emerald-700" aria-hidden="true" /> {t('anx.conversion')}</h2>
                  {conversion && conversion.rate !== null ? (
                    <>
                      <p className="text-4xl font-bold tracking-tight text-slate-950">
                        {new Intl.NumberFormat(LOCALES[language], { style: 'percent', maximumFractionDigits: 2 }).format(conversion.rate / 100)}
                      </p>
                      <p className="mt-2 text-sm text-slate-600">{t('anx.conversion_detail', { signalements: conversion.signalements, visitors: conversion.visitors })}</p>
                    </>
                  ) : <p className="py-6 text-center text-sm text-slate-500">{t('anx.conversion_none')}</p>}
                </section>
              </div>

              <section className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                  <h2 className="flex items-center gap-2 text-lg font-bold text-slate-950"><AlertTriangle className="h-5 w-5 text-amber-600" aria-hidden="true" /> {t('anx.monthly')}</h2>
                  <p className="flex gap-4 text-xs font-semibold text-slate-600">
                    <span className="flex items-center gap-1.5"><span className="h-2.5 w-2.5 rounded-sm bg-amber-500" aria-hidden="true" /> {t('anx.received')}</span>
                    <span className="flex items-center gap-1.5"><span className="h-2.5 w-2.5 rounded-sm bg-emerald-600" aria-hidden="true" /> {t('anx.handled')}</span>
                  </p>
                </div>
                <table className="w-full text-sm">
                  <caption className="sr-only">{t('anx.monthly')}</caption>
                  <thead className="sr-only"><tr><th scope="col">{t('anx.monthly')}</th><th scope="col">{t('anx.received')}</th><th scope="col">{t('anx.handled')}</th></tr></thead>
                  <tbody>
                    {data?.monthly_signalements.map((m) => (
                      <tr key={m.month}>
                        <th scope="row" className="w-20 py-1.5 text-start font-medium text-slate-600">{monthLabel(m.month)}</th>
                        <td className="py-1.5">
                          <span className="flex items-center gap-2">
                            <span className="h-2.5 rounded-sm bg-amber-500" style={{ width: `${(m.received / monthMax) * 70}%`, minWidth: m.received ? 4 : 0 }} aria-hidden="true" />
                            <span className="font-bold text-slate-900">{m.received}</span>
                          </span>
                        </td>
                        <td className="py-1.5">
                          <span className="flex items-center gap-2">
                            <span className="h-2.5 rounded-sm bg-emerald-600" style={{ width: `${(m.handled / monthMax) * 70}%`, minWidth: m.handled ? 4 : 0 }} aria-hidden="true" />
                            <span className="font-bold text-slate-900">{m.handled}</span>
                          </span>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </section>
            </div>
          )}
        </div>
      </main>
    </div>
  );
}
