'use client';

import { FormEvent, useCallback, useEffect, useState } from 'react';
import { ArrowDown, ArrowUp, Pencil, Plus, Trash2, X } from 'lucide-react';
import {
  adminInput, adminLabel, AdminModal, AdminNotice, AdminShell, dangerButton, primaryButton, secondaryButton, smallButton, TrilingualField,
} from '@/components/admin/AdminShell';
import { LIFE_EVENT_ICONS, LifeEventIcon } from '@/components/LifeEventIcon';
import { fetchApi, splitFormErrors } from '@/lib/api';
import { useAdminDemarches } from '@/hooks/useAdminDemarches';
import { useLanguage } from '@/hooks/useLanguage';

interface LifeEvent {
  id: number; slug: string; icon: string; display_order: number; is_active: boolean;
  title: string; title_en: string | null; title_ar: string | null;
  description: string | null; description_en: string | null; description_ar: string | null;
  demarche_ids: number[];
}

const EMPTY = { title: { fr: '', en: '', ar: '' }, description: { fr: '', en: '', ar: '' }, icon: 'sparkles', display_order: 0, is_active: true, steps: [] as number[] };
const FIELDS = ['title', 'title_en', 'title_ar', 'description', 'description_en', 'description_ar', 'icon', 'display_order', 'demarche_ids'];

/** Parcours de vie : une situation et ses démarches dans l'ordre. */
export default function LifeEventsAdminPage() {
  const { t } = useLanguage();
  const demarches = useAdminDemarches();
  const [events, setEvents] = useState<LifeEvent[]>([]);
  const [loaded, setLoaded] = useState(false);
  const [editing, setEditing] = useState<LifeEvent | 'new' | null>(null);
  const [form, setForm] = useState(EMPTY);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const load = useCallback(() => {
    fetchApi<{ data: LifeEvent[] }>('/admin/life-events')
      .then((res) => { setEvents(res.data); setLoaded(true); })
      .catch((e: unknown) => { setError(e instanceof Error ? e.message : t('common.error_generic')); setLoaded(true); });
  }, [t]);

  useEffect(load, [load]);

  const titleOf = (id: number) => demarches.find((d) => d.id === id)?.title ?? `#${id}`;

  const open = (event: LifeEvent | 'new') => {
    setErrors({});
    setForm(event === 'new' ? EMPTY : {
      title: { fr: event.title, en: event.title_en || '', ar: event.title_ar || '' },
      description: { fr: event.description || '', en: event.description_en || '', ar: event.description_ar || '' },
      icon: event.icon, display_order: event.display_order, is_active: event.is_active, steps: [...event.demarche_ids],
    });
    setEditing(event);
  };

  const move = (index: number, delta: number) => {
    const steps = [...form.steps];
    const [item] = steps.splice(index, 1);
    steps.splice(index + delta, 0, item);
    setForm({ ...form, steps });
  };

  const save = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setErrors({});
    setError('');
    const body = {
      title: form.title.fr, title_en: form.title.en || null, title_ar: form.title.ar || null,
      description: form.description.fr || null, description_en: form.description.en || null, description_ar: form.description.ar || null,
      icon: form.icon, display_order: Number(form.display_order) || 0, is_active: form.is_active, demarche_ids: form.steps,
    };
    try {
      if (editing === 'new') await fetchApi('/admin/life-events', { method: 'POST', body: JSON.stringify(body) });
      else if (editing) await fetchApi(`/admin/life-events/${editing.id}`, { method: 'PUT', body: JSON.stringify(body) });
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

  const remove = async (event: LifeEvent) => {
    if (!window.confirm(t('adx.confirm_delete', { name: event.title }))) return;
    try {
      await fetchApi(`/admin/life-events/${event.id}`, { method: 'DELETE' });
      setMessage(t('adx.deleted'));
      load();
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : t('common.error_generic'));
    }
  };

  return (
    <AdminShell title={t('adx.j_title')} intro={t('adx.j_intro')} actions={<button type="button" onClick={() => open('new')} className={primaryButton}><Plus size={16} aria-hidden="true" /> {t('adx.j_new')}</button>}>
      <AdminNotice message={message} error={editing ? '' : error} />
      {!loaded && <p role="status" className="text-slate-500">{t('adx.loading')}</p>}
      {loaded && events.length === 0 && <p className="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-slate-600">{t('adx.j_empty')}</p>}
      <ul className="space-y-3">
        {events.map((event) => (
          <li key={event.id} className="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div className="flex items-start gap-3">
              <span className="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-100 text-blue-800"><LifeEventIcon name={event.icon} size={20} /></span>
              <div>
                <p className="font-bold text-slate-900">{event.title} {!event.is_active && <span className="ms-2 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{t('adx.inactive')}</span>}</p>
                <p className="mt-0.5 text-sm text-slate-600">{event.demarche_ids.map(titleOf).join(' → ')}</p>
              </div>
            </div>
            <div className="flex gap-2">
              <button type="button" onClick={() => open(event)} className={smallButton}><Pencil size={14} aria-hidden="true" /> {t('adx.edit')}</button>
              <button type="button" onClick={() => remove(event)} className={dangerButton}><Trash2 size={14} aria-hidden="true" /> {t('adx.delete')}</button>
            </div>
          </li>
        ))}
      </ul>

      {editing && (
        <AdminModal title={editing === 'new' ? t('adx.j_new') : t('adx.edit')} onClose={() => setEditing(null)} wide>
          <form onSubmit={save} noValidate className="space-y-4">
            {error && <p role="alert" className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}
            <TrilingualField labelKey="adx.j_name" required maxLength={150} values={form.title} onChange={(lang, value) => setForm({ ...form, title: { ...form.title, [lang]: value } })} />
            {errors.title && <p className="text-sm text-red-700">{errors.title[0]}</p>}
            <TrilingualField labelKey="adx.j_desc" multiline maxLength={1000} values={form.description} onChange={(lang, value) => setForm({ ...form, description: { ...form.description, [lang]: value } })} />

            <fieldset>
              <legend className={adminLabel}>{t('adx.j_steps')} *</legend>
              <ol className="mt-2 space-y-2">
                {form.steps.map((id, index) => (
                  <li key={id} className="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                    <span className="font-bold text-slate-500">{index + 1}.</span>
                    <span className="flex-1 font-semibold text-slate-800">{titleOf(id)}</span>
                    <button type="button" disabled={index === 0} onClick={() => move(index, -1)} aria-label={t('adx.j_up')} className="rounded p-1 hover:bg-white disabled:opacity-30"><ArrowUp size={15} aria-hidden="true" /></button>
                    <button type="button" disabled={index === form.steps.length - 1} onClick={() => move(index, 1)} aria-label={t('adx.j_down')} className="rounded p-1 hover:bg-white disabled:opacity-30"><ArrowDown size={15} aria-hidden="true" /></button>
                    <button type="button" onClick={() => setForm({ ...form, steps: form.steps.filter((s) => s !== id) })} aria-label={t('adx.j_remove')} className="rounded p-1 text-red-600 hover:bg-white"><X size={15} aria-hidden="true" /></button>
                  </li>
                ))}
              </ol>
              <label className="mt-2 block text-sm">
                <span className="sr-only">{t('adx.j_add_step')}</span>
                <select value="" onChange={(e) => e.target.value && setForm({ ...form, steps: [...form.steps, Number(e.target.value)] })} className={adminInput}>
                  <option value="">{t('adx.j_choose')}</option>
                  {demarches.filter((d) => !form.steps.includes(d.id)).map((d) => <option key={d.id} value={d.id}>{d.title}</option>)}
                </select>
              </label>
              {errors.demarche_ids && <p className="mt-1 text-sm text-red-700">{errors.demarche_ids[0]}</p>}
            </fieldset>

            <div className="grid gap-3 sm:grid-cols-2">
              <fieldset>
                <legend className={adminLabel}>{t('adx.j_icon')}</legend>
                <div className="mt-1 flex flex-wrap gap-1.5">
                  {LIFE_EVENT_ICONS.map((icon) => (
                    <button key={icon} type="button" aria-pressed={form.icon === icon} aria-label={icon} onClick={() => setForm({ ...form, icon })} className={`grid h-9 w-9 place-items-center rounded-lg border ${form.icon === icon ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50'}`}>
                      <LifeEventIcon name={icon} size={18} />
                    </button>
                  ))}
                </div>
              </fieldset>
              <label className={adminLabel}>{t('adx.j_order')}
                <input type="number" min={0} max={1000} value={form.display_order} onChange={(e) => setForm({ ...form, display_order: Number(e.target.value) })} className={adminInput} />
              </label>
            </div>
            <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
              <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} className="h-4 w-4" /> {t('adx.visible')}
            </label>
            <div className="flex justify-end gap-2">
              <button type="button" onClick={() => setEditing(null)} className={secondaryButton}>{t('adx.cancel')}</button>
              <button type="submit" disabled={saving || form.steps.length === 0 || !form.title.fr.trim()} className={primaryButton}>{t('adx.save')}</button>
            </div>
          </form>
        </AdminModal>
      )}
    </AdminShell>
  );
}
