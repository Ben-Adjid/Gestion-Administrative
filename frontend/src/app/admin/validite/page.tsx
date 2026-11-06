'use client';

import { FormEvent, useCallback, useEffect, useState } from 'react';
import { Pencil, Plus, Trash2, X } from 'lucide-react';
import { adminInput, adminLabel, AdminNotice, AdminShell, dangerButton, primaryButton, secondaryButton, smallButton } from '@/components/admin/AdminShell';
import { fetchApi, splitFormErrors } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';
import { useConfirm } from '@/components/ConfirmProvider';

const CATEGORIES = ['identity', 'travel', 'transport', 'civil_status', 'justice', 'other'] as const;
const STARTS = ['issue_date', 'not_applicable', 'unknown'] as const;
const STATUSES = ['confirmed', 'secondary', 'to_verify'] as const;
const LANGS = ['fr', 'en', 'ar'] as const;

interface Rule {
  id: number; key: string; name: string; name_en: string | null; name_ar: string | null;
  category: (typeof CATEGORIES)[number]; demarche_id: number | null;
  validity_months: number | null; validity_start: (typeof STARTS)[number];
  renewal: string | null; renewal_en: string | null; renewal_ar: string | null;
  notes: string | null; notes_en: string | null; notes_ar: string | null;
  status: (typeof STATUSES)[number]; source_label: string | null; source_url: string | null;
  source_date: string | null; verified_on: string | null; position: number; is_active: boolean;
}

type Form = {
  key: string; name: Record<(typeof LANGS)[number], string>; category: Rule['category']; demarche_id: string;
  validity_months: string; validity_start: Rule['validity_start']; renewal: Record<(typeof LANGS)[number], string>;
  notes: Record<(typeof LANGS)[number], string>; status: Rule['status']; source_label: string; source_url: string;
  source_date: string; verified_on: string; position: string; is_active: boolean;
};

const empty = (): Form => ({
  key: '', name: { fr: '', en: '', ar: '' }, category: 'identity', demarche_id: '', validity_months: '', validity_start: 'issue_date',
  renewal: { fr: '', en: '', ar: '' }, notes: { fr: '', en: '', ar: '' }, status: 'to_verify', source_label: '', source_url: '',
  source_date: '', verified_on: '', position: '0', is_active: true,
});

const FIELDS = ['key', 'name', 'name_en', 'name_ar', 'category', 'demarche_id', 'validity_months', 'validity_start', 'renewal', 'renewal_en', 'renewal_ar',
  'notes', 'notes_en', 'notes_ar', 'status', 'source_label', 'source_url', 'source_date', 'verified_on', 'position'];

/** Règles de validité des documents (calendrier) : aucune durée sans source, contrôlé aussi par l'API. */
export default function ValidityRulesAdminPage() {
  const { t } = useLanguage();
  const confirm = useConfirm();
  const [rules, setRules] = useState<Rule[]>([]);
  const [demarches, setDemarches] = useState<Array<{ id: number; title: string }>>([]);
  const [loaded, setLoaded] = useState(false);
  const [editing, setEditing] = useState<Rule | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState<Form>(empty);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const load = useCallback(() => {
    fetchApi<{ data: Rule[] }>('/admin/document-validity')
      .then((res) => { setRules(res.data); setLoaded(true); })
      .catch((e: unknown) => { setError(e instanceof Error ? e.message : t('common.error_generic')); setLoaded(true); });
  }, [t]);

  useEffect(load, [load]);
  useEffect(() => {
    fetchApi<{ data: Array<{ id: number; title: string }> }>('/admin/demarches?per_page=100')
      .then((res) => setDemarches(res.data))
      .catch(() => setDemarches([]));
  }, []);

  const open = (rule: Rule | null) => {
    setEditing(rule);
    setErrors({});
    setError('');
    setForm(rule ? {
      key: rule.key, name: { fr: rule.name, en: rule.name_en ?? '', ar: rule.name_ar ?? '' }, category: rule.category,
      demarche_id: rule.demarche_id ? String(rule.demarche_id) : '', validity_months: rule.validity_months ? String(rule.validity_months) : '',
      validity_start: rule.validity_start, renewal: { fr: rule.renewal ?? '', en: rule.renewal_en ?? '', ar: rule.renewal_ar ?? '' },
      notes: { fr: rule.notes ?? '', en: rule.notes_en ?? '', ar: rule.notes_ar ?? '' }, status: rule.status,
      source_label: rule.source_label ?? '', source_url: rule.source_url ?? '', source_date: rule.source_date ?? '',
      verified_on: rule.verified_on ?? '', position: String(rule.position), is_active: rule.is_active,
    } : empty());
    setShowForm(true);
  };

  const save = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setErrors({});
    setError('');
    const blank = (value: string) => value.trim() || null;
    const body = {
      key: form.key.trim(), name: form.name.fr.trim(), name_en: blank(form.name.en), name_ar: blank(form.name.ar),
      category: form.category, demarche_id: form.demarche_id ? Number(form.demarche_id) : null,
      validity_months: form.validity_months ? Number(form.validity_months) : null, validity_start: form.validity_start,
      renewal: blank(form.renewal.fr), renewal_en: blank(form.renewal.en), renewal_ar: blank(form.renewal.ar),
      notes: blank(form.notes.fr), notes_en: blank(form.notes.en), notes_ar: blank(form.notes.ar),
      status: form.status, source_label: blank(form.source_label), source_url: blank(form.source_url),
      source_date: form.source_date || null, verified_on: form.verified_on || null, position: Number(form.position) || 0, is_active: form.is_active,
    };
    try {
      await fetchApi(editing ? `/admin/document-validity/${editing.id}` : '/admin/document-validity', { method: editing ? 'PUT' : 'POST', body: JSON.stringify(body) });
      setShowForm(false);
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

  const remove = async (rule: Rule) => {
    if (!(await confirm({ title: t('adx.confirm_delete', { name: rule.name }), tone: 'danger' }))) return;
    try {
      await fetchApi(`/admin/document-validity/${rule.id}`, { method: 'DELETE' });
      setMessage(t('adx.deleted'));
      load();
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : t('common.error_generic'));
    }
  };

  const err = (field: string) => errors[field] && <span className="mt-1 block text-sm font-normal text-red-700">{errors[field][0]}</span>;
  const langLabel = (lang: string) => t(`adx.${lang}`);

  return (
    <AdminShell title={t('adx.dv_title')} intro={t('adx.dv_intro')} actions={!showForm && <button type="button" onClick={() => open(null)} className={primaryButton}><Plus size={16} aria-hidden="true" /> {t('adx.dv_new')}</button>}>
      <AdminNotice message={message} error={error} />

      {showForm && (
        <form onSubmit={save} noValidate className="mb-6 space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <div className="flex items-center justify-between">
            <h2 className="text-lg font-bold text-slate-900">{editing ? editing.name : t('adx.dv_new')}</h2>
            <button type="button" onClick={() => setShowForm(false)} aria-label={t('adm.d_close_form')} className="rounded-lg p-2 text-slate-500 hover:bg-slate-100"><X size={18} /></button>
          </div>
          <div className="grid gap-4 sm:grid-cols-3">
            <label className={adminLabel}>{t('adx.dv_key')}<input dir="ltr" value={form.key} onChange={(e) => setForm({ ...form, key: e.target.value })} className={adminInput} />{err('key')}</label>
            <label className={adminLabel}>{t('adx.e_category')}
              <select value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value as Form['category'] })} className={adminInput}>
                {CATEGORIES.map((c) => <option key={c} value={c}>{t(`valx.cat_${c}`)}</option>)}
              </select>
            </label>
            <label className={adminLabel}>{t('adx.dv_demarche')}
              <select value={form.demarche_id} onChange={(e) => setForm({ ...form, demarche_id: e.target.value })} className={adminInput}>
                <option value="">{t('adx.dv_none')}</option>
                {demarches.map((d) => <option key={d.id} value={String(d.id)}>{d.title}</option>)}
              </select>
            </label>
          </div>
          <div className="grid gap-4 sm:grid-cols-3">
            {LANGS.map((lang) => (
              <label key={lang} className={adminLabel}>{t('adx.dv_name', { lang: langLabel(lang) })}
                <input dir={lang === 'ar' ? 'rtl' : 'ltr'} lang={lang} value={form.name[lang]} onChange={(e) => setForm({ ...form, name: { ...form.name, [lang]: e.target.value } })} className={adminInput} />
                {err(lang === 'fr' ? 'name' : `name_${lang}`)}
              </label>
            ))}
          </div>
          <div className="grid gap-4 sm:grid-cols-3">
            <label className={adminLabel}>{t('adx.dv_status')}
              <select value={form.status} onChange={(e) => setForm({ ...form, status: e.target.value as Form['status'], validity_months: e.target.value === 'to_verify' ? '' : form.validity_months })} className={adminInput}>
                {STATUSES.map((s) => <option key={s} value={s}>{t(`valx.status_${s}`)}</option>)}
              </select>
            </label>
            <label className={adminLabel}>{t('adx.dv_months')}
              <input type="number" min={1} max={600} disabled={form.status === 'to_verify'} value={form.validity_months} onChange={(e) => setForm({ ...form, validity_months: e.target.value })} className={`${adminInput} disabled:bg-slate-100`} />
              <span className="mt-1 block text-xs font-normal text-slate-500">{t('adx.dv_months_hint')}</span>
              {err('validity_months')}
            </label>
            <label className={adminLabel}>{t('valx.start')}
              <select value={form.validity_start} onChange={(e) => setForm({ ...form, validity_start: e.target.value as Form['validity_start'] })} className={adminInput}>
                {STARTS.map((s) => <option key={s} value={s}>{t(`valx.start_${s}`)}</option>)}
              </select>
            </label>
          </div>
          <div className="grid gap-4 sm:grid-cols-3">
            {LANGS.map((lang) => (
              <label key={lang} className={adminLabel}>{t('adx.dv_renewal', { lang: langLabel(lang) })}
                <textarea rows={3} dir={lang === 'ar' ? 'rtl' : 'ltr'} lang={lang} value={form.renewal[lang]} onChange={(e) => setForm({ ...form, renewal: { ...form.renewal, [lang]: e.target.value } })} className={adminInput} />
              </label>
            ))}
            {LANGS.map((lang) => (
              <label key={`n-${lang}`} className={adminLabel}>{t('adx.dv_notes', { lang: langLabel(lang) })}
                <textarea rows={3} dir={lang === 'ar' ? 'rtl' : 'ltr'} lang={lang} value={form.notes[lang]} onChange={(e) => setForm({ ...form, notes: { ...form.notes, [lang]: e.target.value } })} className={adminInput} />
              </label>
            ))}
          </div>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <label className={adminLabel}>{t('adx.dv_source_label')}<input value={form.source_label} onChange={(e) => setForm({ ...form, source_label: e.target.value })} className={adminInput} />{err('source_label')}</label>
            <label className={adminLabel}>{t('adx.dv_source_url')}<input type="url" dir="ltr" value={form.source_url} onChange={(e) => setForm({ ...form, source_url: e.target.value })} className={adminInput} />{err('source_url')}</label>
            <label className={adminLabel}>{t('adx.dv_source_date')}<input type="date" value={form.source_date} onChange={(e) => setForm({ ...form, source_date: e.target.value })} className={adminInput} />{err('source_date')}</label>
            <label className={adminLabel}>{t('adx.dv_verified_on')}<input type="date" value={form.verified_on} onChange={(e) => setForm({ ...form, verified_on: e.target.value })} className={adminInput} />{err('verified_on')}</label>
          </div>
          <div className="flex flex-wrap items-center gap-4">
            <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
              <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} className="h-4 w-4" /> {t('adx.active')}
            </label>
            <button type="submit" disabled={saving} className={primaryButton}>{t('adx.save')}</button>
            <button type="button" onClick={() => setShowForm(false)} className={secondaryButton}>{t('adx.cancel')}</button>
          </div>
        </form>
      )}

      {!loaded && <p role="status" className="text-slate-500">{t('adx.loading')}</p>}
      {loaded && rules.length === 0 && <p className="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-slate-600">{t('adx.dv_empty')}</p>}
      <ul className="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white shadow-sm">
        {rules.map((rule) => (
          <li key={rule.id} className="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
            <div>
              <p className={`font-semibold ${rule.is_active ? 'text-slate-900' : 'text-slate-400 line-through'}`}>{rule.name}</p>
              <p className="text-xs text-slate-500">
                {t(`valx.status_${rule.status}`)} · {rule.validity_months ? t('adx.dv_months') + ' : ' + rule.validity_months : t('valx.duration_unknown')}
              </p>
            </div>
            <div className="flex gap-2">
              <button type="button" onClick={() => open(rule)} className={smallButton}><Pencil size={14} aria-hidden="true" /> {t('adx.edit')}</button>
              <button type="button" onClick={() => remove(rule)} className={dangerButton}><Trash2 size={14} aria-hidden="true" /> {t('adx.delete')}</button>
            </div>
          </li>
        ))}
      </ul>
    </AdminShell>
  );
}
