'use client';

import { FormEvent, Suspense, useState } from 'react';
import Link from '@/components/AppLink';
import { useSearchParams } from 'next/navigation';
import { AlertCircle, CheckCircle2, Send } from 'lucide-react';
import { fetchApi, splitFormErrors } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

const TYPES = ['cout_different', 'info_erronee', 'demarche_obsolete', 'lien_mort', 'demarche_manquante', 'autre'] as const;

function SignalementContent() {
  const { t } = useLanguage();
  const searchParams = useSearchParams();
  const demarcheId = searchParams.get('demarche_id') || '';
  const demarcheTitle = searchParams.get('title') || '';

  const [form, setForm] = useState({
    type: searchParams.get('type') === 'missing' ? 'demarche_manquante' : 'cout_different',
    author_name: '',
    author_email: '',
    message: '',
  });
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [status, setStatus] = useState<'idle' | 'submitting' | 'success'>('idle');
  const [error, setError] = useState('');

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setStatus('submitting');
    setError('');
    setErrors({});
    try {
      await fetchApi('/signalements', {
        method: 'POST',
        body: JSON.stringify({ ...form, demarche_id: demarcheId ? Number(demarcheId) : null }),
      });
      setStatus('success');
    } catch (e: unknown) {
      const { fieldErrors, general } = splitFormErrors(e, ['author_email', 'message'], t('common.error_generic'));
      setErrors(fieldErrors);
      if (general) setError(general);
      setStatus('idle');
    }
  };

  const input = 'w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200';

  return (
    <main className="mx-auto max-w-2xl px-4 py-12 sm:px-6">
      <div className="mb-8 text-center">
        <h1 className="text-3xl font-bold text-slate-950">{t('signal.title')}</h1>
        <p className="mt-2 text-slate-600">{t('signal.intro')}</p>
      </div>

      {status === 'success' ? (
        <div role="status" className="space-y-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-8 text-center">
          <CheckCircle2 aria-hidden="true" className="mx-auto h-12 w-12 text-emerald-600" />
          <h2 className="text-xl font-bold text-emerald-900">{t('signal.success_title')}</h2>
          <p className="text-emerald-800">{t('signal.success_text')}</p>
          <Link href="/" className="mt-4 inline-block rounded-xl bg-blue-800 px-6 py-2.5 font-semibold text-white hover:bg-blue-900">
            {t('common.back_home')}
          </Link>
        </div>
      ) : (
        <form onSubmit={submit} noValidate className="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
          {error && (
            <div role="alert" className="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
              <AlertCircle aria-hidden="true" className="mt-0.5 h-5 w-5 shrink-0" /> <span>{error}</span>
            </div>
          )}

          {demarcheTitle && (
            <p className="rounded-lg bg-blue-50 p-3 text-sm text-blue-900">
              {t('signal.concerned')} <strong>{demarcheTitle}</strong>
            </p>
          )}

          <div>
            <label htmlFor="type" className="mb-1 block text-sm font-semibold text-slate-700">{t('signal.reason')} <span className="text-red-600">*</span></label>
            <select id="type" value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })} className={input}>
              {TYPES.map((type) => <option key={type} value={type}>{t(`signal.type_${type}`)}</option>)}
            </select>
          </div>

          <div>
            <label htmlFor="message" className="mb-1 block text-sm font-semibold text-slate-700">{t('signal.description')} <span className="text-red-600">*</span></label>
            <textarea
              id="message"
              rows={5}
              value={form.message}
              onChange={(e) => setForm({ ...form, message: e.target.value })}
              placeholder={t('signal.description_ph')}
              minLength={10}
              maxLength={2500}
              required
              aria-describedby="message-hint"
              className={input}
            />
            {form.message.trim().length < 10 && (
              <p id="message-hint" className="mt-1 text-xs text-slate-500">{t('signal.min_chars', { n: form.message.trim().length })}</p>
            )}
            {errors.message && <p className="mt-1 text-sm text-red-700">{errors.message[0]}</p>}
          </div>

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label htmlFor="author_name" className="mb-1 block text-sm font-medium text-slate-700">{t('signal.name')} <span className="font-normal text-slate-500">({t('common.optional')})</span></label>
              <input id="author_name" value={form.author_name} onChange={(e) => setForm({ ...form, author_name: e.target.value })} className={input} maxLength={100} />
            </div>
            <div>
              <label htmlFor="author_email" className="mb-1 block text-sm font-medium text-slate-700">{t('signal.email')} <span className="font-normal text-slate-500">({t('common.optional')})</span></label>
              <input id="author_email" type="email" value={form.author_email} onChange={(e) => setForm({ ...form, author_email: e.target.value })} className={input} />
              <p className="mt-1 text-xs text-slate-500">{t('signal.email_hint')}</p>
              {errors.author_email && <p className="mt-1 text-sm text-red-700">{errors.author_email[0]}</p>}
            </div>
          </div>

          <div className="pt-2">
            <button
              type="submit"
              disabled={status === 'submitting' || form.message.trim().length < 10}
              className="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-800 py-3 font-bold text-white shadow-sm transition hover:bg-blue-900 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {status === 'submitting' ? t('signal.submitting') : <><Send aria-hidden="true" className="h-4 w-4 rtl:-scale-x-100" /> {t('signal.submit')}</>}
            </button>
            <p className="mt-3 text-center text-xs text-slate-500">{t('signal.rate_limit')}</p>
          </div>
        </form>
      )}
    </main>
  );
}

export default function SignalementPage() {
  return (
    <Suspense fallback={<p role="status" className="py-12 text-center text-slate-600">…</p>}>
      <SignalementContent />
    </Suspense>
  );
}
