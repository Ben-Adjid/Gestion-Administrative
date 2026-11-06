'use client';

import { FormEvent, useState } from 'react';
import Link from '@/components/AppLink';
import { AlertTriangle, Mail, PlusCircle } from 'lucide-react';
import { API_V1_URL } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

const SUBJECTS = ['general', 'error', 'partnership', 'other'] as const;

/** « Nous contacter » : signalement, suggestion, et formulaire direct à l'équipe (sans exposer d'adresse). */
export default function ContactPage() {
  const { t, language } = useLanguage();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [subject, setSubject] = useState<(typeof SUBJECTS)[number]>('general');
  const [message, setMessage] = useState('');
  const [website, setWebsite] = useState('');
  const [status, setStatus] = useState<'idle' | 'sending' | 'sent' | 'error'>('idle');
  const [error, setError] = useState('');

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setStatus('sending');
    setError('');
    try {
      const res = await fetch(`${API_V1_URL}/contact?lang=${language}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ name, email, subject, message, website }),
      });
      const json = (await res.json().catch(() => ({}))) as { message?: string; errors?: Record<string, string[]> };
      if (!res.ok) {
        setError(json.message || Object.values(json.errors ?? {})[0]?.[0] || t('common.error_generic'));
        setStatus('error');
        return;
      }
      setStatus('sent');
      setName(''); setEmail(''); setMessage('');
    } catch {
      setError(t('common.error_generic'));
      setStatus('error');
    }
  };

  const input = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200';

  return (
    <main className="bg-slate-50">
      <div className="bg-gradient-to-r from-blue-800 via-blue-900 to-slate-900 py-16 text-white">
        <div className="mx-auto max-w-4xl px-4 text-center">
          <h1 className="text-4xl font-bold tracking-tight sm:text-5xl">{t('contact.title')}</h1>
          <p className="mt-4 text-lg text-blue-100">{t('contact.intro')}</p>
        </div>
      </div>

      <div className="mx-auto grid max-w-4xl gap-6 px-4 py-12 sm:grid-cols-2">
        <Link href="/signalement" className="group rounded-2xl border border-slate-200 bg-white p-6 transition hover:border-amber-300 hover:shadow-sm">
          <AlertTriangle aria-hidden="true" className="text-amber-600" size={28} />
          <h2 className="mt-4 text-xl font-bold text-slate-950 group-hover:text-amber-700">{t('contact.wrong_title')}</h2>
          <p className="mt-2 text-slate-600">{t('contact.wrong_text')}</p>
        </Link>

        <Link href="/signalement?type=missing" className="group rounded-2xl border border-slate-200 bg-white p-6 transition hover:border-blue-300 hover:shadow-sm">
          <PlusCircle aria-hidden="true" className="text-blue-700" size={28} />
          <h2 className="mt-4 text-xl font-bold text-slate-950 group-hover:text-blue-800">{t('contact.missing_title')}</h2>
          <p className="mt-2 text-slate-600">{t('contact.missing_text')}</p>
        </Link>

        <section className="rounded-2xl border border-slate-200 bg-white p-6 sm:col-span-2">
          <div className="flex items-center gap-3">
            <Mail aria-hidden="true" className="text-slate-700" size={28} />
            <h2 className="text-xl font-bold text-slate-950">{t('contactx.form_title')}</h2>
          </div>
          <p className="mt-2 text-slate-600">{t('contactx.form_intro')}</p>

          {status === 'sent' ? (
            <p role="status" className="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{t('contactx.sent')}</p>
          ) : (
            <form onSubmit={submit} noValidate className="mt-6 grid gap-4 sm:grid-cols-2">
              <div>
                <label htmlFor="contact-name" className="block text-sm font-semibold text-slate-700">{t('contactx.name')} <span className="font-normal text-slate-500">({t('common.optional')})</span></label>
                <input id="contact-name" value={name} onChange={(e) => setName(e.target.value)} maxLength={120} autoComplete="name" className={input} />
              </div>
              <div>
                <label htmlFor="contact-email" className="block text-sm font-semibold text-slate-700">{t('contactx.email')}</label>
                <input id="contact-email" type="email" required value={email} onChange={(e) => setEmail(e.target.value)} maxLength={150} autoComplete="email" className={input} />
              </div>
              <div className="sm:col-span-2">
                <label htmlFor="contact-subject" className="block text-sm font-semibold text-slate-700">{t('contactx.subject')}</label>
                <select id="contact-subject" value={subject} onChange={(e) => setSubject(e.target.value as (typeof SUBJECTS)[number])} className={input}>
                  {SUBJECTS.map((s) => <option key={s} value={s}>{t(`contactx.subject_${s}`)}</option>)}
                </select>
              </div>
              <div className="sm:col-span-2">
                <label htmlFor="contact-message" className="block text-sm font-semibold text-slate-700">{t('contactx.message')}</label>
                <textarea id="contact-message" required minLength={10} maxLength={2500} rows={5} value={message} onChange={(e) => setMessage(e.target.value)} className={input} />
              </div>
              {/* Champ piège : invisible pour les personnes, rempli seulement par les robots. */}
              <div aria-hidden="true" style={{ position: 'absolute', left: '-10000px' }}>
                <label htmlFor="contact-website">Website</label>
                <input id="contact-website" tabIndex={-1} autoComplete="off" value={website} onChange={(e) => setWebsite(e.target.value)} />
              </div>
              {status === 'error' && <p role="alert" className="sm:col-span-2 text-sm text-red-700">{error}</p>}
              <div className="sm:col-span-2">
                <button type="submit" disabled={status === 'sending'} className="rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800 disabled:opacity-50">
                  {status === 'sending' ? t('contactx.sending') : t('contactx.send')}
                </button>
              </div>
            </form>
          )}
        </section>

        <p className="text-sm text-slate-500 sm:col-span-2">{t('contact.note')}</p>
      </div>
    </main>
  );
}
