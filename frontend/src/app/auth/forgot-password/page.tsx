'use client';

import { FormEvent, useState } from 'react';
import Link from 'next/link';
import { ApiError, fetchApi } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

export default function ForgotPasswordPage() {
  const { t } = useLanguage();
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setError('');
    setMessage('');
    setLoading(true);
    try {
      await fetchApi('/auth/forgot-password', { method: 'POST', body: JSON.stringify({ email }) });
      setMessage(t('auth.forgot_sent'));
    } catch (e: unknown) {
      setError(e instanceof ApiError ? e.firstError() : t('common.network_error'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="flex min-h-[80vh] items-center justify-center bg-slate-50 px-4 py-12">
      <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 className="text-2xl font-bold text-slate-950">{t('auth.forgot_title')}</h1>
        <p className="mt-1 text-sm text-slate-600">{t('auth.forgot_intro')}</p>

        {message && <p role="status" className="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{message}</p>}
        {error && <p role="alert" className="mt-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}

        <form onSubmit={submit} className="mt-6 space-y-4">
          <div>
            <label htmlFor="email" className="block text-sm font-semibold text-slate-700">{t('auth.email')}</label>
            <input
              id="email"
              type="email"
              autoComplete="email"
              required
              dir="ltr"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
            />
          </div>
          <button type="submit" disabled={loading} className="w-full rounded-lg bg-blue-700 px-5 py-3 font-semibold text-white hover:bg-blue-800 disabled:opacity-50">
            {loading ? t('auth.sending') : t('auth.send_link')}
          </button>
        </form>
        <p className="mt-6 text-center text-sm"><Link href="/auth/login" className="font-semibold text-blue-800 hover:underline">{t('auth.back_to_login')}</Link></p>
      </div>
    </main>
  );
}
