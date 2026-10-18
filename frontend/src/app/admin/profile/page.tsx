'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { BarChart3, KeyRound, Mail, Shield, UserRound } from 'lucide-react';
import { AdminSidebar, isModeratorRole, useCurrentAdmin } from '@/components/AdminSidebar';
import { fetchApi } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

interface EmailPreferences {
  notifications: { signalements: boolean; demarche_updates: boolean; admin_reports: boolean; newsletter: boolean };
  frequency: string;
}

/** Abonnement au rapport mensuel (envoyé aux administrateurs et super administrateurs). */
function MonthlyReportPreference() {
  const { t } = useLanguage();
  const [preferences, setPreferences] = useState<EmailPreferences | null>(null);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  useEffect(() => {
    fetchApi<EmailPreferences>('/admin/email/preferences')
      .then(setPreferences)
      .catch((e: unknown) => setError(e instanceof Error ? e.message : t('common.error_generic')));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const toggle = async (checked: boolean) => {
    if (!preferences) return;
    // Le serveur attend toutes les préférences : on renvoie les autres inchangées.
    const next = { ...preferences, notifications: { ...preferences.notifications, admin_reports: checked } };
    setMessage('');
    setError('');
    try {
      await fetchApi('/admin/email/preferences', { method: 'PUT', body: JSON.stringify(next) });
      setPreferences(next);
      setMessage(t('adm.p_report_saved'));
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : t('common.error_generic'));
    }
  };

  return (
    <section className="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
      <h2 className="flex items-center gap-2 text-lg font-bold text-slate-900"><BarChart3 size={18} aria-hidden="true" /> {t('adm.p_report_title')}</h2>
      {preferences && (
        <label className="mt-4 flex items-start gap-3 text-sm text-slate-700">
          <input type="checkbox" checked={preferences.notifications.admin_reports} onChange={(e) => toggle(e.target.checked)} className="mt-0.5 h-4 w-4" />
          {t('adm.p_report_label')}
        </label>
      )}
      {message && <p role="status" className="mt-3 text-sm text-emerald-700">{message}</p>}
      {error && <p role="alert" className="mt-3 text-sm text-red-700">{error}</p>}
    </section>
  );
}

export default function AdminProfilePage() {
  const { t } = useLanguage();
  const admin = useCurrentAdmin();

  return (
    <div className="flex min-h-screen bg-slate-50">
      <AdminSidebar user={admin} />
      <main className="min-w-0 flex-1 px-4 py-8 sm:px-8">
        <div className="mx-auto max-w-3xl">
          <p className="text-sm font-semibold text-blue-700">{t('adm.kicker')}</p>
          <h1 className="mt-1 text-3xl font-bold tracking-tight text-slate-950">{t('adm.p_title')}</h1>
          <p className="mt-2 text-sm text-slate-600">{t('adm.p_intro')}</p>

          {admin && (
            <section className="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
              <dl className="grid gap-6 sm:grid-cols-2">
                <div>
                  <dt className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500"><UserRound size={15} aria-hidden="true" /> {t('adm.u_name')}</dt>
                  <dd className="mt-2 text-lg font-bold text-slate-900">{admin.name}</dd>
                </div>
                <div>
                  <dt className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500"><Mail size={15} aria-hidden="true" /> {t('adm.u_email')}</dt>
                  <dd className="mt-2 text-lg font-bold text-slate-900" dir="ltr">{admin.email}</dd>
                </div>
                <div className="sm:col-span-2">
                  <dt className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500"><Shield size={15} aria-hidden="true" /> {t('adm.u_role')}</dt>
                  <dd className="mt-2 text-lg font-bold text-slate-900">{t(`admin.role_${admin.role}`)}</dd>
                </div>
              </dl>
              <p className="mt-6 rounded-xl bg-slate-50 p-3 text-sm text-slate-600">{t('adm.p_readonly')}</p>
              <Link href="/admin/change-password" className="mt-6 inline-flex items-center gap-2 rounded-xl bg-blue-700 px-5 py-3 font-bold text-white hover:bg-blue-800">
                <KeyRound size={18} aria-hidden="true" /> {t('adm.p_change_password')}
              </Link>
            </section>
          )}

          {admin && isModeratorRole(admin.role) && <MonthlyReportPreference />}
        </div>
      </main>
    </div>
  );
}
