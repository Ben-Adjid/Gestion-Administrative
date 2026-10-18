'use client';

import { FormEvent, useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { ApiError, fetchApi } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

export default function LoginPage() {
  const router = useRouter();
  const { t } = useLanguage();
  const [form, setForm] = useState({ email: '', password: '' });
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setError('');
    setLoading(true);
    try {
      const res = await fetchApi<{ token: string; data: unknown }>('/auth/login', { method: 'POST', body: JSON.stringify(form) });
      localStorage.setItem('token', res.token);
      localStorage.setItem('user', JSON.stringify(res.data));
      router.push('/dashboard');
    } catch (e: unknown) {
      setError(e instanceof ApiError ? e.firstError() : t('common.network_error'));
    } finally {
      setLoading(false);
    }
  };

  const input = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200';

  return (
    <main className="flex min-h-[80vh] items-center justify-center bg-slate-50 px-4 py-12">
      <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 className="text-2xl font-bold text-slate-950">{t('auth.login_title')}</h1>
        <p className="mt-1 text-sm text-slate-600">{t('auth.login_intro')}</p>

        {error && <p role="alert" className="mt-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}

        <form onSubmit={submit} className="mt-6 space-y-4">
          <div>
            <label htmlFor="email" className="block text-sm font-semibold text-slate-700">{t('auth.email')}</label>
            <input id="email" type="email" autoComplete="email" required dir="ltr" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} className={input} />
          </div>
          <div>
            <div className="flex items-center justify-between">
              <label htmlFor="password" className="block text-sm font-semibold text-slate-700">{t('auth.password')}</label>
              <Link href="/auth/forgot-password" className="text-sm font-medium text-blue-800 hover:underline">{t('auth.forgot')}</Link>
            </div>
            <input id="password" type="password" autoComplete="current-password" required value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} className={input} />
          </div>
          <button type="submit" disabled={loading} className="w-full rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800 disabled:opacity-50">
            {loading ? t('auth.logging_in') : t('auth.submit_login')}
          </button>
        </form>

        <p className="mt-6 text-center text-sm text-slate-600">
          {t('auth.no_account')} <Link href="/auth/register" className="font-semibold text-blue-800 hover:underline">{t('auth.create_account')}</Link>
        </p>
        <p className="mt-2 text-center text-xs text-slate-500">
          {t('auth.team_access')} <Link href="/admin/login" className="underline">{t('auth.team_link')}</Link>
        </p>
      </div>
    </main>
  );
}
