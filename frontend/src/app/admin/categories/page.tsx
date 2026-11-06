'use client';

import { FormEvent, useCallback, useEffect, useState } from 'react';
import { ArrowDown, ArrowUp, Pencil, Plus, Trash2, X } from 'lucide-react';
import Button from '@/components/Button';
import { AdminSidebar, useCurrentAdmin } from '@/components/AdminSidebar';
import { fetchApi } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';
import { Categorie } from '@/types';

type AdminCategorie = Categorie & { translations?: Record<'en' | 'ar', { name: string | null; description: string | null }> };

type CategoryForm = {
  name: string; name_en: string; name_ar: string; slug: string;
  description: string; description_en: string; description_ar: string;
  icon: string; color: string; is_active: boolean;
};

const emptyForm: CategoryForm = {
  name: '', name_en: '', name_ar: '', slug: '', description: '', description_en: '', description_ar: '',
  icon: 'ti-folder', color: '#2563EB', is_active: true,
};

const input = 'mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base text-slate-900 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100';

export default function AdminCategoriesPage() {
  const { t } = useLanguage();
  const currentUser = useCurrentAdmin();
  const [categories, setCategories] = useState<AdminCategorie[]>([]);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [editing, setEditing] = useState<AdminCategorie | null>(null);
  const [form, setForm] = useState<CategoryForm>(emptyForm);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const result = await fetchApi<{ data: AdminCategorie[] }>('/admin/categories?per_page=100');
      setCategories(result.data);
    } catch (err) {
      setError(err instanceof Error ? err.message : t('adm.load_error'));
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => { void load(); }, [load]);

  // Réordonner : échange avec la voisine puis enregistre l'ordre complet (1, 2, 3…), affiché tel quel sur le site.
  const [reordering, setReordering] = useState(false);
  const move = async (index: number, delta: number) => {
    const next = [...categories];
    [next[index], next[index + delta]] = [next[index + delta], next[index]];
    setReordering(true);
    setError('');
    try {
      await fetchApi('/admin/categories/reorder', {
        method: 'POST',
        body: JSON.stringify({ categories: next.map((c, position) => ({ id: c.id, display_order: position + 1 })) }),
      });
      setCategories(next);
      setNotice(t('adx.saved'));
    } catch (err) {
      setError(err instanceof Error ? err.message : t('adm.action_error'));
    } finally {
      setReordering(false);
    }
  };

  const closeForm = () => {
    setShowForm(false);
    setEditing(null);
  };

  const openCreate = () => {
    setEditing(null);
    setForm(emptyForm);
    setShowForm(true);
    setError('');
    setNotice('');
  };

  const openEdit = (category: AdminCategorie) => {
    setEditing(category);
    setForm({
      name: category.name,
      name_en: category.translations?.en.name || '',
      name_ar: category.translations?.ar.name || '',
      slug: category.slug,
      description: category.description || '',
      description_en: category.translations?.en.description || '',
      description_ar: category.translations?.ar.description || '',
      icon: category.icon || 'ti-folder',
      color: /^#[0-9a-f]{6}$/i.test(category.color || '') ? category.color : '#2563EB',
      is_active: category.is_active ?? true,
    });
    setShowForm(true);
    setError('');
    setNotice('');
  };

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSaving(true);
    setError('');
    setNotice('');
    const clean = (value: string) => value.trim() || null;
    try {
      await fetchApi(editing ? `/admin/categories/${editing.id}` : '/admin/categories', {
        method: editing ? 'PUT' : 'POST',
        body: JSON.stringify({
          name: form.name.trim(),
          name_en: clean(form.name_en),
          name_ar: clean(form.name_ar),
          ...(form.slug.trim() ? { slug: form.slug.trim() } : {}),
          description: clean(form.description),
          description_en: clean(form.description_en),
          description_ar: clean(form.description_ar),
          icon: form.icon.trim() || 'ti-folder',
          color: form.color,
          is_active: form.is_active,
        }),
      });
      setNotice(t(editing ? 'adm.c_done_updated' : 'adm.c_done_created'));
      closeForm();
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : t('adm.action_error'));
    } finally {
      setSaving(false);
    }
  };

  const remove = async (category: AdminCategorie) => {
    if (!window.confirm(t('adm.c_confirm_delete', { name: category.name }))) return;
    setError('');
    setNotice('');
    try {
      await fetchApi(`/admin/categories/${category.id}`, { method: 'DELETE' });
      setNotice(t('adm.c_done_deleted'));
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : t('adm.action_error'));
    }
  };

  return (
    <div className="flex min-h-screen bg-slate-50">
      <AdminSidebar user={currentUser} />
      <main className="min-w-0 flex-1 px-4 py-8 sm:px-8">
        <div className="mx-auto max-w-6xl">
          <div className="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
              <p className="text-sm font-semibold text-blue-700">{t('adm.kicker')}</p>
              <h1 className="mt-1 text-3xl font-bold tracking-tight text-slate-950">{t('adm.c_title')}</h1>
            </div>
            <Button onClick={openCreate}><Plus size={18} aria-hidden="true" /> {t('adm.c_new')}</Button>
          </div>

          {error && <p role="alert" className="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{error}</p>}
          {notice && <p role="status" className="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{notice}</p>}

          {showForm && (
            <form onSubmit={submit} className="mb-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <div className="mb-5 flex items-center justify-between">
                <h2 className="text-lg font-bold text-slate-900">{t(editing ? 'adm.c_edit_title' : 'adm.c_create')}</h2>
                <button type="button" onClick={closeForm} aria-label={t('adm.d_close_form')} className="rounded-lg p-2 text-slate-500 hover:bg-slate-100"><X size={18} /></button>
              </div>
              <div className="grid gap-4 sm:grid-cols-3">
                <label className="text-sm font-medium text-slate-700">{t('adm.c_f_name')} *
                  <input required maxLength={100} value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adm.c_f_name_en')}
                  <input maxLength={100} dir="ltr" value={form.name_en} onChange={(e) => setForm({ ...form, name_en: e.target.value })} className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adm.c_f_name_ar')}
                  <input maxLength={100} dir="rtl" lang="ar" value={form.name_ar} onChange={(e) => setForm({ ...form, name_ar: e.target.value })} className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700 sm:col-span-3">{t('adm.c_f_description')} (FR)
                  <textarea maxLength={1000} rows={2} value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700 sm:col-span-3 lg:col-span-1">{t('adm.c_f_description')} (EN)
                  <textarea maxLength={1000} rows={2} dir="ltr" value={form.description_en} onChange={(e) => setForm({ ...form, description_en: e.target.value })} className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700 sm:col-span-3 lg:col-span-2">{t('adm.c_f_description')} (AR)
                  <textarea maxLength={1000} rows={2} dir="rtl" lang="ar" value={form.description_ar} onChange={(e) => setForm({ ...form, description_ar: e.target.value })} className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adm.c_f_slug')}
                  <input maxLength={120} dir="ltr" pattern="[A-Za-z0-9_\-]*" value={form.slug} onChange={(e) => setForm({ ...form, slug: e.target.value })} className={input} />
                  <span className="mt-1 block text-xs font-normal text-slate-500">{t('adm.c_f_slug_hint')}</span>
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adm.c_f_icon')}
                  <input maxLength={50} dir="ltr" value={form.icon} onChange={(e) => setForm({ ...form, icon: e.target.value })} className={input} />
                </label>
                <label className="text-sm font-medium text-slate-700">{t('adm.c_f_color')}
                  <input type="color" value={form.color} onChange={(e) => setForm({ ...form, color: e.target.value })} className="mt-1.5 h-11 w-full rounded-lg border border-slate-300 bg-white p-1" />
                </label>
                <label className="flex items-center gap-2 text-sm font-medium text-slate-700 sm:col-span-3">
                  <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} className="h-4 w-4" />
                  {t('adm.c_f_active')}
                </label>
              </div>
              <div className="mt-5 flex gap-3">
                <Button type="submit" loading={saving}>{t(editing ? 'common.save' : 'adm.c_create_btn')}</Button>
                <Button type="button" variant="secondary" onClick={closeForm}>{t('common.cancel')}</Button>
              </div>
            </form>
          )}

          {loading ? (
            <p role="status" className="rounded-xl bg-white p-8 text-center text-slate-600">{t('adm.loading')}</p>
          ) : categories.length === 0 ? (
            <p className="rounded-xl border border-slate-200 bg-white p-8 text-center text-slate-600">{t('adm.c_empty')}</p>
          ) : (
            <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
              {categories.map((category, index) => (
                <article key={category.id} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                  <div className="mb-3 flex items-start justify-between gap-3">
                    <span className="h-4 w-4 shrink-0 rounded-full border border-slate-200" style={{ backgroundColor: /^#/.test(category.color || '') ? category.color : '#cbd5e1' }} aria-hidden="true" />
                    <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${category.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>
                      {t(category.is_active ? 'adm.c_active' : 'adm.c_inactive')}
                    </span>
                  </div>
                  <h2 className="text-lg font-bold text-slate-900">{category.name}</h2>
                  <p className="mt-0.5 text-xs text-slate-500" dir="ltr">/{category.slug}</p>
                  <p className="mt-1 text-sm text-slate-600">
                    {[category.translations?.en.name, category.translations?.ar.name].filter(Boolean).join(' · ') || '—'}
                  </p>
                  <p className="mt-2 min-h-10 text-sm leading-5 text-slate-600">{category.description || t('adm.c_no_description')}</p>
                  <p className="mt-3 text-xs text-slate-500">{t('adm.c_count', { n: category.demarches_count ?? 0 })}</p>
                  <div className="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                    <Button variant="secondary" size="sm" disabled={index === 0 || reordering} onClick={() => void move(index, -1)} aria-label={t('adx.j_up')}>
                      <ArrowUp size={15} aria-hidden="true" />
                    </Button>
                    <Button variant="secondary" size="sm" disabled={index === categories.length - 1 || reordering} onClick={() => void move(index, 1)} aria-label={t('adx.j_down')}>
                      <ArrowDown size={15} aria-hidden="true" />
                    </Button>
                    <Button variant="secondary" size="sm" className="min-w-[7rem] flex-1 whitespace-nowrap" onClick={() => openEdit(category)}><Pencil size={15} aria-hidden="true" /> {t('common.edit')}</Button>
                    <span className="flex min-w-[7rem] flex-1" title={(category.demarches_count ?? 0) > 0 ? t('catx.delete_blocked', { n: category.demarches_count ?? 0 }) : undefined}>
                    <Button variant="danger" size="sm" className="w-full whitespace-nowrap" disabled={(category.demarches_count ?? 0) > 0} onClick={() => void remove(category)}>
                      <Trash2 size={15} aria-hidden="true" /> {t('common.delete')}
                    </Button>
                    </span>
                  </div>
                </article>
              ))}
            </div>
          )}
        </div>
      </main>
    </div>
  );
}
