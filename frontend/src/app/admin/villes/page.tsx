'use client';

import { FormEvent, useCallback, useEffect, useState } from 'react';
import { MapPin, Pencil, Plus, Trash2 } from 'lucide-react';
import {
  adminInput, adminLabel, AdminModal, AdminNotice, AdminShell, dangerButton, primaryButton, secondaryButton, smallButton, TrilingualField,
} from '@/components/admin/AdminShell';
import { fetchApi, splitFormErrors } from '@/lib/api';
import { useAdminDemarches } from '@/hooks/useAdminDemarches';
import { useLanguage } from '@/hooks/useLanguage';
import { useConfirm } from '@/components/ConfirmProvider';
import type { Office } from '@/types';

interface CityRow { id: number; name: string; name_ar: string | null; slug: string; province: string | null; is_active: boolean; display_order: number; offices_count: number }

const EMPTY_CITY = { name: '', name_ar: '', province: '', display_order: 10, is_active: true };
const EMPTY_OFFICE = {
  city_id: '', name: { fr: '', en: '', ar: '' }, address: '', address_ar: '', hours: { fr: '', en: '', ar: '' },
  phone: '', latitude: '', longitude: '', source_url: '', is_active: true, demarche_ids: [] as number[],
};
const CITY_FIELDS = ['name', 'name_ar', 'province', 'display_order'];
const OFFICE_FIELDS = ['source_url', 'city_id', 'name', 'name_en', 'name_ar', 'address', 'address_ar', 'opening_hours', 'opening_hours_en', 'opening_hours_ar', 'phone', 'latitude', 'longitude', 'demarche_ids'];

/** Villes et bureaux (guichets) où se font les démarches. */
export default function CitiesAdminPage() {
  const { t } = useLanguage();
  const confirm = useConfirm();
  const demarches = useAdminDemarches();
  const [cities, setCities] = useState<CityRow[]>([]);
  const [offices, setOffices] = useState<Office[]>([]);
  const [filter, setFilter] = useState('');
  const [loaded, setLoaded] = useState(false);
  const [cityEditing, setCityEditing] = useState<CityRow | 'new' | null>(null);
  const [officeEditing, setOfficeEditing] = useState<Office | 'new' | null>(null);
  const [cityForm, setCityForm] = useState(EMPTY_CITY);
  const [officeForm, setOfficeForm] = useState(EMPTY_OFFICE);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const load = useCallback(() => {
    Promise.all([
      fetchApi<{ data: CityRow[] }>('/admin/cities'),
      fetchApi<{ data: Office[] }>('/admin/offices'),
    ])
      .then(([c, o]) => { setCities(c.data); setOffices(o.data); setLoaded(true); })
      .catch((e: unknown) => { setError(e instanceof Error ? e.message : t('common.error_generic')); setLoaded(true); });
  }, [t]);

  useEffect(load, [load]);

  const handle = async (action: () => Promise<unknown>, fields: string[], close: () => void, done: string) => {
    setSaving(true);
    setErrors({});
    setError('');
    try {
      await action();
      close();
      setMessage(done);
      load();
    } catch (err: unknown) {
      const { fieldErrors, general } = splitFormErrors(err, fields, t('common.error_generic'));
      setErrors(fieldErrors);
      if (general) setError(general);
    } finally {
      setSaving(false);
    }
  };

  // ------------------------------------------------------------ villes
  const openCity = (city: CityRow | 'new') => {
    setErrors({});
    setError('');
    setCityForm(city === 'new' ? EMPTY_CITY : { name: city.name, name_ar: city.name_ar || '', province: city.province || '', display_order: city.display_order, is_active: city.is_active });
    setCityEditing(city);
  };

  const saveCity = (e: FormEvent) => {
    e.preventDefault();
    const body = JSON.stringify({ ...cityForm, name_ar: cityForm.name_ar || null, province: cityForm.province || null, display_order: Number(cityForm.display_order) || 0 });
    handle(
      () => (cityEditing === 'new' ? fetchApi('/admin/cities', { method: 'POST', body }) : fetchApi(`/admin/cities/${(cityEditing as CityRow).id}`, { method: 'PUT', body })),
      CITY_FIELDS, () => setCityEditing(null), t('adx.saved'),
    );
  };

  const removeCity = async (city: CityRow) => {
    if (!(await confirm({ title: t('adx.confirm_delete', { name: city.name }), tone: 'danger' }))) return;
    handle(() => fetchApi(`/admin/cities/${city.id}`, { method: 'DELETE' }), [], () => undefined, t('adx.deleted'));
  };

  // ------------------------------------------------------------ bureaux
  const openOffice = (office: Office | 'new') => {
    setErrors({});
    setError('');
    if (office === 'new') {
      setOfficeForm({ ...EMPTY_OFFICE, city_id: filter ? String(cities.find((c) => c.slug === filter)?.id ?? '') : '' });
    } else {
      const raw = office.raw ?? {};
      setOfficeForm({
        city_id: String(raw.city_id ?? ''),
        name: { fr: String(raw.name ?? ''), en: String(raw.name_en ?? ''), ar: String(raw.name_ar ?? '') },
        address: String(raw.address ?? ''), address_ar: String(raw.address_ar ?? ''),
        hours: { fr: String(raw.opening_hours ?? ''), en: String(raw.opening_hours_en ?? ''), ar: String(raw.opening_hours_ar ?? '') },
        phone: office.phone || '', latitude: office.latitude?.toString() ?? '', longitude: office.longitude?.toString() ?? '', source_url: office.source_url || '',
        is_active: office.is_active, demarche_ids: office.demarche_ids ?? [],
      });
    }
    setOfficeEditing(office);
  };

  const saveOffice = (e: FormEvent) => {
    e.preventDefault();
    const f = officeForm;
    const body = JSON.stringify({
      city_id: f.city_id ? Number(f.city_id) : null,
      name: f.name.fr, name_en: f.name.en || null, name_ar: f.name.ar || null,
      address: f.address || null, address_ar: f.address_ar || null,
      opening_hours: f.hours.fr || null, opening_hours_en: f.hours.en || null, opening_hours_ar: f.hours.ar || null,
      phone: f.phone || null,
      latitude: f.latitude === '' ? null : Number(f.latitude.replace(',', '.')),
      longitude: f.longitude === '' ? null : Number(f.longitude.replace(',', '.')),
      source_url: f.source_url || null,
      is_active: f.is_active, demarche_ids: f.demarche_ids,
    });
    handle(
      () => (officeEditing === 'new' ? fetchApi('/admin/offices', { method: 'POST', body }) : fetchApi(`/admin/offices/${(officeEditing as Office).id}`, { method: 'PUT', body })),
      OFFICE_FIELDS, () => setOfficeEditing(null), t('adx.saved'),
    );
  };

  const removeOffice = async (office: Office) => {
    if (!(await confirm({ title: t('adx.confirm_delete', { name: office.name }), tone: 'danger' }))) return;
    handle(() => fetchApi(`/admin/offices/${office.id}`, { method: 'DELETE' }), [], () => undefined, t('adx.deleted'));
  };

  const toggleDemarche = (id: number) => setOfficeForm((f) => ({
    ...f, demarche_ids: f.demarche_ids.includes(id) ? f.demarche_ids.filter((d) => d !== id) : [...f.demarche_ids, id],
  }));

  const visibleOffices = filter ? offices.filter((o) => o.city?.slug === filter) : offices;
  const err = (name: string) => errors[name]?.[0] && <p className="mt-1 text-sm text-red-700">{errors[name][0]}</p>;
  const modalError = error && <p role="alert" className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>;

  return (
    <AdminShell title={t('adx.c_title')} intro={t('adx.c_intro')}>
      <AdminNotice message={message} error={cityEditing || officeEditing ? '' : error} />
      {!loaded && <p role="status" className="text-slate-500">{t('adx.loading')}</p>}

      <div className="grid gap-6 lg:grid-cols-[18rem_1fr]">
        <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div className="mb-3 flex items-center justify-between">
            <h2 className="font-bold text-slate-900">{t('adx.c_cities')}</h2>
            <button type="button" onClick={() => openCity('new')} className={smallButton}><Plus size={14} aria-hidden="true" /> {t('adx.add')}</button>
          </div>
          <ul className="max-h-[32rem] space-y-1 overflow-y-auto">
            {cities.map((city) => (
              <li key={city.id} className={`flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 ${filter === city.slug ? 'bg-blue-50' : 'hover:bg-slate-50'}`}>
                <button type="button" onClick={() => setFilter(filter === city.slug ? '' : city.slug)} className="min-w-0 flex-1 text-start">
                  <span className={`block truncate text-sm font-semibold ${city.is_active ? 'text-slate-800' : 'text-slate-400 line-through'}`}>{city.name}</span>
                  <span className="block text-xs text-slate-500">{t('adx.c_offices', { n: city.offices_count })}</span>
                </button>
                <button type="button" onClick={() => openCity(city)} aria-label={`${t('adx.edit')} ${city.name}`} className="rounded p-1 text-slate-500 hover:bg-white hover:text-blue-700"><Pencil size={14} aria-hidden="true" /></button>
                <button type="button" onClick={() => removeCity(city)} aria-label={`${t('adx.delete')} ${city.name}`} className="rounded p-1 text-slate-500 hover:bg-white hover:text-red-700"><Trash2 size={14} aria-hidden="true" /></button>
              </li>
            ))}
          </ul>
        </section>

        <section>
          <div className="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 className="text-lg font-bold text-slate-900">{t('adx.o_title')}{filter && ` · ${cities.find((c) => c.slug === filter)?.name}`}</h2>
            <button type="button" onClick={() => openOffice('new')} className={primaryButton}><Plus size={16} aria-hidden="true" /> {t('adx.o_new')}</button>
          </div>
          {loaded && visibleOffices.length === 0 && <p className="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-slate-600">{t('adx.o_empty')}</p>}
          <ul className="grid gap-3 xl:grid-cols-2">
            {visibleOffices.map((office) => (
              <li key={office.id} className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <p className="flex items-start gap-2 font-bold text-slate-900">
                  <MapPin size={16} aria-hidden="true" className="mt-0.5 shrink-0 text-blue-700" />
                  <span>{office.name}<span className="font-normal text-slate-500"> · {office.city?.name}</span></span>
                </p>
                {office.address && <p className="mt-1 ps-6 text-sm text-slate-600">{office.address}</p>}
                <p className="mt-1 ps-6 text-xs text-slate-500">
                  {(office.demarche_ids ?? []).map((id) => demarches.find((d) => d.id === id)?.title).filter(Boolean).join(' · ') || '—'}
                </p>
                <div className="mt-3 flex gap-2 ps-6">
                  <button type="button" onClick={() => openOffice(office)} className={smallButton}><Pencil size={14} aria-hidden="true" /> {t('adx.edit')}</button>
                  <button type="button" onClick={() => removeOffice(office)} className={dangerButton}><Trash2 size={14} aria-hidden="true" /> {t('adx.delete')}</button>
                </div>
              </li>
            ))}
          </ul>
        </section>
      </div>

      {cityEditing && (
        <AdminModal title={cityEditing === 'new' ? t('adx.c_new_city') : t('adx.edit')} onClose={() => setCityEditing(null)}>
          <form onSubmit={saveCity} noValidate className="space-y-3">
            {modalError}
            <label className={adminLabel}>{t('adx.c_name')} *<input required maxLength={100} value={cityForm.name} onChange={(e) => setCityForm({ ...cityForm, name: e.target.value })} className={adminInput} />{err('name')}</label>
            <label className={adminLabel}>{t('adx.c_name_ar')}<input dir="rtl" lang="ar" maxLength={100} value={cityForm.name_ar} onChange={(e) => setCityForm({ ...cityForm, name_ar: e.target.value })} className={adminInput} /></label>
            <label className={adminLabel}>{t('adx.c_province')}<input maxLength={100} value={cityForm.province} onChange={(e) => setCityForm({ ...cityForm, province: e.target.value })} className={adminInput} /></label>
            <label className={adminLabel}>{t('adx.c_order')}<input type="number" min={0} max={1000} value={cityForm.display_order} onChange={(e) => setCityForm({ ...cityForm, display_order: Number(e.target.value) })} className={adminInput} /></label>
            <label className="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" checked={cityForm.is_active} onChange={(e) => setCityForm({ ...cityForm, is_active: e.target.checked })} className="h-4 w-4" /> {t('adx.visible')}</label>
            <div className="flex justify-end gap-2">
              <button type="button" onClick={() => setCityEditing(null)} className={secondaryButton}>{t('adx.cancel')}</button>
              <button type="submit" disabled={saving || !cityForm.name.trim()} className={primaryButton}>{t('adx.save')}</button>
            </div>
          </form>
        </AdminModal>
      )}

      {officeEditing && (
        <AdminModal title={officeEditing === 'new' ? t('adx.o_new') : t('adx.edit')} onClose={() => setOfficeEditing(null)} wide>
          <form onSubmit={saveOffice} noValidate className="space-y-4">
            {modalError}
            <label className={adminLabel}>{t('adx.o_city')} *
              <select required value={officeForm.city_id} onChange={(e) => setOfficeForm({ ...officeForm, city_id: e.target.value })} className={adminInput}>
                <option value="">—</option>
                {cities.map((city) => <option key={city.id} value={city.id}>{city.name}</option>)}
              </select>
              {err('city_id')}
            </label>
            <TrilingualField labelKey="adx.o_name" required maxLength={200} values={officeForm.name} onChange={(lang, value) => setOfficeForm({ ...officeForm, name: { ...officeForm.name, [lang]: value } })} />
            {err('name')}
            <div className="grid gap-3 sm:grid-cols-2">
              <label className={adminLabel}>{t('adx.o_address')}<input maxLength={255} value={officeForm.address} onChange={(e) => setOfficeForm({ ...officeForm, address: e.target.value })} className={adminInput} /></label>
              <label className={adminLabel}>{t('adx.o_address_ar')}<input dir="rtl" lang="ar" maxLength={255} value={officeForm.address_ar} onChange={(e) => setOfficeForm({ ...officeForm, address_ar: e.target.value })} className={adminInput} /></label>
            </div>
            <TrilingualField labelKey="adx.o_hours" maxLength={150} values={officeForm.hours} onChange={(lang, value) => setOfficeForm({ ...officeForm, hours: { ...officeForm.hours, [lang]: value } })} />
            <div className="grid gap-3 sm:grid-cols-3">
              <label className={adminLabel}>{t('adx.o_phone')}<input dir="ltr" maxLength={50} value={officeForm.phone} onChange={(e) => setOfficeForm({ ...officeForm, phone: e.target.value })} className={adminInput} /></label>
              <label className={adminLabel}>{t('adx.o_lat')}<input dir="ltr" inputMode="decimal" value={officeForm.latitude} onChange={(e) => setOfficeForm({ ...officeForm, latitude: e.target.value })} className={adminInput} />{err('latitude')}</label>
              <label className={adminLabel}>{t('adx.o_lng')}<input dir="ltr" inputMode="decimal" value={officeForm.longitude} onChange={(e) => setOfficeForm({ ...officeForm, longitude: e.target.value })} className={adminInput} />{err('longitude')}</label>
            </div>
            <p className="text-xs text-slate-500">{t('adx.o_coords_hint')}</p>
            <label className={adminLabel}>{t('adx.o_source')}
              <input type="url" dir="ltr" maxLength={255} value={officeForm.source_url} onChange={(e) => setOfficeForm({ ...officeForm, source_url: e.target.value })} placeholder="https://" className={adminInput} />
              {err('source_url')}
            </label>
            <fieldset>
              <legend className={adminLabel}>{t('adx.o_demarches')}</legend>
              <div className="mt-2 grid max-h-48 gap-1 overflow-y-auto rounded-lg border border-slate-200 p-2 sm:grid-cols-2">
                {demarches.map((d) => (
                  <label key={d.id} className="flex items-center gap-2 rounded px-2 py-1 text-sm hover:bg-slate-50">
                    <input type="checkbox" checked={officeForm.demarche_ids.includes(d.id)} onChange={() => toggleDemarche(d.id)} className="h-4 w-4" /> {d.title}
                  </label>
                ))}
              </div>
            </fieldset>
            <label className="flex items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" checked={officeForm.is_active} onChange={(e) => setOfficeForm({ ...officeForm, is_active: e.target.checked })} className="h-4 w-4" /> {t('adx.visible')}</label>
            <div className="flex justify-end gap-2">
              <button type="button" onClick={() => setOfficeEditing(null)} className={secondaryButton}>{t('adx.cancel')}</button>
              <button type="submit" disabled={saving || !officeForm.name.fr.trim() || !officeForm.city_id} className={primaryButton}>{t('adx.save')}</button>
            </div>
          </form>
        </AdminModal>
      )}
    </AdminShell>
  );
}
