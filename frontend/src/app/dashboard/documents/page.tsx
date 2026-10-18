'use client';

import { FormEvent, useEffect, useState } from 'react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import CitizenNav from '@/components/CitizenNav';
import { API_V1_URL, fetchApi, splitFormErrors } from '@/lib/api';
import { formatDate } from '@/lib/i18n';
import { useLanguage } from '@/hooks/useLanguage';

interface ValidityOption { id: number; key: string; name: string; status: string; validity_months: number | null; can_compute_expiry: boolean }

/** Type de document proposé quand une règle est choisie (les actes d'état civil relèvent de « Autre document »). */
const RULE_TYPE: Record<string, string> = {
  'carte-nationale-identite': 'cni',
  'passeport-ordinaire': 'passeport',
  'passeport-diplomatique-service': 'passeport',
  'permis-de-conduire': 'permis',
};

interface UserDocument {
  id: number;
  type: string;
  label: string | null;
  expiration_date: string | null;
  issue_date: string | null;
  validity_rule_id: number | null;
  days_remaining: number | null;
  validity: null | {
    state: 'valid' | 'expiring' | 'expired' | 'permanent' | 'status_based' | 'unavailable';
    expiry_date: string | null;
    days_left: number | null;
    message: string;
    rule_name: string;
    rule_status: string;
    is_business_requirement: boolean;
    source_url: string | null;
  };
  state: 'expired' | 'urgent' | 'soon' | 'ok' | 'no_expiry';
}

const STATE_STYLE: Record<UserDocument['state'], string> = {
  expired: 'bg-red-100 text-red-800',
  urgent: 'bg-orange-100 text-orange-800',
  soon: 'bg-amber-100 text-amber-900',
  ok: 'bg-emerald-100 text-emerald-800',
  no_expiry: 'bg-sky-100 text-sky-900',
};

/** Règles sans date d'expiration (acte permanent ou lié au statut) : aucune date à saisir pour l'expiration. */
const OPEN_ENDED_STATUSES = ['permanent', 'status_based'];

// Couleurs des états calculés (règles officielles ou exigences métier).
const VALIDITY_STYLE: Record<string, string> = {
  valid: 'bg-emerald-100 text-emerald-800',
  expiring: 'bg-amber-100 text-amber-900',
  expired: 'bg-red-100 text-red-800',
  permanent: 'bg-sky-100 text-sky-900',
  status_based: 'bg-sky-100 text-sky-900',
  unavailable: 'bg-slate-200 text-slate-800',
};

const EMPTY = { type: 'cni', label: '', expiration_date: '', issue_date: '', validity_rule_id: '' };

export default function DocumentsPage() {
  const { language, t } = useLanguage();
  const [documents, setDocuments] = useState<UserDocument[] | null>(null);
  const [types, setTypes] = useState<string[]>([]);
  const [rules, setRules] = useState<ValidityOption[]>([]);
  const [form, setForm] = useState(EMPTY);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [saving, setSaving] = useState(false);

  const load = () =>
    fetchApi<{ data: UserDocument[]; types: Record<string, string> }>('/me/documents')
      .then((res) => {
        setDocuments(res.data);
        setTypes(Object.keys(res.types));
      })
      .catch((e: unknown) => setError(e instanceof Error ? e.message : t('common.error_generic')));

  // Seules les règles qui permettent un calcul sont proposées (jamais une durée non sourcée ou non confirmée).
  useEffect(() => {
    fetch(`${API_V1_URL}/document-validity?lang=${language}`, { headers: { Accept: 'application/json' } })
      .then((res) => (res.ok ? res.json() : { data: [] }))
      .then((json: { data: ValidityOption[] }) => setRules(json.data.filter((rule) => rule.can_compute_expiry || OPEN_ENDED_STATUSES.includes(rule.status))))
      .catch(() => setRules([]));
  }, [language]);

  // eslint-disable-next-line react-hooks/exhaustive-deps
  useEffect(() => { load(); }, []);

  const nameOf = (doc: UserDocument) => doc.label || t(`documents.type_${doc.type}`);

  // Règle choisie sans date d'expiration (acte permanent ou lié au statut) : rien à saisir pour l'expiration.
  const selectedRule = rules.find((rule) => String(rule.id) === form.validity_rule_id);
  const openEnded = !!selectedRule && OPEN_ENDED_STATUSES.includes(selectedRule.status);

  const reset = () => {
    setForm(EMPTY);
    setEditingId(null);
    setErrors({});
  };

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setSaving(true);
    setErrors({});
    setError('');
    setMessage('');
    try {
      await fetchApi(editingId ? `/me/documents/${editingId}` : '/me/documents', {
        method: editingId ? 'PUT' : 'POST',
        body: JSON.stringify({
          type: form.type,
          // Sans nom personnalisé, le document prend le nom de la règle choisie (ex. « Acte de naissance »).
          label: form.label || selectedRule?.name || null,
          // Document à règle : l'expiration est calculée par le serveur (ou absente pour un acte permanent).
          validity_rule_id: form.validity_rule_id ? Number(form.validity_rule_id) : null,
          issue_date: form.validity_rule_id && form.issue_date ? form.issue_date : null,
          expiration_date: form.validity_rule_id ? null : form.expiration_date,
        }),
      });
      setMessage(t(editingId ? 'documents.updated' : 'documents.added'));
      reset();
      await load();
    } catch (e: unknown) {
      const { fieldErrors, general } = splitFormErrors(e, ['expiration_date', 'label', 'type'], t('common.error_generic'));
      setErrors(fieldErrors);
      if (general) setError(general);
    } finally {
      setSaving(false);
    }
  };

  const edit = (doc: UserDocument) => {
    setEditingId(doc.id);
    setForm({ type: doc.type, label: doc.label || '', expiration_date: doc.validity_rule_id ? '' : (doc.expiration_date ?? ''), issue_date: doc.issue_date ?? '', validity_rule_id: doc.validity_rule_id ? String(doc.validity_rule_id) : '' });
    setMessage('');
  };

  const remove = async (doc: UserDocument) => {
    if (!window.confirm(t('documents.confirm_delete', { name: nameOf(doc) }))) return;
    try {
      await fetchApi(`/me/documents/${doc.id}`, { method: 'DELETE' });
      setMessage(t('documents.deleted'));
      if (editingId === doc.id) reset();
      await load();
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : t('common.error_generic'));
    }
  };

  const fieldError = (name: string) => errors[name]?.[0];
  const input = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200';

  return (
    <main className="mx-auto max-w-5xl px-4 py-10">
      <h1 className="text-3xl font-bold tracking-tight text-slate-950">{t('documents.title')}</h1>
      <p className="mt-2 text-slate-600">{t('documents.intro')}</p>
      <div className="mt-6"><CitizenNav /></div>

      {message && <p role="status" className="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{message}</p>}
      {error && <p role="alert" className="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">{error}</p>}

      <form onSubmit={submit} noValidate className="mb-10 grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-end">
        <div>
          <label htmlFor="doc-type" className="block text-sm font-semibold text-slate-700">{t('documents.type')}</label>
          <select id="doc-type" value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })} className={input}>
            {types.map((value) => <option key={value} value={value}>{t(`documents.type_${value}`)}</option>)}
          </select>
          {fieldError('type') && <p className="mt-1 text-sm text-red-700">{fieldError('type')}</p>}
        </div>
        <div>
          <label htmlFor="doc-rule" className="block text-sm font-semibold text-slate-700">{t('valx.rule_label')} <span className="font-normal text-slate-500">({t('common.optional')})</span></label>
          <select
            id="doc-rule"
            value={form.validity_rule_id}
            onChange={(e) => {
              const rule = rules.find((item) => String(item.id) === e.target.value);
              setForm({ ...form, validity_rule_id: e.target.value, type: rule ? (RULE_TYPE[rule.key] ?? 'autre') : form.type, issue_date: '', expiration_date: '' });
            }}
            className={input}
          >
            <option value="">{t('valx.rule_none')}</option>
            {rules.map((rule) => <option key={rule.id} value={rule.id}>{rule.name}</option>)}
          </select>
        </div>
        <div>
          <label htmlFor="doc-label" className="block text-sm font-semibold text-slate-700">
            {t('documents.custom_name')} <span className="font-normal text-slate-500">({t('common.optional')})</span>
          </label>
          <input id="doc-label" value={form.label} maxLength={120} onChange={(e) => setForm({ ...form, label: e.target.value })} placeholder={t('documents.custom_ph')} className={input} />
          {fieldError('label') && <p className="mt-1 text-sm text-red-700">{fieldError('label')}</p>}
        </div>
        <div>
          {openEnded ? (
            <p className="text-sm text-slate-700">{t('documents.no_expiry_note')}</p>
          ) : (
            <>
              <label htmlFor="doc-date" className="block text-sm font-semibold text-slate-700">{form.validity_rule_id ? t('valx.issued_on_label') : t('documents.expiry')}</label>
              <input id="doc-date" type="date" required value={form.validity_rule_id ? form.issue_date : form.expiration_date} onChange={(e) => setForm(form.validity_rule_id ? { ...form, issue_date: e.target.value } : { ...form, expiration_date: e.target.value })} className={input} />
            </>
          )}
          {fieldError('expiration_date') && <p className="mt-1 text-sm text-red-700">{fieldError('expiration_date')}</p>}
          {fieldError('issue_date') && <p className="mt-1 text-sm text-red-700">{fieldError('issue_date')}</p>}
        </div>
        <div className="flex gap-2">
          <button
            type="submit"
            disabled={saving || (!openEnded && !(form.validity_rule_id ? form.issue_date : form.expiration_date))}
            className="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
          >
            <Plus aria-hidden="true" size={17} /> {editingId ? t('common.save') : t('documents.add')}
          </button>
          {editingId && (
            <button type="button" onClick={reset} className="rounded-lg border border-slate-300 px-4 py-2.5 font-semibold text-slate-700 hover:bg-slate-50">
              {t('common.cancel')}
            </button>
          )}
        </div>
      </form>

      {documents === null && !error && <div role="status" aria-label={t('common.loading')} className="h-32 animate-pulse rounded-2xl bg-slate-100" />}

      {documents && documents.length === 0 && (
        <p className="rounded-xl border border-dashed border-slate-300 p-8 text-center text-slate-600">{t('documents.empty')}</p>
      )}

      {documents && documents.length > 0 && (
        <ul className="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white">
          {documents.map((doc) => (
            <li key={doc.id} className="flex flex-wrap items-center justify-between gap-3 p-4">
              <div>
                <p className="font-semibold text-slate-900">{nameOf(doc)}</p>
                <p className="text-sm text-slate-600">
                  {doc.label ? `${t(`documents.type_${doc.type}`)} · ` : ''}
                  {doc.expiration_date ? t('documents.expires_on', { date: formatDate(language, doc.expiration_date) }) : t('documents.no_expiry')}
                </p>
              </div>
              <div className="flex items-center gap-2">
                <span className={`rounded-full px-3 py-1 text-xs font-bold ${doc.validity ? VALIDITY_STYLE[doc.validity.state] : STATE_STYLE[doc.state]}`}>{doc.validity ? t(`valx.state_${doc.validity.state}`) : t(`documents.state_${doc.state}`)}</span>
                <button type="button" onClick={() => edit(doc)} aria-label={t('documents.edit_label', { name: nameOf(doc) })} className="rounded-lg p-2 text-slate-600 hover:bg-slate-100 hover:text-blue-800">
                  <Pencil aria-hidden="true" size={17} />
                </button>
                <button type="button" onClick={() => remove(doc)} aria-label={t('documents.delete_label', { name: nameOf(doc) })} className="rounded-lg p-2 text-slate-600 hover:bg-red-50 hover:text-red-700">
                  <Trash2 aria-hidden="true" size={17} />
                </button>
              </div>
            </li>
          ))}
        </ul>
      )}
    </main>
  );
}
