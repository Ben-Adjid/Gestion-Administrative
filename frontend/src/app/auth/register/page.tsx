'use client';

import { FormEvent, useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { fetchApi, splitFormErrors } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

const EMPTY = { name: '', email: '', phone: '', password: '', password_confirmation: '', accept_terms: false };

export default function RegisterPage() {
  const router = useRouter();
  const { language, t } = useLanguage();
  const [form, setForm] = useState(EMPTY);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setErrors({});
    setError('');
    if (form.password !== form.password_confirmation) {
      setErrors({ password_confirmation: [t('auth.mismatch')] });
      return;
    }
    setLoading(true);
    try {
      const res = await fetchApi<{ token: string; data: unknown }>('/auth/register', {
        method: 'POST',
        body: JSON.stringify({ ...form, phone: form.phone || null, language }),
      });
      localStorage.setItem('token', res.token);
      localStorage.setItem('user', JSON.stringify(res.data));
      router.push('/dashboard');
    } catch (e: unknown) {
      const { fieldErrors, general } = splitFormErrors(e, ['accept_terms', 'email', 'name', 'password', 'password_confirmation', 'phone'], t('common.network_error'));
      setErrors(fieldErrors);
      if (general) setError(general);
    } finally {
      setLoading(false);
    }
  };

  const input = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200';
  const err = (name: string) => errors[name]?.[0] && <p className="mt-1 text-sm text-red-700">{errors[name][0]}</p>;

  return (
    <main className="flex min-h-[80vh] items-center justify-center bg-slate-50 px-4 py-12">
      <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 className="text-2xl font-bold text-slate-950">{t('auth.register_title')}</h1>
        <p className="mt-1 text-sm text-slate-600">{t('auth.register_intro')}</p>

        {error && <p role="alert" className="mt-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}

        <form onSubmit={submit} noValidate className="mt-6 space-y-4">
          <div>
            <label htmlFor="name" className="block text-sm font-semibold text-slate-700">{t('auth.name')}</label>
            <input id="name" autoComplete="name" required value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className={input} />
            {err('name')}
          </div>
          <div>
            <label htmlFor="email" className="block text-sm font-semibold text-slate-700">{t('auth.email')}</label>
            <input id="email" type="email" autoComplete="email" required dir="ltr" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} className={input} />
            {err('email')}
          </div>
          <div>
            <label htmlFor="phone" className="block text-sm font-semibold text-slate-700">{t('auth.phone')} <span className="font-normal text-slate-500">({t('common.optional')})</span></label>
            <input id="phone" type="tel" autoComplete="tel" dir="ltr" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} className={input} />
            {err('phone')}
          </div>
          <div>
            <label htmlFor="password" className="block text-sm font-semibold text-slate-700">{t('auth.password')}</label>
            <input id="password" type="password" autoComplete="new-password" required value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} className={input} />
            <p className="mt-1 text-xs text-slate-500">{t('auth.password_hint')}</p>
            {err('password')}
          </div>
          <div>
            <label htmlFor="password_confirmation" className="block text-sm font-semibold text-slate-700">{t('auth.confirm_password')}</label>
            <input id="password_confirmation" type="password" autoComplete="new-password" required value={form.password_confirmation} onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })} className={input} />
            {err('password_confirmation')}
          </div>
          <div className="flex items-start gap-2">
            <input id="accept_terms" type="checkbox" checked={form.accept_terms} onChange={(e) => setForm({ ...form, accept_terms: e.target.checked })} className="mt-1 h-4 w-4" />
            <label htmlFor="accept_terms" className="text-sm text-slate-700">{t('auth.consent')} <Link href="/confidentialite" target="_blank" className="font-semibold text-blue-800 underline">{t('nav.privacy')}</Link></label>
          </div>
          {err('accept_terms')}
          <button type="submit" disabled={loading || !form.accept_terms} className="w-full rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50">
            {loading ? t('auth.registering') : t('auth.submit_register')}
          </button>
        </form>

        <p className="mt-6 text-center text-sm text-slate-600">
          {t('auth.have_account')} <Link href="/auth/login" className="font-semibold text-blue-800 hover:underline">{t('auth.login_link')}</Link>
        </p>
      </div>
    </main>
  );
}
