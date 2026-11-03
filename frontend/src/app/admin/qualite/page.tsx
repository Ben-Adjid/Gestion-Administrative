'use client';

import Link from 'next/link';
import { useCallback, useEffect, useState } from 'react';
import { AdminNotice, AdminShell, smallButton, secondaryButton } from '@/components/admin/AdminShell';
import { fetchApi } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

interface Quality {
  published: number;
  published_without_source: number;
  published_never_verified: number;
  published_stale_verification: number;
  published_under_min_rules: number;
  drafts: number;
  drafts_ready_to_publish: number;
  min_rules: number;
  stale_after_months: number;
}

interface Item { id: number; slug: string; title: string; issues: string[] }
interface Draft { id: number; title: string; checks: Record<string, boolean>; missing: string[]; ready: boolean }
interface HistoryEntry { id: number; description: string; created_at: string; old_values: Record<string, unknown> | null }

const CHECKS = ['categorie', 'institution', 'cout', 'delai', 'pieces', 'etapes', 'regles', 'source_officielle', 'verifiee'] as const;

/**
 * Tableau de bord qualité : ce qui manque à chaque fiche, vérification directe, historique et restauration,
 * liste de contrôle des brouillons. Les garde-fous (source, vérification, double validation) sont appliqués par l'API.
 */
export default function QualityPage() {
  const { t } = useLanguage();
  const [data, setData] = useState<Quality | null>(null);
  const [items, setItems] = useState<Item[]>([]);
  const [drafts, setDrafts] = useState<Draft[]>([]);
  const [openHistory, setOpenHistory] = useState<number | null>(null);
  const [history, setHistory] = useState<HistoryEntry[]>([]);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const load = useCallback(() => {
    Promise.all([
      fetchApi<{ data: Quality; items: Item[] }>('/admin/quality'),
      fetchApi<{ data: Draft[] }>('/admin/quality/drafts'),
    ])
      .then(([quality, checklist]) => {
        setData(quality.data);
        setItems(quality.items);
        setDrafts(checklist.data);
        setError('');
      })
      .catch((e: unknown) => setError(e instanceof Error ? e.message : t('common.error_generic')));
  }, [t]);

  useEffect(load, [load]);

  const run = async (action: () => Promise<unknown>, done: string) => {
    setError('');
    setMessage('');
    try {
      await action();
      setMessage(done);
      load();
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : t('common.error_generic'));
    }
  };

  const toggleHistory = async (id: number) => {
    if (openHistory === id) { setOpenHistory(null); return; }
    setOpenHistory(id);
    try {
      const res = await fetchApi<{ data: HistoryEntry[] }>(`/admin/demarches/${id}/history`);
      setHistory(res.data);
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : t('common.error_generic'));
    }
  };

  const counters: Array<[string, number | undefined]> = data ? [
    [t('qualx.c_published'), data.published],
    [t('qualx.c_no_source'), data.published_without_source],
    [t('qualx.c_never_verified'), data.published_never_verified],
    [t('qualx.c_stale'), data.published_stale_verification],
    [t('qualx.c_min_rules'), data.published_under_min_rules],
    [t('qualx.c_drafts'), data.drafts],
    [t('qualx.c_ready'), data.drafts_ready_to_publish],
  ] : [];

  return (
    <AdminShell title={t('qualx.title')} intro={t('qualx.intro')}>
      <AdminNotice message={message} error={error} />
      <p className="mb-4"><Link href="/admin/geo" className="font-semibold text-blue-800 underline">{t('qualx.geo_link')}</Link></p>

      {data && (
        <dl className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {counters.map(([label, value]) => (
            <div key={label} className="rounded-2xl border border-slate-200 bg-white p-4">
              <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</dt>
              <dd className="mt-1 text-2xl font-bold text-slate-950">{value ?? 0}</dd>
            </div>
          ))}
        </dl>
      )}

      <h2 className="mt-8 text-lg font-bold text-slate-950">{t('qualx.items_title')}</h2>
      {data && items.length === 0 && <p className="mt-3 text-slate-600">{t('qualx.empty')}</p>}
      <ul className="mt-3 divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white">
        {items.map((item) => (
          <li key={item.id} className="p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <span className="font-semibold text-slate-900">{item.title}</span>
              <span className="flex flex-wrap gap-2">
                {item.issues.map((issue) => (
                  <span key={issue} className="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-900">{t(`qualx.issue_${issue}`)}</span>
                ))}
              </span>
            </div>
            <div className="mt-3 flex flex-wrap gap-2">
              {(item.issues.includes('non_verifiee') || item.issues.includes('verification_perimee')) && !item.issues.includes('sans_source') && (
                <button type="button" className={smallButton}
                  onClick={() => run(() => fetchApi(`/admin/demarches/${item.id}/verify`, { method: 'PATCH' }), t('qualx.verified_ok'))}>
                  {t('qualx.verify_btn')}
                </button>
              )}
              <button type="button" className={secondaryButton} onClick={() => toggleHistory(item.id)}>{t('qualx.history_btn')}</button>
            </div>
            {openHistory === item.id && (
              <ul className="mt-3 space-y-2 text-sm">
                {history.length === 0 && <li className="text-slate-500">{t('qualx.history_empty')}</li>}
                {history.map((entry) => (
                  <li key={entry.id} className="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 p-2">
                    <span>{entry.created_at} · {entry.description}</span>
                    {entry.old_values && (
                      <button type="button" className={smallButton}
                        onClick={() => run(() => fetchApi(`/admin/demarches/${item.id}/restore/${entry.id}`, { method: 'POST' }), t('qualx.restore_done'))}>
                        {t('qualx.restore_btn')}
                      </button>
                    )}
                  </li>
                ))}
              </ul>
            )}
          </li>
        ))}
      </ul>

      <h2 className="mt-10 text-lg font-bold text-slate-950">{t('qualx.checklist_title')}</h2>
      {data && drafts.length === 0 && <p className="mt-3 text-slate-600">{t('qualx.checklist_empty')}</p>}
      <ul className="mt-3 space-y-3">
        {drafts.map((draft) => (
          <li key={draft.id} className="rounded-2xl border border-slate-200 bg-white p-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <span className="font-semibold text-slate-900">{draft.title}</span>
              <span className={`rounded-full px-2.5 py-0.5 text-xs font-bold ${draft.ready ? 'bg-emerald-100 text-emerald-900' : 'bg-amber-100 text-amber-900'}`}>
                {draft.ready ? t('qualx.ready') : t('qualx.not_ready')}
              </span>
            </div>
            <ul className="mt-2 flex flex-wrap gap-2 text-xs">
              {CHECKS.map((check) => (
                <li key={check} className={`rounded-full px-2 py-0.5 font-semibold ${draft.checks[check] ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-800'}`}>
                  {draft.checks[check] ? '✓' : '✗'} {t(`qualx.check_${check}`)}
                </li>
              ))}
            </ul>
          </li>
        ))}
      </ul>
    </AdminShell>
  );
}
