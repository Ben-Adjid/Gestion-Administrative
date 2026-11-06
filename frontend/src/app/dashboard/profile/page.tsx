'use client';

import { FormEvent, useEffect, useState } from 'react';
import CitizenNav from '@/components/CitizenNav';
import { ApiError, fetchApi, splitFormErrors } from '@/lib/api';
import { formatDate, isLanguage, LANGUAGE_LABELS, LANGUAGES } from '@/lib/i18n';
import { useLanguage } from '@/hooks/useLanguage';
import { useConfirm } from '@/components/ConfirmProvider';

interface Profile {
  name: string;
  email: string;
  phone: string | null;
  language: 'fr' | 'en' | 'ar';
  created_at: string | null;
}

export default function ProfilePage() {
  const { language, changeLanguage, t } = useLanguage();
  const [profile, setProfile] = useState<Profile | null>(null);
  const [form, setForm] = useState({ name: '', phone: '', language: 'fr' });
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    fetchApi<{ data: Profile }>('/auth/me')
      .then(({ data }) => {
        setProfile(data);
        setForm({ name: data.name, phone: data.phone || '', language: data.language });
      })
      .catch((e: unknown) => setError(e instanceof Error ? e.message : t('common.error_generic')));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    setErrors({});
    setError('');
    setMessage('');
    try {
      const res = await fetchApi<{ data: Profile }>('/auth/profile', {
        method: 'PUT',
        body: JSON.stringify({ name: form.name, phone: form.phone || null, language: form.language }),
      });
      setProfile(res.data);
      localStorage.setItem('user', JSON.stringify(res.data));
      if (isLanguage(form.language) && form.language !== language) changeLanguage(form.language);
      setMessage(t('profile.updated'));
    } catch (e: unknown) {
      const { fieldErrors, general } = splitFormErrors(e, ['name', 'phone'], t('common.error_generic'));
      setErrors(fieldErrors);
      if (general) setError(general);
    } finally {
      setSaving(false);
    }
  };

  const input = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200';

  return (
    <main className="mx-auto max-w-5xl px-4 py-10">
      <h1 className="text-3xl font-bold tracking-tight text-slate-950">{t('profile.title')}</h1>
      <div className="mt-6"><CitizenNav /></div>

      {message && <p role="status" className="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{message}</p>}
      {error && <p role="alert" className="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">{error}</p>}

      {!profile && !error && <div role="status" aria-label={t('common.loading')} className="h-64 max-w-xl animate-pulse rounded-2xl bg-slate-100" />}

      {profile && (
        <form onSubmit={submit} noValidate className="max-w-xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6">
          <div>
            <label htmlFor="email" className="block text-sm font-semibold text-slate-700">{t('auth.email')}</label>
            <input id="email" value={profile.email} disabled dir="ltr" className={`${input} bg-slate-50 text-slate-500`} />
            <p className="mt-1 text-xs text-slate-500">{t('profile.email_hint')}</p>
          </div>
          <div>
            <label htmlFor="name" className="block text-sm font-semibold text-slate-700">{t('auth.name')}</label>
            <input id="name" required maxLength={255} value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className={input} />
            {errors.name && <p className="mt-1 text-sm text-red-700">{errors.name[0]}</p>}
          </div>
          <div>
            <label htmlFor="phone" className="block text-sm font-semibold text-slate-700">{t('auth.phone')} <span className="font-normal text-slate-500">({t('common.optional')})</span></label>
            <input id="phone" type="tel" dir="ltr" maxLength={20} value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} className={input} />
            {errors.phone && <p className="mt-1 text-sm text-red-700">{errors.phone[0]}</p>}
          </div>
          <div>
            <label htmlFor="language" className="block text-sm font-semibold text-slate-700">{t('profile.language')}</label>
            <select id="language" value={form.language} onChange={(e) => setForm({ ...form, language: e.target.value })} className={input}>
              {LANGUAGES.map((code) => <option key={code} value={code}>{LANGUAGE_LABELS[code]}</option>)}
            </select>
          </div>
          <button type="submit" disabled={saving} className="rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800 disabled:opacity-50">
            {saving ? t('common.saving') : t('common.save')}
          </button>
          {profile.created_at && <p className="text-xs text-slate-500">{t('profile.created_on', { date: formatDate(language, profile.created_at) })}</p>}
        </form>
      )}

      {profile && <DeleteAccount />}
    </main>
  );
}

/** Suppression définitive du compte : mot de passe exigé et confirmation explicite. */
function DeleteAccount() {
  const { t } = useLanguage();
  const confirm = useConfirm();
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [deleting, setDeleting] = useState(false);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    if (!(await confirm({ title: t('profile.delete_confirm'), tone: 'danger' }))) return;
    setDeleting(true);
    setError('');
    try {
      await fetchApi('/auth/account', { method: 'DELETE', body: JSON.stringify({ password }) });
      try {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
      } catch {
        // Stockage indisponible : le jeton est de toute façon révoqué côté serveur.
      }
      window.location.href = '/?compte=supprime';
    } catch (e: unknown) {
      setError(e instanceof ApiError ? e.firstError() : t('common.error_generic'));
      setDeleting(false);
    }
  };

  return (
    <form onSubmit={submit} className="mt-8 max-w-xl space-y-4 rounded-2xl border border-red-200 bg-red-50/40 p-6">
      <h2 className="text-lg font-bold text-red-900">{t('profile.delete_title')}</h2>
      <p className="text-sm text-slate-700">{t('profile.delete_text')}</p>
      {error && <p role="alert" className="rounded-lg border border-red-200 bg-white p-3 text-sm text-red-800">{error}</p>}
      <div>
        <label htmlFor="delete_password" className="block text-sm font-semibold text-slate-700">{t('profile.delete_password')}</label>
        <input id="delete_password" type="password" autoComplete="current-password" required value={password} onChange={(e) => setPassword(e.target.value)} className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base focus:border-red-700 focus:outline-none focus:ring-2 focus:ring-red-200" />
      </div>
      <button type="submit" disabled={deleting || !password} className="rounded-lg bg-red-700 px-5 py-2.5 font-semibold text-white hover:bg-red-800 disabled:opacity-50">
        {deleting ? t('profile.deleting') : t('profile.delete_button')}
      </button>
    </form>
  );
}
