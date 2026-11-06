'use client';

import { FormEvent, useCallback, useEffect, useState } from 'react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import {
  adminInput, adminLabel, AdminModal, AdminNotice, AdminShell, dangerButton, primaryButton, secondaryButton, smallButton, TrilingualField,
} from '@/components/admin/AdminShell';
import { fetchApi, splitFormErrors } from '@/lib/api';
import { formatDate } from '@/lib/i18n';
import { useAdminDemarches } from '@/hooks/useAdminDemarches';
import { useLanguage } from '@/hooks/useLanguage';
import { useConfirm } from '@/components/ConfirmProvider';

const CATEGORIES = ['elections', 'education', 'exams', 'identity', 'tax', 'other'] as const;
const KINDS = ['period', 'deadline', 'event'] as const;

interface CalendarEvent {
  id: number;
  title: string; title_en: string | null; title_ar: string | null;
  description: string | null; description_en: string | null; description_ar: string | null;
  category: (typeof CATEGORIES)[number];
  kind: (typeof KINDS)[number];
  starts_on: string; ends_on: string | null;
  demarche_id: number | null; source_url: string | null; is_published: boolean;
}

const EMPTY = {
  title: { fr: '', en: '', ar: '' }, description: { fr: '', en: '', ar: '' },
  category: 'other' as CalendarEvent['category'], kind: 'event' as CalendarEvent['kind'], starts_on: '', ends_on: '', demarche_id: '', source_url: '', is_published: true,
};
const FIELDS = ['kind', 'title', 'title_en', 'title_ar', 'description', 'description_en', 'description_ar', 'category', 'starts_on', 'ends_on', 'demarche_id', 'source_url'];

/** Calendrier administratif : dates publiées sur le site. */
export default function CalendarAdminPage() {
  const { language, t } = useLanguage();
  const confirm = useConfirm();
  const demarches = useAdminDemarches();
  const [events, setEvents] = useState<CalendarEvent[]>([]);
  const [loaded, setLoaded] = useState(false);
  const [editing, setEditing] = useState<CalendarEvent | 'new' | null>(null);
  const [form, setForm] = useState(EMPTY);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const load = useCallback(() => {
    fetchApi<{ data: CalendarEvent[] }>('/admin/calendar')
      .then((res) => { setEvents(res.data); setLoaded(true); })
      .catch((e: unknown) => { setError(e instanceof Error ? e.message : t('common.error_generic')); setLoaded(true); });
  }, [t]);

  useEffect(load, [load]);

  const open = (event: CalendarEvent | 'new') => {
    setErrors({});
    setForm(event === 'new' ? EMPTY : {
      title: { fr: event.title, en: event.title_en || '', ar: event.title_ar || '' },
      description: { fr: event.description || '', en: event.description_en || '', ar: event.description_ar || '' },
      category: event.category, kind: event.kind, starts_on: event.starts_on.slice(0, 10), ends_on: event.ends_on?.slice(0, 10) || '',
      demarche_id: event.demarche_id ? String(event.demarche_id) : '', source_url: event.source_url || '', is_published: event.is_published,
    });
    setEditing(event);
  };

  const save = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setErrors({});
    setError('');
    const body = {
      title: form.title.fr, title_en: form.title.en || null, title_ar: form.title.ar || null,
      description: form.description.fr || null, description_en: form.description.en || null, description_ar: form.description.ar || null,
      category: form.category, kind: form.kind, starts_on: form.starts_on, ends_on: form.kind === 'period' ? form.ends_on || null : null,
      demarche_id: form.demarche_id ? Number(form.demarche_id) : null, source_url: form.source_url || null, is_published: form.is_published,
    };
    try {
      if (editing === 'new') await fetchApi('/admin/calendar', { method: 'POST', body: JSON.stringify(body) });
      else if (editing) await fetchApi(`/admin/calendar/${editing.id}`, { method: 'PUT', body: JSON.stringify(body) });
      setEditing(null);
      setMessage(t('adx.saved'));
      load();
    } catch (err: unknown) {
      const { fieldErrors, general } = splitFormErrors(err, FIELDS, t('common.error_generic'));
      setErrors(fieldErrors);
      if (general) setError(general);
    } finally {
      setSaving(false);
    }
  };

  const remove = async (event: CalendarEvent) => {
    if (!(await confirm({ title: t('adx.confirm_delete', { name: event.title }), tone: 'danger' }))) return;
    try {
      await fetchApi(`/admin/calendar/${event.id}`, { method: 'DELETE' });
      setMessage(t('adx.deleted'));
      load();
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : t('common.error_generic'));
    }
  };

  const today = new Date().toISOString().slice(0, 10);
  const err = (name: string) => errors[name]?.[0] && <p className="mt-1 text-sm text-red-700">{errors[name][0]}</p>;

  return (
    <AdminShell title={t('adx.e_title')} intro={t('adx.e_intro')} actions={<button type="button" onClick={() => open('new')} className={primaryButton}><Plus size={16} aria-hidden="true" /> {t('adx.e_new')}</button>}>
      <AdminNotice message={message} error={editing ? '' : error} />
      {!loaded && <p role="status" className="text-slate-500">{t('adx.loading')}</p>}
      {loaded && events.length === 0 && <p className="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-slate-600">{t('adx.e_empty')}</p>}
      <ul className="space-y-3">
        {events.map((event) => {
          const past = (event.ends_on ?? event.starts_on).slice(0, 10) < today;
          return (
            <li key={event.id} className={`flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between ${past ? 'opacity-60' : ''}`}>
              <div>
                <p className="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
                  <span className="rounded-full bg-slate-100 px-2 py-0.5 text-slate-700">{t(`calendar.cat_${event.category}`)}</span>
                  {!event.is_published && <span className="rounded-full bg-amber-100 px-2 py-0.5 text-amber-800">{t('adx.e_draft')}</span>}
                  {past && <span className="rounded-full bg-slate-200 px-2 py-0.5">{t('adx.e_past')}</span>}
                  {event.ends_on && event.ends_on !== event.starts_on
                    ? t('calendar.from_to', { start: formatDate(language, event.starts_on), end: formatDate(language, event.ends_on) })
                    : formatDate(language, event.starts_on)}
                </p>
                <p className="mt-1 font-bold text-slate-900">{event.title}</p>
                <p className="text-xs font-semibold text-slate-500">{t(`calendar.kind_${event.kind}`)}</p>
              </div>
              <div className="flex gap-2">
                <button type="button" onClick={() => open(event)} className={smallButton}><Pencil size={14} aria-hidden="true" /> {t('adx.edit')}</button>
                <button type="button" onClick={() => remove(event)} className={dangerButton}><Trash2 size={14} aria-hidden="true" /> {t('adx.delete')}</button>
              </div>
            </li>
          );
        })}
      </ul>

      {editing && (
        <AdminModal title={editing === 'new' ? t('adx.e_new') : t('adx.edit')} onClose={() => setEditing(null)} wide>
          <form onSubmit={save} noValidate className="space-y-4">
            {error && <p role="alert" className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}
            <TrilingualField labelKey="adx.e_name" required maxLength={200} values={form.title} onChange={(lang, value) => setForm({ ...form, title: { ...form.title, [lang]: value } })} />
            {err('title')}
            <TrilingualField labelKey="adx.e_desc" multiline maxLength={2000} values={form.description} onChange={(lang, value) => setForm({ ...form, description: { ...form.description, [lang]: value } })} />
            <fieldset>
              <legend className={adminLabel}>{t('adx.e_kind')} *</legend>
              <div className="mt-1 flex flex-wrap gap-2">
                {KINDS.map((k) => (
                  <label key={k} className={`cursor-pointer rounded-lg border px-3 py-1.5 text-sm font-semibold ${form.kind === k ? 'border-blue-600 bg-blue-50 text-blue-800' : 'border-slate-300 text-slate-700'}`}>
                    <input type="radio" name="kind" value={k} checked={form.kind === k} onChange={() => setForm({ ...form, kind: k, ends_on: k === 'period' ? form.ends_on : '' })} className="sr-only" />
                    {t(`calendar.kind_${k}`)}
                  </label>
                ))}
              </div>
              <p className="mt-1 text-xs text-slate-500">{t('adx.e_kind_hint')}</p>
              {err('kind')}
            </fieldset>
            <div className="grid gap-3 sm:grid-cols-3">
              <label className={adminLabel}>{t('adx.e_category')}
                <select value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value as CalendarEvent['category'] })} className={adminInput}>
                  {CATEGORIES.map((c) => <option key={c} value={c}>{t(`calendar.cat_${c}`)}</option>)}
                </select>
              </label>
              <label className={adminLabel}>{form.kind === 'period' ? t('adx.e_starts') : form.kind === 'deadline' ? t('calendar.kind_deadline') : t('adx.e_date')} *
                <input type="date" required value={form.starts_on} onChange={(e) => setForm({ ...form, starts_on: e.target.value })} className={adminInput} />
                {err('starts_on')}
              </label>
              {form.kind === 'period' && (
                <label className={adminLabel}>{t('adx.e_end')} *
                  <input type="date" required min={form.starts_on || undefined} value={form.ends_on} onChange={(e) => setForm({ ...form, ends_on: e.target.value })} className={adminInput} />
                  {err('ends_on')}
                </label>
              )}
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
              <label className={adminLabel}>{t('adx.e_demarche')}
                <select value={form.demarche_id} onChange={(e) => setForm({ ...form, demarche_id: e.target.value })} className={adminInput}>
                  <option value="">{t('adx.none')}</option>
                  {demarches.map((d) => <option key={d.id} value={d.id}>{d.title}</option>)}
                </select>
              </label>
              <label className={adminLabel}>{t('adx.e_source')}
                <input type="url" dir="ltr" value={form.source_url} onChange={(e) => setForm({ ...form, source_url: e.target.value })} className={adminInput} />
                {err('source_url')}
              </label>
            </div>
            <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
              <input type="checkbox" checked={form.is_published} onChange={(e) => setForm({ ...form, is_published: e.target.checked })} className="h-4 w-4" /> {t('adx.e_published')}
            </label>
            <div className="flex justify-end gap-2">
              <button type="button" onClick={() => setEditing(null)} className={secondaryButton}>{t('adx.cancel')}</button>
              <button type="submit" disabled={saving} className={primaryButton}>{t('adx.save')}</button>
            </div>
          </form>
        </AdminModal>
      )}
    </AdminShell>
  );
}
