'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { Activity, AlertTriangle, BarChart3, CheckCircle2, FileText, Folder, Shield, Users } from 'lucide-react';
import { fetchApi } from '@/lib/api';
import { formatDate } from '@/lib/i18n';
import { AdminSidebar, AdminUser } from '@/components/AdminSidebar';
import { useLanguage } from '@/hooks/useLanguage';

type AdminRole = 'editor' | 'admin' | 'super_admin';

interface DashboardData {
  role: AdminRole;
  capabilities: Record<string, boolean>;
  content: {
    published: number;
    drafts: number;
    archived: number;
    needs_verification: number;
    to_verify: Array<{ id: number; title: string; slug: string; last_verified_at: string | null }>;
  };
  signalements: {
    pending: number;
    recent: Array<{ id: number; status: string; excerpt: string; demarche: { title: string } | null; created_at: string | null }>;
  };
  my_work?: {
    my_drafts: number;
    my_published: number;
    drafts: Array<{ id: number; title: string; updated_at: string | null }>;
    recent_updates: Array<{ id: number; title: string; status: string }>;
  };
  validation_queue?: Array<{ id: number; title: string; author: string | null }>;
  audience?: { visitors_today: number; views_today: number; top_demarches: Array<{ id: number; title: string; views_count: number }> };
  team?: { total: number; active: number; by_role: Record<AdminRole, number>; locked: number; with_failed_logins: number };
  recent_activity?: Array<{ id: number; description: string; admin: string | null; created_at: string | null }>;
}

function StatCard({ label, value, icon: Icon, tone }: { label: string; value: number; icon: typeof FileText; tone: string }) {
  return (
    <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div className="flex items-center justify-between gap-4">
        <p className="text-sm font-medium text-slate-600">{label}</p>
        <span className={`rounded-xl p-2 ${tone}`}><Icon aria-hidden="true" size={19} /></span>
      </div>
      <p className="mt-3 text-3xl font-bold tracking-tight text-slate-950">{value}</p>
    </div>
  );
}

function Shortcut({ href, label, description, icon: Icon }: { href: string; label: string; description: string; icon: typeof FileText }) {
  return (
    <Link href={href} className="group flex items-start gap-4 rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-blue-300 hover:shadow-sm">
      <span className="rounded-xl bg-blue-50 p-3 text-blue-800"><Icon aria-hidden="true" size={21} /></span>
      <span>
        <span className="block font-semibold text-slate-900 group-hover:text-blue-800">{label}</span>
        <span className="mt-1 block text-sm text-slate-600">{description}</span>
      </span>
    </Link>
  );
}

const card = 'rounded-2xl border border-slate-200 bg-white p-6';

export default function AdminDashboard() {
  const { language, t } = useLanguage();
  const [dashboard, setDashboard] = useState<DashboardData | null>(null);
  const [currentUser, setCurrentUser] = useState<AdminUser | null>(null);
  const [error, setError] = useState('');

  useEffect(() => {
    try {
      const raw = localStorage.getItem('admin');
      if (raw) setCurrentUser(JSON.parse(raw));
    } catch {
      setCurrentUser(null);
    }

    let active = true;
    fetchApi<{ data: DashboardData }>('/admin/stats')
      .then((response) => active && setDashboard(response.data))
      .catch((err: unknown) => active && setError(err instanceof Error ? err.message : t('common.error_generic')));
    return () => { active = false; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const date = (value: string | null) => (value ? formatDate(language, value) : '—');

  return (
    <div className="flex min-h-screen bg-slate-50">
      <AdminSidebar user={currentUser} />
      <main className="min-w-0 flex-1 px-4 py-8 sm:px-8">
        <div className="mx-auto max-w-7xl">
          {error && <p role="alert" className="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">{error}</p>}
          {!dashboard && !error && (
            <div role="status" aria-label={t('common.loading')} className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
              {[0, 1, 2, 3].map((i) => <div key={i} className="h-28 animate-pulse rounded-2xl bg-slate-100" />)}
            </div>
          )}

          {dashboard && (
            <>
              <header className="mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                  <p className="text-sm font-semibold text-blue-800">{t(`admin.role_${dashboard.role}`)}</p>
                  <h1 className="mt-1 text-3xl font-bold tracking-tight text-slate-950">{t('admin.dash_title')}</h1>
                  <p className="mt-2 text-slate-600">{t('admin.dash_intro')}</p>
                </div>
                <span className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                  <Shield aria-hidden="true" size={16} /> {t('admin.access_by_role', { role: t(`admin.role_${dashboard.role}`) })}
                </span>
              </header>

              <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label={t('admin.stat_published')} value={dashboard.content.published} icon={CheckCircle2} tone="bg-emerald-50 text-emerald-700" />
                <StatCard label={t('admin.stat_drafts')} value={dashboard.role === 'editor' ? dashboard.my_work?.my_drafts ?? 0 : dashboard.content.drafts} icon={FileText} tone="bg-amber-50 text-amber-700" />
                <StatCard label={t('admin.stat_to_verify')} value={dashboard.content.needs_verification} icon={AlertTriangle} tone="bg-orange-50 text-orange-700" />
                {dashboard.capabilities.moderate_signalements
                  ? <StatCard label={t('admin.stat_pending')} value={dashboard.signalements.pending} icon={Activity} tone="bg-red-50 text-red-700" />
                  : <StatCard label={t('admin.stat_my_published')} value={dashboard.my_work?.my_published ?? 0} icon={CheckCircle2} tone="bg-blue-50 text-blue-700" />}
              </section>

              <section className="mt-10">
                <h2 className="mb-4 text-xl font-bold text-slate-950">{t('admin.shortcuts')}</h2>
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                  <Shortcut href="/admin/demarches" label={t('admin.demarches')} description={t('admin.sc_demarches')} icon={FileText} />
                  <Shortcut href="/admin/signalements" label={t('admin.signalements')} description={t('admin.sc_signalements')} icon={AlertTriangle} />
                  {dashboard.capabilities.manage_categories && <Shortcut href="/admin/categories" label={t('admin.categories')} description={t('admin.sc_categories')} icon={Folder} />}
                  {dashboard.capabilities.view_analytics && <Shortcut href="/admin/analytics" label={t('admin.analytics')} description={t('admin.sc_analytics')} icon={BarChart3} />}
                  {dashboard.capabilities.manage_team && <Shortcut href="/admin/users" label={t('admin.team')} description={t('admin.sc_team')} icon={Users} />}
                </div>
              </section>

              {dashboard.my_work && (
                <section className="mt-10 grid gap-6 lg:grid-cols-2">
                  <div className={card}>
                    <h2 className="text-lg font-bold text-slate-950">{t('admin.my_drafts')}</h2>
                    {dashboard.my_work.drafts.length ? (
                      <ul className="mt-4 divide-y divide-slate-100">
                        {dashboard.my_work.drafts.map((draft) => (
                          <li key={draft.id} className="py-3">
                            <Link className="font-medium text-blue-800 hover:underline" href="/admin/demarches">{draft.title}</Link>
                            <p className="mt-1 text-sm text-slate-500">{t('admin.modified_on', { date: date(draft.updated_at) })}</p>
                          </li>
                        ))}
                      </ul>
                    ) : <p className="mt-3 text-sm text-slate-600">{t('admin.no_drafts')}</p>}
                  </div>
                  <div className={card}>
                    <h2 className="text-lg font-bold text-slate-950">{t('admin.my_updates')}</h2>
                    {dashboard.my_work.recent_updates.length ? (
                      <ul className="mt-4 divide-y divide-slate-100">
                        {dashboard.my_work.recent_updates.map((item) => (
                          <li key={item.id} className="flex justify-between gap-4 py-3">
                            <span className="font-medium text-slate-800">{item.title}</span>
                            <span className="text-sm text-slate-500">{t(`admin.status_${item.status}`)}</span>
                          </li>
                        ))}
                      </ul>
                    ) : <p className="mt-3 text-sm text-slate-600">{t('admin.no_updates')}</p>}
                  </div>
                </section>
              )}

              {dashboard.content.to_verify.length > 0 && (
                <section className={`mt-10 ${card}`}>
                  <h2 className="text-lg font-bold text-slate-950">{t('admin.to_verify')}</h2>
                  <ul className="mt-4 divide-y divide-slate-100">
                    {dashboard.content.to_verify.map((item) => (
                      <li key={item.id} className="flex flex-wrap justify-between gap-2 py-3">
                        <Link href={`/demarches/${item.slug}`} target="_blank" className="font-medium text-blue-800 hover:underline">{item.title}</Link>
                        <span className="text-sm text-slate-500">
                          {item.last_verified_at ? t('admin.verified_on', { date: date(item.last_verified_at) }) : t('admin.never_verified')}
                        </span>
                      </li>
                    ))}
                  </ul>
                </section>
              )}

              {dashboard.validation_queue && (
                <section className={`mt-10 ${card}`}>
                  <h2 className="text-lg font-bold text-slate-950">{t('admin.validation_queue')}</h2>
                  {dashboard.validation_queue.length ? (
                    <ul className="mt-4 divide-y divide-slate-100">
                      {dashboard.validation_queue.map((item) => (
                        <li key={item.id} className="flex flex-wrap justify-between gap-2 py-3">
                          <Link href="/admin/demarches" className="font-medium text-slate-800 hover:text-blue-800">{item.title}</Link>
                          <span className="text-sm text-slate-500">{t('admin.proposed_by', { name: item.author || '—' })}</span>
                        </li>
                      ))}
                    </ul>
                  ) : <p className="mt-3 text-sm text-slate-600">{t('admin.no_validation')}</p>}
                </section>
              )}

              {dashboard.audience && (
                <section className="mt-10 grid gap-6 lg:grid-cols-2">
                  <div className={card}>
                    <h2 className="text-lg font-bold text-slate-950">{t('admin.audience_today')}</h2>
                    <p className="mt-4 text-3xl font-bold text-slate-950">{dashboard.audience.visitors_today}</p>
                    <p className="text-sm text-slate-600">{t('admin.visitors', { n: dashboard.audience.views_today })}</p>
                    <h3 className="mt-6 font-semibold text-slate-800">{t('admin.most_viewed')}</h3>
                    <ul className="mt-2 space-y-2">
                      {dashboard.audience.top_demarches.map((item) => (
                        <li key={item.id} className="flex justify-between gap-4 text-sm">
                          <span className="text-slate-700">{item.title}</span>
                          <span className="font-semibold text-slate-900">{t('admin.views', { n: item.views_count })}</span>
                        </li>
                      ))}
                    </ul>
                  </div>
                  <div className={card}>
                    <h2 className="text-lg font-bold text-slate-950">{t('admin.recent_reports')}</h2>
                    {dashboard.signalements.recent.length ? (
                      <ul className="mt-3 divide-y divide-slate-100">
                        {dashboard.signalements.recent.map((item) => (
                          <li key={item.id} className="py-3">
                            <p className="font-medium text-slate-800">{item.demarche?.title || t('admin.general')}</p>
                            <p className="mt-1 text-sm text-slate-600">{item.excerpt}</p>
                            <p className="mt-1 text-xs text-slate-500">{t(`admin.status_${item.status}`)}</p>
                          </li>
                        ))}
                      </ul>
                    ) : <p className="mt-3 text-sm text-slate-600">{t('admin.no_reports')}</p>}
                  </div>
                </section>
              )}

              {dashboard.team && (
                <section className={`mt-10 ${card}`}>
                  <h2 className="text-lg font-bold text-slate-950">{t('admin.team_title')}</h2>
                  <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <p><strong>{dashboard.team.active}/{dashboard.team.total}</strong><span className="block text-sm text-slate-600">{t('admin.active_accounts')}</span></p>
                    <p><strong>{dashboard.team.by_role.super_admin}</strong><span className="block text-sm text-slate-600">{t('admin.super_admins')}</span></p>
                    <p><strong>{dashboard.team.locked}</strong><span className="block text-sm text-slate-600">{t('admin.locked')}</span></p>
                    <p><strong>{dashboard.team.with_failed_logins}</strong><span className="block text-sm text-slate-600">{t('admin.failed')}</span></p>
                  </div>
                  {!!dashboard.recent_activity?.length && (
                    <div className="mt-6 border-t border-slate-100 pt-5">
                      <h3 className="font-semibold text-slate-800">{t('admin.recent_activity')}</h3>
                      <ul className="mt-2 divide-y divide-slate-100">
                        {dashboard.recent_activity.map((item) => (
                          <li key={item.id} className="py-3">
                            <p className="text-sm text-slate-800">{item.description}</p>
                            <p className="mt-1 text-xs text-slate-500">{item.admin || t('admin.deleted_account')} · {item.created_at ? new Date(item.created_at).toLocaleString(language === 'ar' ? 'ar-TD' : language) : '—'}</p>
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                </section>
              )}
            </>
          )}
        </div>
      </main>
    </div>
  );
}
