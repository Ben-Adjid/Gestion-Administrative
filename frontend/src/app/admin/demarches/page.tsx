'use client';

import { FormEvent, useCallback, useEffect, useState } from 'react';
import { BadgeCheck, ChevronLeft, ChevronRight, Download, Languages, Pencil, Plus, RotateCcw, Search, Trash2, X } from 'lucide-react';
import Button from '@/components/Button';
import { AdminSidebar, isModeratorRole, useCurrentAdmin } from '@/components/AdminSidebar';
import { downloadAdminFile, fetchApi } from '@/lib/api';
import { formatDate } from '@/lib/i18n';
import { useLanguage } from '@/hooks/useLanguage';
import { Categorie, Demarche } from '@/types';

const TEXT_FIELDS = ['title', 'summary', 'description', 'cost', 'processing_time', 'validity', 'institution', 'location'] as const;
const LIST_FIELDS = ['required_documents', 'procedure_steps', 'conditions'] as const;
type TranslatedField = (typeof TEXT_FIELDS)[number] | (typeof LIST_FIELDS)[number];
type TranslationForm = Record<TranslatedField, string>;

type AdminDemarche = Demarche & { translations?: Record<'en' | 'ar', Partial<Record<TranslatedField, string | string[] | null>>> };

type DemarcheForm = {
  title: string; summary: string; description: string; categorie_id: string; cost: string; cost_amount: string;
  processing_time: string; validity: string; processing_time_days: string; institution: string; location: string; contact_email: string;
  contact_phone: string; official_source_url: string; online_url: string; required_documents: string; procedure_steps: string;
  conditions: string; status: 'draft' | 'published' | 'archived'; is_online: boolean;
  en: TranslationForm; ar: TranslationForm;
  extra_costs: ExtraCostRow[];
  office_ids: number[];
  authority_level: '' | 'local' | 'regional' | 'national';
  location_city_id: string;
};

type ExtraCostRow = { label: string; label_en: string; label_ar: string; amount: string };
type OfficeOption = { id: number; name: string; city?: { name: string } };
type CityOption = { id: number; name: string };

const emptyTranslation = (): TranslationForm =>
  Object.fromEntries([...TEXT_FIELDS, ...LIST_FIELDS].map((field) => [field, ''])) as TranslationForm;

const emptyForm = (): DemarcheForm => ({
  title: '', summary: '', description: '', categorie_id: '', cost: '', cost_amount: '', processing_time: '',
  validity: '', processing_time_days: '', institution: '', location: '', contact_email: '', contact_phone: '', official_source_url: '',
  online_url: '', required_documents: '', procedure_steps: '', conditions: '', status: 'draft', is_online: false,
  en: emptyTranslation(), ar: emptyTranslation(),
  extra_costs: [], office_ids: [], authority_level: '', location_city_id: '',
});

const lines = (value: string): string[] => value.split(/\r?\n/).map((item) => item.trim()).filter(Boolean);

const FIELD_LABELS: Record<TranslatedField, string> = {
  title: 'adm.d_f_title', summary: 'adm.d_f_summary', description: 'adm.d_f_description', cost: 'adm.d_f_cost',
  processing_time: 'adm.d_f_delay', institution: 'adm.d_f_institution', location: 'adm.d_f_location',
  validity: 'adm.d_f_validity',
  required_documents: 'adm.d_f_documents', procedure_steps: 'adm.d_f_steps', conditions: 'adm.d_f_conditions',
};

const input = 'mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100';

export default function AdminDemarchesPage() {
  const { language, t } = useLanguage();
  const currentUser = useCurrentAdmin();
  const isModerator = isModeratorRole(currentUser?.role);

  const [demarches, setDemarches] = useState<AdminDemarche[]>([]);
  const [categories, setCategories] = useState<Categorie[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [actionId, setActionId] = useState<number | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState<AdminDemarche | null>(null);
  const [form, setForm] = useState<DemarcheForm>(emptyForm());
  const [showTranslations, setShowTranslations] = useState(false);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');
  const [search, setSearch] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [trashedOnly, setTrashedOnly] = useState(false);
  const [page, setPage] = useState(1);
  const [meta, setMeta] = useState({ currentPage: 1, lastPage: 1, total: 0 });
  const [offices, setOffices] = useState<OfficeOption[]>([]);
  const [cityOptions, setCityOptions] = useState<CityOption[]>([]);

  // Bureaux disponibles pour la liaison (lecture ouverte à toute l'équipe).
  useEffect(() => {
    fetchApi<{ data: OfficeOption[] }>('/admin/offices').then((res) => setOffices(res.data)).catch(() => setOffices([]));
    fetchApi<{ data: CityOption[] }>('/admin/cities').then((res) => setCityOptions(res.data)).catch(() => setCityOptions([]));
  }, []);

  const fetchData = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const query = new URLSearchParams({ per_page: '15', page: String(page) });
      if (search.trim()) query.set('q', search.trim());
      if (categoryFilter) query.set('categorie_id', categoryFilter);
      if (statusFilter) query.set('status', statusFilter);
      if (trashedOnly) query.set('trashed', 'only');

      const [demarchesResult, categoriesResult] = await Promise.all([
        fetchApi<{ data: AdminDemarche[]; meta?: { current_page?: number; last_page?: number; total?: number } }>(`/admin/demarches?${query}`),
        fetchApi<{ data: Categorie[] }>('/admin/categories?per_page=100'),
      ]);
      setDemarches(demarchesResult.data);
      setCategories(categoriesResult.data);
      setMeta({
        currentPage: demarchesResult.meta?.current_page ?? page,
        lastPage: demarchesResult.meta?.last_page ?? 1,
        total: demarchesResult.meta?.total ?? demarchesResult.data.length,
      });
    } catch (err) {
      setError(err instanceof Error ? err.message : t('adm.load_error'));
      setDemarches([]);
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [categoryFilter, page, search, statusFilter, trashedOnly]);

  useEffect(() => { void fetchData(); }, [fetchData]);

  const closeForm = () => {
    setShowForm(false);
    setEditing(null);
  };

  const openCreate = () => {
    setEditing(null);
    setForm({ ...emptyForm(), categorie_id: categories[0] ? String(categories[0].id) : '' });
    setShowTranslations(false);
    setShowForm(true);
    setError('');
    setNotice('');
  };

  const toTranslationForm = (values?: Partial<Record<TranslatedField, string | string[] | null>>): TranslationForm => {
    const result = emptyTranslation();
    if (!values) return result;
    for (const field of TEXT_FIELDS) result[field] = (values[field] as string | null) || '';
    for (const field of LIST_FIELDS) result[field] = Array.isArray(values[field]) ? (values[field] as string[]).join('\n') : '';
    return result;
  };

  const openEdit = (demarche: AdminDemarche) => {
    setEditing(demarche);
    setForm({
      title: demarche.title,
      summary: demarche.summary || '',
      description: demarche.description || '',
      categorie_id: String(demarche.categorie?.id || ''),
      cost: demarche.cost || '',
      cost_amount: demarche.cost_amount == null ? '' : String(demarche.cost_amount),
      processing_time: demarche.processing_time || '',
      validity: demarche.validity || '',
      processing_time_days: demarche.processing_time_days == null ? '' : String(demarche.processing_time_days),
      institution: demarche.institution || '',
      location: demarche.location || '',
      contact_email: demarche.contact_email || '',
      contact_phone: demarche.contact_phone || '',
      official_source_url: demarche.official_source_url || '',
      online_url: demarche.online_url || '',
      required_documents: (demarche.required_documents || []).join('\n'),
      procedure_steps: (demarche.procedure_steps || []).join('\n'),
      conditions: (demarche.conditions || []).join('\n'),
      status: demarche.status,
      is_online: demarche.is_online,
      en: toTranslationForm(demarche.translations?.en),
      ar: toTranslationForm(demarche.translations?.ar),
      extra_costs: (demarche.extra_costs_raw ?? []).map((item) => ({
        label: item.label, label_en: item.label_en || '', label_ar: item.label_ar || '', amount: String(item.amount),
      })),
      office_ids: demarche.office_ids ?? [],
      authority_level: demarche.authority_level ?? '',
      location_city_id: demarche.location_city_id ? String(demarche.location_city_id) : '',
    });
    setShowTranslations(false);
    setShowForm(true);
    setError('');
    setNotice('');
  };

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSaving(true);
    setError('');
    setNotice('');

    const translations: Record<string, string | string[] | null> = {};
    for (const language of ['en', 'ar'] as const) {
      for (const field of TEXT_FIELDS) translations[`${field}_${language}`] = form[language][field].trim() || null;
      for (const field of LIST_FIELDS) translations[`${field}_${language}`] = lines(form[language][field]);
    }

    try {
      await fetchApi(editing ? `/admin/demarches/${editing.id}` : '/admin/demarches', {
        method: editing ? 'PUT' : 'POST',
        body: JSON.stringify({
          title: form.title,
          description: form.description,
          institution: form.institution,
          cost: form.cost,
          status: form.status,
          is_online: form.is_online,
          categorie_id: Number(form.categorie_id),
          summary: form.summary.trim() || null,
          cost_amount: form.cost_amount ? Number(form.cost_amount) : null,
          processing_time: form.processing_time.trim() || null,
          validity: form.validity.trim() || null,
          processing_time_days: form.processing_time_days ? Number(form.processing_time_days) : null,
          location: form.location.trim() || null,
          contact_email: form.contact_email.trim() || null,
          contact_phone: form.contact_phone.trim() || null,
          official_source_url: form.official_source_url.trim() || null,
          online_url: form.online_url.trim() || null,
          required_documents: lines(form.required_documents),
          procedure_steps: lines(form.procedure_steps),
          conditions: lines(form.conditions),
          ...translations,
          extra_costs: form.extra_costs
            .filter((row) => row.label.trim() && row.amount !== '')
            .map((row) => ({ label: row.label.trim(), label_en: row.label_en.trim() || null, label_ar: row.label_ar.trim() || null, amount: Number(row.amount) })),
          office_ids: form.office_ids,
          authority_level: form.authority_level || null,
          location_city_id: form.location_city_id ? Number(form.location_city_id) : null,
        }),
      });
      setNotice(t(editing ? 'adm.d_done_updated' : 'adm.d_done_created'));
      closeForm();
      await fetchData();
    } catch (err) {
      setError(err instanceof Error ? err.message : t('adm.action_error'));
    } finally {
      setSaving(false);
    }
  };

  const runAction = async (demarche: AdminDemarche, action: 'delete' | 'restore' | 'publish' | 'unpublish') => {
    if (!window.confirm(t(`adm.d_confirm_${action}`, { title: demarche.title }))) return;
    setActionId(demarche.id);
    setError('');
    setNotice('');
    try {
      const endpoint = action === 'restore'
        ? `/admin/demarches/${demarche.id}/restore`
        : action === 'delete' ? `/admin/demarches/${demarche.id}` : `/admin/demarches/${demarche.id}/${action}`;
      await fetchApi(endpoint, { method: action === 'delete' ? 'DELETE' : action === 'restore' ? 'POST' : 'PATCH' });
      setNotice(t(`adm.d_done_${action}`));
      await fetchData();
    } catch (err) {
      setError(err instanceof Error ? err.message : t('adm.action_error'));
    } finally {
      setActionId(null);
    }
  };

  // L'équipe confirme que la fiche est toujours exacte (prix, pièces, adresses).
  const verify = async (demarche: AdminDemarche) => {
    setActionId(demarche.id);
    setError('');
    setNotice('');
    try {
      await fetchApi(`/admin/demarches/${demarche.id}/verify`, { method: 'PATCH' });
      setNotice(t('adx.d_verified'));
      await fetchData();
    } catch (err) {
      setError(err instanceof Error ? err.message : t('adm.action_error'));
    } finally {
      setActionId(null);
    }
  };

  const updateExtra = (index: number, field: keyof ExtraCostRow, value: string) =>
    setForm((current) => ({ ...current, extra_costs: current.extra_costs.map((row, i) => (i === index ? { ...row, [field]: value } : row)) }));

  const exportCsv = async () => {
    try {
      await downloadAdminFile('/admin/export/demarches', `demarches_${new Date().toISOString().slice(0, 10)}.csv`);
    } catch {
      setError(t('adm.d_export_error'));
    }
  };

  const update = <K extends keyof DemarcheForm>(field: K, value: DemarcheForm[K]) => setForm((current) => ({ ...current, [field]: value }));
  const updateTranslation = (language: 'en' | 'ar', field: TranslatedField, value: string) =>
    setForm((current) => ({ ...current, [language]: { ...current[language], [field]: value } }));

  const statusLabel = (status: string) => t(`admin.status_${status}`);
  const isUntranslated = (d: AdminDemarche) => !d.translations?.en?.title || !d.translations?.ar?.title;

  return (
    <div className="flex min-h-screen bg-slate-50">
      <AdminSidebar user={currentUser} />
      <main className="min-w-0 flex-1 px-4 py-8 sm:px-8">
        <div className="mx-auto max-w-7xl">
          <div className="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
              <p className="text-sm font-semibold text-blue-700">{t('adm.kicker')}</p>
              <h1 className="mt-1 text-3xl font-bold tracking-tight text-slate-950">{t('adm.d_title')}</h1>
              <p className="mt-2 text-sm text-slate-600">{t(isModerator ? 'adm.d_intro_mod' : 'adm.d_intro_editor')}</p>
            </div>
            <div className="flex flex-wrap items-center gap-3">
              {isModerator && (
                <button type="button" onClick={exportCsv} className="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                  <Download size={17} aria-hidden="true" /> {t('adm.d_export')}
                </button>
              )}
              <Button onClick={openCreate}><Plus size={18} aria-hidden="true" /> {t('adm.d_new')}</Button>
            </div>
          </div>

          {error && <p role="alert" className="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{error}</p>}
          {notice && <p role="status" className="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{notice}</p>}

          <section className="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
              <label className="relative block">
                <span className="sr-only">{t('adm.d_search')}</span>
                <Search size={18} aria-hidden="true" className="absolute start-3 top-1/2 -translate-y-1/2 text-slate-400" />
                <input value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }} placeholder={t('adm.d_search')} className="w-full rounded-lg border border-slate-300 py-2.5 pe-3 ps-10 text-sm" />
              </label>
              <select aria-label={t('adm.d_all_categories')} value={categoryFilter} onChange={(e) => { setCategoryFilter(e.target.value); setPage(1); }} className="rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                <option value="">{t('adm.d_all_categories')}</option>
                {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
              </select>
              <select aria-label={t('adm.d_all_statuses')} value={statusFilter} onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }} className="rounded-lg border border-slate-300 px-3 py-2.5 text-sm">
                <option value="">{t('adm.d_all_statuses')}</option>
                <option value="draft">{statusLabel('draft')}</option>
                <option value="published">{statusLabel('published')}</option>
              </select>
              <label className="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-700">
                <input type="checkbox" checked={trashedOnly} onChange={(e) => { setTrashedOnly(e.target.checked); setPage(1); }} />
                {t('adm.d_trash')}
              </label>
            </div>
          </section>

          {showForm && (
            <form onSubmit={submit} className="mb-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
              <div className="mb-5 flex items-center justify-between">
                <h2 className="text-xl font-bold text-slate-900">{t(editing ? 'adm.d_edit_title' : 'adm.d_create')}</h2>
                <button type="button" onClick={closeForm} aria-label={t('adm.d_close_form')} className="rounded-lg p-2 text-slate-500 hover:bg-slate-100"><X size={18} /></button>
              </div>

              <div className="grid gap-4 sm:grid-cols-2">
                <label className="text-sm font-medium text-slate-700">{t('adm.d_f_title')} *
                  <input required maxLength={255} value={form.title} onChange={(e) => update('title', e.target.value)} className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adm.d_f_category')} *
                  <select required value={form.categorie_id} onChange={(e) => update('categorie_id', e.target.value)} className={input}>
                    <option value="">{t('adm.d_choose_category')}</option>
                    {categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
                  </select>
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adm.d_f_institution')} *
                  <input required value={form.institution} onChange={(e) => update('institution', e.target.value)} className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adm.d_f_cost')} *
                  <input required value={form.cost} onChange={(e) => update('cost', e.target.value)} placeholder={t('adm.d_f_cost_ph')} className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adm.d_f_amount')}
                  <input type="number" min="0" step="1" value={form.cost_amount} onChange={(e) => update('cost_amount', e.target.value)} className={input} />
                  <span className="mt-1 block text-xs font-normal text-slate-500">{t('adm.d_f_amount_hint')}</span>
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adm.d_f_delay')}
                  <input value={form.processing_time} onChange={(e) => update('processing_time', e.target.value)} placeholder={t('adm.d_f_delay_ph')} className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adm.d_f_validity')}
                  <input maxLength={255} value={form.validity} onChange={(e) => update('validity', e.target.value)} className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adm.d_f_delay_days')}
                  <input type="number" min="0" step="1" value={form.processing_time_days} onChange={(e) => update('processing_time_days', e.target.value)} className={input} />
                </label>
                {isModerator ? (
                  <label className="text-sm font-medium text-slate-700">{t('adm.d_f_status')}
                    <select value={form.status} onChange={(e) => update('status', e.target.value as DemarcheForm['status'])} className={input}>
                      <option value="draft">{statusLabel('draft')}</option>
                      <option value="published">{statusLabel('published')}</option>
                    </select>
                  </label>
                ) : (
                  <div className="text-sm font-medium text-slate-700">{t('adm.d_f_status')}
                    <p className="mt-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-600">
                      {statusLabel(form.status)}
                      <span className="block text-xs text-slate-500">{t('adm.d_status_hint')}</span>
                    </p>
                  </div>
                )}
                <label className="text-sm font-medium text-slate-700 sm:col-span-2">{t('adm.d_f_summary')}
                  <textarea maxLength={500} rows={2} value={form.summary} onChange={(e) => update('summary', e.target.value)} className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700 sm:col-span-2">{t('adm.d_f_description')} *
                  <textarea required rows={4} value={form.description} onChange={(e) => update('description', e.target.value)} className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adm.d_f_location')}
                  <input value={form.location} onChange={(e) => update('location', e.target.value)} className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adx.d_location_city')}
                  <select value={form.location_city_id} onChange={(e) => update('location_city_id', e.target.value)} className={input}>
                    <option value="">{t('adx.d_location_city_any')}</option>
                    {cityOptions.map((c) => <option key={c.id} value={String(c.id)}>{c.name}</option>)}
                  </select>
                  <span className="mt-1 block text-xs font-normal text-slate-500">{t('adx.d_location_hint')}</span>
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adx.d_authority')}
                  <select value={form.authority_level} onChange={(e) => update('authority_level', e.target.value as DemarcheForm['authority_level'])} className={input}>
                    <option value="">{t('adx.d_authority_none')}</option>
                    <option value="local">{t('adx.d_authority_local')}</option>
                    <option value="regional">{t('adx.d_authority_regional')}</option>
                    <option value="national">{t('adx.d_authority_national')}</option>
                  </select>
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adm.d_f_phone')}
                  <input type="tel" dir="ltr" value={form.contact_phone} onChange={(e) => update('contact_phone', e.target.value)} className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adm.d_f_email')}
                  <input type="email" dir="ltr" value={form.contact_email} onChange={(e) => update('contact_email', e.target.value)} className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adm.d_f_source')}
                  <input type="url" dir="ltr" value={form.official_source_url} onChange={(e) => update('official_source_url', e.target.value)} placeholder="https://" className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700 sm:col-span-2">{t('adm.d_f_online_url')}
                  <input type="url" dir="ltr" value={form.online_url} onChange={(e) => update('online_url', e.target.value)} placeholder="https://" className={input} />
                </label>
                <label className="flex items-center gap-2 text-sm font-medium text-slate-700 sm:col-span-2">
                  <input type="checkbox" checked={form.is_online} onChange={(e) => update('is_online', e.target.checked)} />
                  {t('adm.d_f_is_online')}
                </label>
                {LIST_FIELDS.map((field) => (
                  <label key={field} className={`text-sm font-medium text-slate-700 ${field === 'conditions' ? 'sm:col-span-2' : ''}`}>
                    {t(FIELD_LABELS[field])} <span className="font-normal text-slate-500">({t('adm.one_per_line')})</span>
                    <textarea rows={4} value={form[field]} onChange={(e) => update(field, e.target.value)} className={input} />
                  </label>
                ))}
              </div>

              {/* Frais annexes : servent à l'estimation du coût total affichée aux citoyens */}
              <fieldset className="mt-6 rounded-xl border border-slate-200 p-4">
                <legend className="px-1 text-sm font-bold text-slate-800">{t('adx.d_extra_title')}</legend>
                <div className="space-y-3">
                  {form.extra_costs.map((row, index) => (
                    <div key={index} className="grid gap-2 rounded-lg bg-slate-50 p-3 sm:grid-cols-[1fr_1fr_1fr_8rem_auto] sm:items-end">
                      <label className="text-xs font-semibold text-slate-600">{t('adx.d_extra_label', { lang: t('adx.fr') })}
                        <input value={row.label} maxLength={100} onChange={(e) => updateExtra(index, 'label', e.target.value)} className={input} />
                      </label>
                      <label className="text-xs font-semibold text-slate-600">{t('adx.d_extra_label', { lang: t('adx.en') })}
                        <input value={row.label_en} maxLength={100} dir="ltr" onChange={(e) => updateExtra(index, 'label_en', e.target.value)} className={input} />
                      </label>
                      <label className="text-xs font-semibold text-slate-600">{t('adx.d_extra_label', { lang: t('adx.ar') })}
                        <input value={row.label_ar} maxLength={100} dir="rtl" lang="ar" onChange={(e) => updateExtra(index, 'label_ar', e.target.value)} className={input} />
                      </label>
                      <label className="text-xs font-semibold text-slate-600">{t('adx.d_extra_amount')}
                        <input type="number" min={0} step={1} value={row.amount} onChange={(e) => updateExtra(index, 'amount', e.target.value)} className={input} />
                      </label>
                      <button type="button" onClick={() => setForm((c) => ({ ...c, extra_costs: c.extra_costs.filter((_, i) => i !== index) }))} className="mb-1 rounded-lg px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                        {t('adx.d_extra_remove')}
                      </button>
                    </div>
                  ))}
                  {form.extra_costs.length < 10 && (
                    <button type="button" onClick={() => setForm((c) => ({ ...c, extra_costs: [...c.extra_costs, { label: '', label_en: '', label_ar: '', amount: '' }] }))} className="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-800 hover:underline">
                      <Plus size={15} aria-hidden="true" /> {t('adx.d_extra_add')}
                    </button>
                  )}
                </div>
              </fieldset>

              {/* Bureaux où se fait la démarche */}
              <fieldset className="mt-6 rounded-xl border border-slate-200 p-4">
                <legend className="px-1 text-sm font-bold text-slate-800">{t('adx.d_offices')}</legend>
                {offices.length === 0 ? (
                  <p className="text-sm text-slate-500">{t('adx.d_no_offices')} {t('adx.d_offices_hint')}</p>
                ) : (
                  <div className="grid max-h-48 gap-1 overflow-y-auto sm:grid-cols-2">
                    {offices.map((office) => (
                      <label key={office.id} className="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-slate-50">
                        <input
                          type="checkbox"
                          checked={form.office_ids.includes(office.id)}
                          onChange={() => setForm((c) => ({ ...c, office_ids: c.office_ids.includes(office.id) ? c.office_ids.filter((id) => id !== office.id) : [...c.office_ids, office.id] }))}
                          className="h-4 w-4"
                        />
                        {office.name}{office.city && <span className="text-slate-500"> · {office.city.name}</span>}
                      </label>
                    ))}
                  </div>
                )}
              </fieldset>

              <div className="mt-6 rounded-xl border border-slate-200">
                <button type="button" onClick={() => setShowTranslations(!showTranslations)} aria-expanded={showTranslations} className="flex w-full items-center justify-between gap-3 px-4 py-3 text-start font-semibold text-slate-800">
                  <span className="flex items-center gap-2"><Languages size={18} aria-hidden="true" /> {t('adm.d_translations')}</span>
                  <span className="text-sm text-slate-500">{showTranslations ? '−' : '+'}</span>
                </button>
                {showTranslations && (
                  <div className="border-t border-slate-200 p-4">
                    <p className="mb-4 text-sm text-slate-600">{t('adm.d_translations_hint')}</p>
                    <div className="grid gap-6 lg:grid-cols-2">
                      {(['en', 'ar'] as const).map((language) => (
                        <fieldset key={language} className="space-y-3" dir={language === 'ar' ? 'rtl' : 'ltr'} lang={language}>
                          <legend className="mb-2 text-sm font-bold text-blue-800">{t(language === 'en' ? 'adm.d_lang_en' : 'adm.d_lang_ar')}</legend>
                          {[...TEXT_FIELDS, ...LIST_FIELDS].map((field) => {
                            const isList = (LIST_FIELDS as readonly string[]).includes(field);
                            const multiline = isList || field === 'description' || field === 'summary';
                            return (
                              <label key={field} className="block text-sm font-medium text-slate-700">
                                {t(FIELD_LABELS[field])}{isList && <span className="font-normal text-slate-500"> ({t('adm.one_per_line')})</span>}
                                {multiline ? (
                                  <textarea rows={field === 'description' || isList ? 3 : 2} value={form[language][field]} onChange={(e) => updateTranslation(language, field, e.target.value)} className={input} />
                                ) : (
                                  <input value={form[language][field]} onChange={(e) => updateTranslation(language, field, e.target.value)} className={input} />
                                )}
                              </label>
                            );
                          })}
                        </fieldset>
                      ))}
                    </div>
                  </div>
                )}
              </div>

              <div className="mt-5 flex flex-wrap justify-end gap-3">
                <Button type="button" variant="secondary" onClick={closeForm}>{t('common.cancel')}</Button>
                <Button type="submit" loading={saving}>{t(editing ? 'adm.d_save' : 'adm.d_create_btn')}</Button>
              </div>
            </form>
          )}

          <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            {loading ? (
              <p role="status" className="p-10 text-center text-slate-600">{t('adm.loading')}</p>
            ) : demarches.length === 0 ? (
              <p className="p-10 text-center text-slate-600">{t('adm.d_empty')}</p>
            ) : (
              <div className="divide-y divide-slate-100">
                {demarches.map((demarche) => (
                  <article key={demarche.id} className="flex flex-col justify-between gap-4 p-5 sm:flex-row sm:items-center">
                    <div className="min-w-0">
                      <div className="mb-1 flex flex-wrap items-center gap-2">
                        <h2 className="font-bold text-slate-900">{demarche.title}</h2>
                        <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${demarche.status === 'published' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}`}>
                          {statusLabel(demarche.status)}
                        </span>
                        {isUntranslated(demarche) && (
                          <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{t('adm.d_untranslated')}</span>
                        )}
                      </div>
                      <p className="text-sm text-slate-600">{demarche.institution} · {demarche.categorie?.name || t('adm.d_no_category')}</p>
                      <p className="mt-0.5 text-xs text-slate-500">
                        {demarche.last_verified_at ? t('adx.d_last_verified', { date: formatDate(language, demarche.last_verified_at) }) : t('adx.d_never_verified')}
                      </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                      {trashedOnly ? (
                        isModerator && (
                          <Button size="sm" variant="secondary" loading={actionId === demarche.id} onClick={() => void runAction(demarche, 'restore')}>
                            <RotateCcw size={15} aria-hidden="true" /> {t('adm.d_restore')}
                          </Button>
                        )
                      ) : (
                        <>
                          <Button size="sm" variant="secondary" onClick={() => openEdit(demarche)}><Pencil size={15} aria-hidden="true" /> {t('common.edit')}</Button>
                          {isModerator && (
                            <>
                              {demarche.status === 'published'
                                ? <Button size="sm" variant="secondary" loading={actionId === demarche.id} onClick={() => void runAction(demarche, 'unpublish')}>{t('adm.d_unpublish')}</Button>
                                : <Button size="sm" loading={actionId === demarche.id} onClick={() => void runAction(demarche, 'publish')}>{t('adm.d_publish')}</Button>}
                              {demarche.status === 'published' && (
                                <Button size="sm" variant="secondary" loading={actionId === demarche.id} onClick={() => void verify(demarche)}>
                                  <BadgeCheck size={15} aria-hidden="true" /> {t('adx.d_verify')}
                                </Button>
                              )}
                              <Button size="sm" variant="danger" loading={actionId === demarche.id} onClick={() => void runAction(demarche, 'delete')}>
                                <Trash2 size={15} aria-hidden="true" /> {t('adm.d_archive')}
                              </Button>
                            </>
                          )}
                          {!isModerator && demarche.status === 'draft' && (
                            <span className="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">{t('adm.d_awaiting')}</span>
                          )}
                        </>
                      )}
                    </div>
                  </article>
                ))}
              </div>
            )}
            {!loading && meta.total > 0 && (
              <nav aria-label={t('catalog.pagination')} className="flex flex-col justify-between gap-3 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center">
                <p className="text-sm text-slate-600">{t('adm.results', { n: meta.total, page: meta.currentPage, pages: meta.lastPage })}</p>
                <div className="flex gap-2">
                  <Button size="sm" variant="secondary" disabled={meta.currentPage <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))}>
                    <ChevronLeft size={16} aria-hidden="true" className="rtl:-scale-x-100" /> {t('catalog.previous')}
                  </Button>
                  <Button size="sm" variant="secondary" disabled={meta.currentPage >= meta.lastPage} onClick={() => setPage((p) => Math.min(meta.lastPage, p + 1))}>
                    {t('catalog.next')} <ChevronRight size={16} aria-hidden="true" className="rtl:-scale-x-100" />
                  </Button>
                </div>
              </nav>
            )}
          </section>
        </div>
      </main>
    </div>
  );
}
