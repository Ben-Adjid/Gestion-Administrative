'use client';

import { FormEvent, useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { Globe, ShieldCheck } from 'lucide-react';
import { API_V1_URL } from '@/lib/api';
import { isLanguage, LANGUAGE_LABELS, LANGUAGES } from '@/lib/i18n';
import { useLanguage } from '@/hooks/useLanguage';

export default function AdminLoginPage() {
  const router = useRouter();
  const { language, changeLanguage, t } = useLanguage();
  const [form, setForm] = useState({ email: '', password: '' });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setError('');
    setLoading(true);

    try {
      const response = await fetch(`${API_V1_URL}/admin/auth/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'Accept-Language': language },
        body: JSON.stringify(form),
      });
      const data = await response.json();

      if (!response.ok) {
        const first = data.errors ? Object.values(data.errors as Record<string, string[]>)[0]?.[0] : null;
        setError(first || data.message || t('common.error_generic'));
        return;
      }

      localStorage.setItem('admin_token', data.token);
      localStorage.setItem('admin', JSON.stringify(data.admin));
      router.push('/admin/dashboard');
    } catch {
      setError(t('admin.login_unreachable', { url: API_V1_URL }));
    } finally {
      setLoading(false);
    }
  };

  const input = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200';

  return (
    <main className="flex min-h-screen items-center justify-center bg-gradient-to-br from-blue-800 to-slate-900 p-4">
      <div className="w-full max-w-md rounded-2xl bg-white p-8 shadow-2xl">
        <div className="mb-6 flex items-start justify-between gap-4">
          <div>
            <ShieldCheck aria-hidden="true" className="text-blue-700" size={32} />
            <h1 className="mt-3 text-2xl font-bold text-slate-950">{t('admin.login_title')}</h1>
            <p className="mt-1 text-sm text-slate-600">{t('admin.login_intro')}</p>
          </div>
          <label className="flex items-center gap-1.5 text-slate-500">
            <Globe size={15} aria-hidden="true" />
            <span className="sr-only">{t('nav.choose_language')}</span>
            <select
              value={language}
              onChange={(event) => isLanguage(event.target.value) && changeLanguage(event.target.value)}
              className="rounded-lg border border-slate-200 px-2 py-1.5 text-xs font-semibold text-slate-700"
            >
              {LANGUAGES.map((code) => <option key={code} value={code}>{LANGUAGE_LABELS[code]}</option>)}
            </select>
          </label>
        </div>

        {error && <p role="alert" className="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}

        <form onSubmit={submit} className="space-y-4">
          <div>
            <label htmlFor="email" className="block text-sm font-semibold text-slate-700">{t('admin.login_email')}</label>
            <input id="email" type="email" autoComplete="username" required dir="ltr" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} className={input} />
          </div>
          <div>
            <label htmlFor="password" className="block text-sm font-semibold text-slate-700">{t('auth.password')}</label>
            <input id="password" type="password" autoComplete="current-password" required value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} className={input} />
          </div>
          <button type="submit" disabled={loading} className="w-full rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800 disabled:opacity-50">
            {loading ? t('auth.logging_in') : t('auth.submit_login')}
          </button>
        </form>

        <p className="mt-6 text-center text-sm text-slate-600">
          {t('admin.citizen_link')} <Link href="/auth/login" className="font-medium text-blue-700 hover:underline">{t('admin.citizen_login')}</Link>
        </p>
        <p className="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-center text-xs text-slate-600">{t('admin.security_note')}</p>
      </div>
    </main>
  );
}
