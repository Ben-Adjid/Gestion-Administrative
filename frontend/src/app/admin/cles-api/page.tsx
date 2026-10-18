'use client';

import { FormEvent, useCallback, useEffect, useState } from 'react';
import { Copy, KeyRound, Plus, Trash2 } from 'lucide-react';
import { adminInput, adminLabel, AdminNotice, AdminShell, dangerButton, primaryButton, smallButton } from '@/components/admin/AdminShell';
import { fetchApi, splitFormErrors } from '@/lib/api';
import { formatDate } from '@/lib/i18n';
import { useLanguage } from '@/hooks/useLanguage';

interface ApiKey {
  id: number;
  name: string;
  key_prefix: string;
  requests_count: number;
  last_used_at: string | null;
  is_active: boolean;
  created_at: string;
}

/** Clés API des partenaires (super administrateur) : accès sans limite de débit, usage compté. */
export default function ApiKeysAdminPage() {
  const { language, t } = useLanguage();
  const [items, setItems] = useState<ApiKey[]>([]);
  const [loaded, setLoaded] = useState(false);
  const [name, setName] = useState('');
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  // Clé en clair : renvoyée une seule fois par l'API, gardée uniquement en mémoire de la page.
  const [newKey, setNewKey] = useState<string | null>(null);
  const [copied, setCopied] = useState(false);

  const load = useCallback(() => {
    fetchApi<{ data: ApiKey[] }>('/admin/api-keys')
      .then((res) => { setItems(res.data); setLoaded(true); })
      .catch((e: unknown) => { setError(e instanceof Error ? e.message : t('common.error_generic')); setLoaded(true); });
  }, [t]);

  useEffect(load, [load]);

  const create = async (e: FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setErrors({});
    setError('');
    setMessage('');
    setCopied(false);
    try {
      const res = await fetchApi<{ data: ApiKey & { key: string } }>('/admin/api-keys', { method: 'POST', body: JSON.stringify({ name }) });
      setNewKey(res.data.key);
      setName('');
      load();
    } catch (err: unknown) {
      const { fieldErrors, general } = splitFormErrors(err, ['name'], t('common.error_generic'));
      setErrors(fieldErrors);
      if (general) setError(general);
    } finally {
      setSaving(false);
    }
  };

  const copy = async () => {
    if (!newKey) return;
    try {
      await navigator.clipboard.writeText(newKey);
      setCopied(true);
    } catch {
      setCopied(false); // Presse-papiers refusé : la clé reste sélectionnable à la main.
    }
  };

  const toggle = async (item: ApiKey) => {
    try {
      await fetchApi(`/admin/api-keys/${item.id}`, { method: 'PATCH', body: JSON.stringify({ is_active: !item.is_active }) });
      setNewKey(null);
      setMessage(t('adx.saved'));
      load();
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : t('common.error_generic'));
    }
  };

  const remove = async (item: ApiKey) => {
    if (!window.confirm(t('adx.confirm_delete', { name: item.name }))) return;
    try {
      await fetchApi(`/admin/api-keys/${item.id}`, { method: 'DELETE' });
      setNewKey(null);
      setMessage(t('adx.deleted'));
      load();
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : t('common.error_generic'));
    }
  };

  return (
    <AdminShell title={t('adx.key_title')} intro={t('adx.key_intro')}>
      <AdminNotice message={message} error={error} />

      <form onSubmit={create} noValidate className="mb-4 flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <label className={`${adminLabel} min-w-[16rem] flex-1`}>{t('adx.key_name')}
          <input required maxLength={150} value={name} onChange={(e) => setName(e.target.value)} placeholder={t('adx.key_name_ph')} className={adminInput} />
          {errors.name && <span className="mt-1 block text-sm text-red-700">{errors.name[0]}</span>}
        </label>
        <button type="submit" disabled={saving || !name.trim()} className={primaryButton}><Plus size={16} aria-hidden="true" /> {t('adx.key_create')}</button>
      </form>

      {newKey && (
        <div role="alert" className="mb-4 rounded-2xl border border-amber-300 bg-amber-50 p-4">
          <p className="font-semibold text-amber-900">{t('adx.key_new')}</p>
          <div className="mt-2 flex flex-wrap items-center gap-2">
            <code dir="ltr" className="break-all rounded-lg bg-white px-3 py-2 font-mono text-sm text-slate-900 select-all">{newKey}</code>
            <button type="button" onClick={copy} className={smallButton}><Copy size={14} aria-hidden="true" /> {copied ? t('adx.key_copied') : t('adx.key_copy')}</button>
          </div>
        </div>
      )}

      <p className="mb-4 text-sm text-slate-600">{t('adx.key_usage')}</p>

      {!loaded && <p role="status" className="text-slate-500">{t('adx.loading')}</p>}
      {loaded && items.length === 0 && <p className="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-slate-600">{t('adx.key_empty')}</p>}
      <ul className="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white shadow-sm">
        {items.map((item) => (
          <li key={item.id} className="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
            <div>
              <p className={`flex items-center gap-2 font-semibold ${item.is_active ? 'text-slate-900' : 'text-slate-400 line-through'}`}>
                <KeyRound size={16} aria-hidden="true" className="text-slate-400" /> {item.name}
                {!item.is_active && <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 no-underline">{t('adx.key_revoked')}</span>}
              </p>
              <p className="mt-0.5 text-xs text-slate-500">
                <code dir="ltr" className="font-mono">{item.key_prefix}…</code>
                {' · '}{t('adx.key_requests', { count: item.requests_count.toLocaleString(language === 'ar' ? 'ar-TD' : language === 'en' ? 'en-GB' : 'fr-FR') })}
                {' · '}{item.last_used_at ? t('adx.key_last_used', { date: formatDate(language, item.last_used_at) }) : t('adx.key_never_used')}
              </p>
            </div>
            <div className="flex gap-2">
              <button type="button" onClick={() => toggle(item)} className={smallButton}>{item.is_active ? t('adx.key_revoke') : t('adx.key_reactivate')}</button>
              <button type="button" onClick={() => remove(item)} className={dangerButton}><Trash2 size={14} aria-hidden="true" /> {t('adx.delete')}</button>
            </div>
          </li>
        ))}
      </ul>
    </AdminShell>
  );
}
