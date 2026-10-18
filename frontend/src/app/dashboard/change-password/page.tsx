'use client';

import { FormEvent, useState } from 'react';
import CitizenNav from '@/components/CitizenNav';
import { fetchApi, splitFormErrors } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

const EMPTY = { current_password: '', password: '', password_confirmation: '' };

export default function ChangePasswordPage() {
  const { t } = useLanguage();
  const [form, setForm] = useState(EMPTY);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [saving, setSaving] = useState(false);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setErrors({});
    setError('');
    setMessage('');
    if (form.password !== form.password_confirmation) {
      setErrors({ password_confirmation: [t('auth.mismatch')] });
      return;
    }
    setSaving(true);
    try {
      await fetchApi('/auth/change-password', { method: 'POST', body: JSON.stringify(form) });
      setMessage(t('password.success'));
      setForm(EMPTY);
    } catch (e: unknown) {
      const { fieldErrors, general } = splitFormErrors(e, ['current_password', 'password', 'password_confirmation'], t('common.error_generic'));
      setErrors(fieldErrors);
      if (general) setError(general);
    } finally {
      setSaving(false);
    }
  };

  const input = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200';
  const field = (name: keyof typeof EMPTY, labelKey: string, autoComplete: string) => (
    <div>
      <label htmlFor={name} className="block text-sm font-semibold text-slate-700">{t(labelKey)}</label>
      <input
        id={name}
        type="password"
        required
        autoComplete={autoComplete}
        value={form[name]}
        onChange={(e) => setForm({ ...form, [name]: e.target.value })}
        className={input}
      />
      {errors[name] && <p className="mt-1 text-sm text-red-700">{errors[name][0]}</p>}
    </div>
  );

  return (
    <main className="mx-auto max-w-5xl px-4 py-10">
      <h1 className="text-3xl font-bold tracking-tight text-slate-950">{t('password.title')}</h1>
      <div className="mt-6"><CitizenNav /></div>

      {message && <p role="status" className="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{message}</p>}
      {error && <p role="alert" className="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">{error}</p>}

      <form onSubmit={submit} noValidate className="max-w-xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6">
        {field('current_password', 'password.current', 'current-password')}
        {field('password', 'password.new', 'new-password')}
        <p className="-mt-3 text-xs text-slate-500">{t('auth.password_hint')}</p>
        {field('password_confirmation', 'password.confirm', 'new-password')}
        <button type="submit" disabled={saving} className="rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800 disabled:opacity-50">
          {saving ? t('password.submitting') : t('password.submit')}
        </button>
      </form>
    </main>
  );
}
