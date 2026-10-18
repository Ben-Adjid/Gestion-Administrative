'use client';

import { FormEvent, Suspense, useState } from 'react';
import Link from 'next/link';
import { useSearchParams } from 'next/navigation';
import { fetchApi, splitFormErrors } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

function ResetPasswordForm() {
  const { t } = useLanguage();
  const params = useSearchParams();
  const token = params.get('token') || '';
  const email = params.get('email') || '';
  const [form, setForm] = useState({ password: '', password_confirmation: '' });
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  if (!token || !email) {
    return (
      <p role="alert" className="mt-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
        {t('auth.reset_incomplete')} <Link href="/auth/forgot-password" className="font-semibold underline">{t('auth.request_new')}</Link>
      </p>
    );
  }

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
      await fetchApi('/auth/reset-password', {
        method: 'POST',
        body: JSON.stringify({ ...form, token, email }),
      });
      setMessage(t('auth.reset_done'));
    } catch (e: unknown) {
      const { fieldErrors, general } = splitFormErrors(e, ['password', 'password_confirmation'], t('common.error_generic'));
      setErrors(fieldErrors);
      if (general) setError(general);
    } finally {
      setLoading(false);
    }
  };

  if (message) {
    return (
      <div className="mt-5 space-y-4">
        <p role="status" className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{message}</p>
        <Link href="/auth/login" className="block w-full rounded-lg bg-blue-700 px-5 py-3 text-center font-semibold text-white hover:bg-blue-800">{t('auth.login_link')}</Link>
      </div>
    );
  }

  const input = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200';

  return (
    <>
      {error && <p role="alert" className="mt-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}
      <form onSubmit={submit} noValidate className="mt-6 space-y-4">
        <p className="text-sm text-slate-600">{t('auth.account')} <strong dir="ltr">{email}</strong></p>
        <div>
          <label htmlFor="password" className="block text-sm font-semibold text-slate-700">{t('auth.new_password')}</label>
          <input id="password" type="password" autoComplete="new-password" required value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} className={input} />
          <p className="mt-1 text-xs text-slate-500">{t('auth.password_hint')}</p>
          {errors.password && <p className="mt-1 text-sm text-red-700">{errors.password[0]}</p>}
        </div>
        <div>
          <label htmlFor="password_confirmation" className="block text-sm font-semibold text-slate-700">{t('auth.confirm')}</label>
          <input id="password_confirmation" type="password" autoComplete="new-password" required value={form.password_confirmation} onChange={(e) => setForm({ ...form, password_confirmation: e.target.value })} className={input} />
          {errors.password_confirmation && <p className="mt-1 text-sm text-red-700">{errors.password_confirmation[0]}</p>}
        </div>
        <button type="submit" disabled={loading} className="w-full rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800 disabled:opacity-50">
          {loading ? t('common.saving') : t('auth.set_password')}
        </button>
      </form>
    </>
  );
}

function Title() {
  const { t } = useLanguage();
  return <h1 className="text-2xl font-bold text-slate-950">{t('auth.reset_title')}</h1>;
}

export default function ResetPasswordPage() {
  return (
    <main className="flex min-h-[80vh] items-center justify-center bg-slate-50 px-4 py-12">
      <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <Title />
        <Suspense fallback={<p role="status" className="mt-5 text-slate-600">…</p>}>
          <ResetPasswordForm />
        </Suspense>
      </div>
    </main>
  );
}
