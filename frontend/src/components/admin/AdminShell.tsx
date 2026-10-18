'use client';

import { ReactNode, useEffect } from 'react';
import { X } from 'lucide-react';
import { AdminSidebar, useCurrentAdmin } from '@/components/AdminSidebar';
import { useLanguage } from '@/hooks/useLanguage';

export const adminInput = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200';
export const adminLabel = 'block text-sm font-semibold text-slate-700';
export const primaryButton = 'inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-50';
export const secondaryButton = 'inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50';
export const dangerButton = 'inline-flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:opacity-50';
export const smallButton = 'inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100 disabled:opacity-50';

/** Mise en page commune des pages d'administration. */
export function AdminShell({ title, intro, actions, children }: { title: string; intro?: string; actions?: ReactNode; children: ReactNode }) {
  const admin = useCurrentAdmin();
  const { t } = useLanguage();

  return (
    <div className="flex min-h-screen bg-slate-50">
      <AdminSidebar user={admin} />
      <main className="min-w-0 flex-1 px-4 py-8 sm:px-8">
        <div className="mx-auto max-w-6xl">
          <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
              <p className="text-sm font-semibold text-blue-700">{t('adm.kicker')}</p>
              <h1 className="mt-1 text-3xl font-bold tracking-tight text-slate-950">{title}</h1>
              {intro && <p className="mt-2 max-w-3xl text-sm text-slate-600">{intro}</p>}
            </div>
            {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
          </div>
          <div className="mt-6">{children}</div>
        </div>
      </main>
    </div>
  );
}

/** Message de réussite ou d'erreur en haut de page. */
export function AdminNotice({ message, error }: { message?: string; error?: string }) {
  if (!message && !error) return null;
  return error
    ? <p role="alert" className="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>
    : <p role="status" className="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{message}</p>;
}

/** Fenêtre de saisie (Échap ou clic hors de la fenêtre pour fermer). */
export function AdminModal({ title, onClose, children, wide = false }: { title: string; onClose: () => void; children: ReactNode; wide?: boolean }) {
  const { t } = useLanguage();

  useEffect(() => {
    const onKey = (event: KeyboardEvent) => event.key === 'Escape' && onClose();
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [onClose]);

  return (
    <div className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-950/50 p-4 sm:items-center" role="dialog" aria-modal="true" aria-label={title}>
      <button type="button" aria-label={t('adx.cancel')} onClick={onClose} className="fixed inset-0 cursor-default" tabIndex={-1} />
      <div className={`relative w-full ${wide ? 'max-w-3xl' : 'max-w-xl'} rounded-2xl bg-white p-6 shadow-xl`}>
        <div className="mb-4 flex items-start justify-between gap-4">
          <h2 className="text-xl font-bold text-slate-950">{title}</h2>
          <button type="button" onClick={onClose} aria-label={t('adx.cancel')} className="rounded-lg p-1 text-slate-500 hover:bg-slate-100"><X size={20} aria-hidden="true" /></button>
        </div>
        {children}
      </div>
    </div>
  );
}

/** Champ texte dans les trois langues (français obligatoire selon `required`). */
export function TrilingualField({
  labelKey, values, onChange, multiline = false, required = false, maxLength,
}: {
  labelKey: string;
  values: { fr: string; en: string; ar: string };
  onChange: (lang: 'fr' | 'en' | 'ar', value: string) => void;
  multiline?: boolean;
  required?: boolean;
  maxLength?: number;
}) {
  const { t } = useLanguage();

  return (
    <div className="grid gap-3 sm:grid-cols-3">
      {(['fr', 'en', 'ar'] as const).map((lang) => (
        <label key={lang} className={adminLabel}>
          {t(labelKey, { lang: t(`adx.${lang}`) })}{required && lang === 'fr' && ' *'}
          {multiline ? (
            <textarea rows={3} maxLength={maxLength} dir={lang === 'ar' ? 'rtl' : 'ltr'} lang={lang} required={required && lang === 'fr'} value={values[lang]} onChange={(e) => onChange(lang, e.target.value)} className={adminInput} />
          ) : (
            <input maxLength={maxLength} dir={lang === 'ar' ? 'rtl' : 'ltr'} lang={lang} required={required && lang === 'fr'} value={values[lang]} onChange={(e) => onChange(lang, e.target.value)} className={adminInput} />
          )}
        </label>
      ))}
    </div>
  );
}
