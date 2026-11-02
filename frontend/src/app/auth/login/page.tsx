'use client';

import { FormEvent, useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { ArrowRight, ShieldCheck } from 'lucide-react';
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

  const input = 'mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-base transition focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-100';

  return (
    <main className="relative isolate flex min-h-[72vh] items-center justify-center overflow-hidden bg-slate-950 px-4 py-12">
      <div aria-hidden="true" className="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_15%_20%,rgba(30,64,175,0.55),transparent_42%),radial-gradient(ellipse_at_90%_85%,rgba(217,119,6,0.18),transparent_38%)]" />
      <div className="w-full max-w-lg overflow-hidden rounded-[2rem] border border-white/10 bg-white p-7 shadow-2xl shadow-black/25 sm:p-10">
        <Link href="/" className="mb-8 inline-flex items-center gap-3" aria-label="DemarchesTchad">
          <span className="grid h-11 w-11 place-items-center rounded-2xl bg-blue-900 text-sm font-extrabold text-white shadow-lg shadow-blue-900/20" dir="ltr">DT</span>
          <span className="text-lg font-bold text-slate-900" dir="ltr">Demarches<span className="text-blue-800">Tchad</span></span>
        </Link>
        <p className="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-blue-800">
          <ShieldCheck size={15} aria-hidden="true" /> {t('home.badge')}
        </p>
        <h1 className="text-3xl font-bold tracking-tight text-slate-950">{t('auth.login_title')}</h1>
        <p className="mt-2 text-sm leading-6 text-slate-600">{t('auth.login_intro')}</p>

        {error && <p role="alert" className="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{error}</p>}

        <form onSubmit={submit} className="mt-7 space-y-5">
          <div>
            <label htmlFor="email" className="block text-sm font-semibold text-slate-700">{t('auth.email')}</label>
            <input id="email" type="email" autoComplete="email" required dir="ltr" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} className={input} />
          </div>
          <div>
            <div className="flex items-center justify-between">
              <label htmlFor="password" className="block text-sm font-semibold text-slate-700">{t('auth.password')}</label>
              <Link href="/auth/forgot-password" className="text-sm font-semibold text-blue-800 hover:underline">{t('auth.forgot')}</Link>
            </div>
            <input id="password" type="password" autoComplete="current-password" required value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} className={input} />
          </div>
          <button type="submit" disabled={loading} className="group flex w-full items-center justify-center gap-2 rounded-xl bg-blue-800 px-5 py-3.5 font-semibold text-white shadow-lg shadow-blue-900/15 transition hover:-translate-y-0.5 hover:bg-blue-900 disabled:translate-y-0 disabled:opacity-50">
            {loading ? t('auth.logging_in') : t('auth.submit_login')}
            {!loading && <ArrowRight size={17} aria-hidden="true" className="transition-transform group-hover:translate-x-1 rtl:-scale-x-100 rtl:group-hover:-translate-x-1" />}
          </button>
        </form>

        <p className="mt-7 text-center text-sm text-slate-600">
          {t('auth.no_account')} <Link href="/auth/register" className="font-semibold text-blue-800 hover:underline">{t('auth.create_account')}</Link>
        </p>
        <p className="mt-2 text-center text-xs text-slate-500">
          {t('auth.team_access')} <Link href="/admin/login" className="underline">{t('auth.team_link')}</Link>
        </p>
      </div>
    </main>
  );
}
