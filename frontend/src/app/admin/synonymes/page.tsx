'use client';

import { FormEvent, useCallback, useEffect, useState } from 'react';
import { ArrowLeftRight, Plus, Trash2 } from 'lucide-react';
import { adminInput, adminLabel, AdminNotice, AdminShell, dangerButton, primaryButton } from '@/components/admin/AdminShell';
import { fetchApi, splitFormErrors } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';
import { useConfirm } from '@/components/ConfirmProvider';

interface Synonym { id: number; term: string; synonym: string; is_active: boolean }

/** Synonymes de recherche : « pièce d'identité » trouve aussi la carte d'identité, dans les deux sens. */
export default function SynonymsAdminPage() {
  const { t } = useLanguage();
  const confirm = useConfirm();
  const [items, setItems] = useState<Synonym[]>([]);
  const [loaded, setLoaded] = useState(false);
  const [form, setForm] = useState({ term: '', synonym: '' });
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const load = useCallback(() => {
    fetchApi<{ data: Synonym[] }>('/admin/synonyms')
      .then((res) => { setItems(res.data); setLoaded(true); })
      .catch((e: unknown) => { setError(e instanceof Error ? e.message : t('common.error_generic')); setLoaded(true); });
  }, [t]);

  useEffect(load, [load]);

  const add = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setErrors({});
    setError('');
    setMessage('');
    try {
      await fetchApi('/admin/synonyms', { method: 'POST', body: JSON.stringify({ ...form, is_active: true }) });
      setForm({ term: '', synonym: '' });
      setMessage(t('adx.saved'));
      load();
    } catch (err: unknown) {
      const { fieldErrors, general } = splitFormErrors(err, ['term', 'synonym'], t('common.error_generic'));
      setErrors(fieldErrors);
      if (general) setError(general);
    } finally {
      setSaving(false);
    }
  };

  const toggle = async (item: Synonym) => {
    try {
      await fetchApi(`/admin/synonyms/${item.id}`, { method: 'PUT', body: JSON.stringify({ term: item.term, synonym: item.synonym, is_active: !item.is_active }) });
      load();
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : t('common.error_generic'));
    }
  };

  const remove = async (item: Synonym) => {
    if (!(await confirm({ title: t('adx.confirm_delete', { name: `${item.term} ↔ ${item.synonym}` }), tone: 'danger' }))) return;
    try {
      await fetchApi(`/admin/synonyms/${item.id}`, { method: 'DELETE' });
      setMessage(t('adx.deleted'));
      load();
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : t('common.error_generic'));
    }
  };

  return (
    <AdminShell title={t('adx.syn_title')} intro={t('adx.syn_intro')}>
      <AdminNotice message={message} error={error} />

      <form onSubmit={add} noValidate className="mb-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_auto_1fr_auto] sm:items-end">
        <label className={adminLabel}>{t('adx.syn_term')}
          <input required maxLength={100} value={form.term} onChange={(e) => setForm({ ...form, term: e.target.value })} placeholder={t('adx.syn_term_ph')} className={adminInput} />
          {errors.term && <span className="mt-1 block text-sm text-red-700">{errors.term[0]}</span>}
        </label>
        <ArrowLeftRight size={18} aria-hidden="true" className="mx-auto mb-3 hidden text-slate-400 sm:block" />
        <label className={adminLabel}>{t('adx.syn_synonym')}
          <input required maxLength={100} value={form.synonym} onChange={(e) => setForm({ ...form, synonym: e.target.value })} placeholder={t('adx.syn_synonym_ph')} className={adminInput} />
          {errors.synonym && <span className="mt-1 block text-sm text-red-700">{errors.synonym[0]}</span>}
        </label>
        <button type="submit" disabled={saving || !form.term.trim() || !form.synonym.trim()} className={primaryButton}><Plus size={16} aria-hidden="true" /> {t('adx.add')}</button>
      </form>

      {!loaded && <p role="status" className="text-slate-500">{t('adx.loading')}</p>}
      {loaded && items.length === 0 && <p className="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-slate-600">{t('adx.syn_empty')}</p>}
      <ul className="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white shadow-sm">
        {items.map((item) => (
          <li key={item.id} className="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
            <p className={`flex items-center gap-2 font-semibold ${item.is_active ? 'text-slate-900' : 'text-slate-400 line-through'}`}>
              {item.term} <ArrowLeftRight size={14} aria-hidden="true" className="text-slate-400" /> {item.synonym}
            </p>
            <div className="flex gap-2">
              <label className="flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                <input type="checkbox" checked={item.is_active} onChange={() => toggle(item)} className="h-4 w-4" /> {t('adx.active')}
              </label>
              <button type="button" onClick={() => remove(item)} className={dangerButton}><Trash2 size={14} aria-hidden="true" /> {t('adx.delete')}</button>
            </div>
          </li>
        ))}
      </ul>
    </AdminShell>
  );
}
