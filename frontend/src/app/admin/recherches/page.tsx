'use client';

import { useCallback, useEffect, useState } from 'react';
import { Check, Plus } from 'lucide-react';
import Link from 'next/link';
import { AdminNotice, AdminShell, smallButton } from '@/components/admin/AdminShell';
import { isModeratorRole, useCurrentAdmin } from '@/components/AdminSidebar';
import { fetchApi } from '@/lib/api';
import { formatDate } from '@/lib/i18n';
import { useLanguage } from '@/hooks/useLanguage';

interface SearchMiss { id: number; term: string; count: number; last_searched_at: string }

/** Recherches sans résultat : les fiches que les visiteurs cherchent et ne trouvent pas. */
export default function SearchMissesPage() {
  const { language, t } = useLanguage();
  const admin = useCurrentAdmin();
  const [misses, setMisses] = useState<SearchMiss[]>([]);
  const [loaded, setLoaded] = useState(false);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');

  const load = useCallback(() => {
    fetchApi<{ data: SearchMiss[] }>('/admin/search-misses')
      .then((res) => { setMisses(res.data); setLoaded(true); })
      .catch((e: unknown) => { setError(e instanceof Error ? e.message : t('common.error_generic')); setLoaded(true); });
  }, [t]);

  useEffect(load, [load]);

  const dismiss = async (miss: SearchMiss) => {
    setError('');
    try {
      await fetchApi(`/admin/search-misses/${miss.id}`, { method: 'PATCH' });
      setMessage(t('adx.saved'));
      setMisses((list) => list.filter((m) => m.id !== miss.id));
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : t('common.error_generic'));
    }
  };

  return (
    <AdminShell title={t('adx.s_title')} intro={t('adx.s_intro')}>
      <AdminNotice message={message} error={error} />
      {!loaded && <p role="status" className="text-slate-500">{t('adx.loading')}</p>}
      {loaded && misses.length === 0 && <p className="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-slate-600">{t('adx.s_empty')}</p>}
      {misses.length > 0 && (
        <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
          <table className="min-w-full text-sm">
            <thead className="bg-slate-50 text-start text-xs font-bold uppercase tracking-wide text-slate-500">
              <tr>
                <th scope="col" className="px-4 py-3 text-start">{t('adx.s_term')}</th>
                <th scope="col" className="px-4 py-3 text-start">{t('adx.s_count')}</th>
                <th scope="col" className="px-4 py-3 text-start">{t('adx.s_last')}</th>
                <th scope="col" className="px-4 py-3"><span className="sr-only">{t('adx.j_actions')}</span></th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {misses.map((miss) => (
                <tr key={miss.id}>
                  <td className="px-4 py-3 font-semibold text-slate-900">{miss.term}</td>
                  <td className="px-4 py-3 text-slate-700">{miss.count}</td>
                  <td className="px-4 py-3 text-slate-600">{formatDate(language, miss.last_searched_at)}</td>
                  <td className="px-4 py-3">
                    <div className="flex flex-wrap justify-end gap-2">
                      <Link href="/admin/demarches" className={smallButton}><Plus size={14} aria-hidden="true" /> {t('adx.s_create')}</Link>
                      {isModeratorRole(admin?.role) && (
                        <button type="button" onClick={() => dismiss(miss)} className={smallButton}><Check size={14} aria-hidden="true" /> {t('adx.s_dismiss')}</button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </AdminShell>
  );
}
