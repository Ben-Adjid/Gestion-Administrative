'use client';

import { useEffect, useState } from 'react';
import { Activity, ChevronLeft, ChevronRight, Clock, ShieldAlert, User } from 'lucide-react';
import { AdminSidebar, useCurrentAdmin } from '@/components/AdminSidebar';
import { fetchApi } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

interface ActivityLogItem {
  id: number;
  action: string;
  description: string;
  ip_address: string | null;
  created_at: string;
  admin?: { id: number; name: string; email: string; role: string } | null;
}

interface PaginatedLogs {
  data: ActivityLogItem[];
  current_page: number;
  last_page: number;
  total: number;
}

export default function AdminActivityLogsPage() {
  const { language, t } = useLanguage();
  const currentUser = useCurrentAdmin();
  const [logs, setLogs] = useState<ActivityLogItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [error, setError] = useState('');

  useEffect(() => {
    if (currentUser?.role !== 'super_admin') return;
    let active = true;
    setLoading(true);
    fetchApi<PaginatedLogs>(`/admin/activity-logs?page=${page}`)
      .then((result) => {
        if (!active) return;
        setLogs(result.data || []);
        setLastPage(result.last_page || 1);
        setTotal(result.total || 0);
      })
      .catch((err: unknown) => active && setError(err instanceof Error ? err.message : t('adm.load_error')))
      .finally(() => active && setLoading(false));
    return () => { active = false; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [currentUser, page]);

  return (
    <div className="flex min-h-screen bg-slate-50">
      <AdminSidebar user={currentUser} />
      <main className="min-w-0 flex-1 px-4 py-8 sm:px-8">
        <div className="mx-auto max-w-6xl">
          <div className="mb-8">
            <p className="flex items-center gap-2 text-sm font-semibold text-blue-700"><ShieldAlert className="h-4 w-4" aria-hidden="true" /> {t('adm.l_kicker')}</p>
            <h1 className="mt-1 text-3xl font-bold tracking-tight text-slate-950">{t('adm.l_title', { n: total })}</h1>
            <p className="mt-2 text-sm text-slate-600">{t('adm.l_intro')}</p>
          </div>

          {error && <p role="alert" className="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{error}</p>}

          {loading ? (
            <p role="status" className="rounded-2xl border border-slate-200 bg-white py-16 text-center text-slate-600">{t('adm.loading')}</p>
          ) : logs.length === 0 ? (
            <p className="rounded-2xl border border-slate-200 bg-white py-16 text-center text-slate-500">{t('adm.l_empty')}</p>
          ) : (
            <>
              <ul className="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                {logs.map((log) => (
                  <li key={log.id} className="flex items-start gap-3.5 p-4 sm:p-5">
                    <span className="mt-0.5 rounded-xl bg-blue-50 p-2.5 text-blue-800"><Activity className="h-5 w-5" aria-hidden="true" /></span>
                    <div className="min-w-0">
                      <p className="text-sm font-semibold text-slate-900">{log.description}</p>
                      <div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                        <span className="inline-flex items-center gap-1 font-medium text-slate-600">
                          <User className="h-3.5 w-3.5" aria-hidden="true" />
                          {log.admin ? <>{log.admin.name} (<span dir="ltr">{log.admin.email}</span>)</> : t('adm.l_system')}
                        </span>
                        <span className="inline-flex items-center gap-1">
                          <Clock className="h-3.5 w-3.5" aria-hidden="true" />
                          {new Date(log.created_at).toLocaleString(language === 'ar' ? 'ar-TD' : language === 'en' ? 'en-GB' : 'fr-FR')}
                        </span>
                        {log.ip_address && <span className="rounded-full bg-slate-100 px-2 py-0.5" dir="ltr">{t('adm.l_ip', { ip: log.ip_address })}</span>}
                        <code className="rounded bg-slate-100 px-1.5 py-0.5 text-[11px]" dir="ltr">{log.action}</code>
                      </div>
                    </div>
                  </li>
                ))}
              </ul>

              {lastPage > 1 && (
                <div className="flex items-center justify-between py-4">
                  <p className="text-sm text-slate-600">{t('adm.l_page', { page, pages: lastPage })}</p>
                  <div className="flex items-center gap-2">
                    <button type="button" aria-label={t('catalog.previous')} onClick={() => setPage((p) => Math.max(1, p - 1))} disabled={page <= 1} className="rounded-lg border border-slate-200 bg-white p-2 hover:bg-slate-50 disabled:opacity-40">
                      <ChevronLeft className="h-5 w-5 rtl:-scale-x-100" />
                    </button>
                    <button type="button" aria-label={t('catalog.next')} onClick={() => setPage((p) => Math.min(lastPage, p + 1))} disabled={page >= lastPage} className="rounded-lg border border-slate-200 bg-white p-2 hover:bg-slate-50 disabled:opacity-40">
                      <ChevronRight className="h-5 w-5 rtl:-scale-x-100" />
                    </button>
                  </div>
                </div>
              )}
            </>
          )}
        </div>
      </main>
    </div>
  );
}
