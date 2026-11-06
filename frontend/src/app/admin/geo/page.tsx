'use client';

import { FormEvent, useCallback, useEffect, useState } from 'react';
import { adminInput, adminLabel, AdminNotice, AdminShell, dangerButton, primaryButton, smallButton } from '@/components/admin/AdminShell';
import { useAdminDemarches } from '@/hooks/useAdminDemarches';
import { fetchApi } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

const LEVELS = ['province', 'department', 'commune', 'locality'] as const;
const UNIT_STATUSES = ['official', 'to_verify'] as const;
const SERVICE_STATUSES = ['confirmed', 'press_reported', 'to_verify'] as const;
const SUGGESTION_STATUSES = ['new', 'accepted', 'rejected', 'duplicate'] as const;

interface Unit {
  id: number; parent_id: number | null; level: (typeof LEVELS)[number]; name: string; name_ar: string | null; slug: string;
  status: (typeof UNIT_STATUSES)[number]; source_label: string | null; source_url: string | null; verified_on: string | null;
  is_active: boolean; path: string;
}
interface Service {
  id: number; office_id: number; office: string | null; city: string | null; demarche_id: number; demarche: string | null;
  status: (typeof SERVICE_STATUSES)[number]; source_label: string | null; source_url: string | null; verified_on: string | null;
}
interface Suggestion {
  id: number; name: string; province: string | null; comment: string | null; contact_email: string | null;
  status: (typeof SUGGESTION_STATUSES)[number]; admin_note: string | null; created_at: string | null;
}
interface OfficeOption { id: number; name: string; city?: { name: string } | null }

const DATE = /^\d{4}-\d{2}-\d{2}$/;

/** Niveau de rattachement autorisé pour chaque niveau (même règle que le serveur). */
const ALLOWED_PARENTS: Record<(typeof LEVELS)[number], string[]> = {
  province: [],
  department: ['province'],
  commune: ['department'],
  locality: ['province', 'department', 'commune'],
};

/**
 * Référentiel géographique : unités, services des bureaux et localités signalées.
 * Toute information est datée et sourcée ; le statut « officiel » ou « confirmé » exige une source. Le serveur contrôle aussi.
 */
export default function GeoAdminPage() {
  const { t } = useLanguage();
  const demarches = useAdminDemarches();
  const [units, setUnits] = useState<Unit[]>([]);
  const [services, setServices] = useState<Service[]>([]);
  const [suggestions, setSuggestions] = useState<Suggestion[]>([]);
  const [offices, setOffices] = useState<OfficeOption[]>([]);
  const [loaded, setLoaded] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const load = useCallback(() => {
    Promise.all([
      fetchApi<{ data: { units: Unit[]; services: Service[] } }>('/admin/geo'),
      fetchApi<{ data: Suggestion[] }>('/admin/geo/suggestions'),
      fetchApi<{ data: OfficeOption[] }>('/admin/offices'),
    ])
      .then(([geo, sug, off]) => {
        setUnits(geo.data.units);
        setServices(geo.data.services);
        setSuggestions(sug.data);
        setOffices(off.data);
        setLoaded(true);
      })
      .catch((e: unknown) => { setError(e instanceof Error ? e.message : t('common.error_generic')); setLoaded(true); });
  }, [t]);

  useEffect(load, [load]);

  const run = async (action: () => Promise<unknown>) => {
    setError('');
    setMessage('');
    try {
      await action();
      setMessage(t('covx.admin_saved'));
      load();
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : t('common.error_generic'));
    }
  };

  return (
    <AdminShell title={t('covx.admin_title')} intro={t('covx.admin_intro')}>
      <AdminNotice message={message} error={error} />
      {!loaded && <p role="status" className="text-slate-500">{t('adx.loading')}</p>}

      <ImportSection onDone={load} />

      <UnitsSection units={units}
        onNew={(body) => run(() => fetchApi('/admin/geo/units', { method: 'POST', body: JSON.stringify(body) }))}
        onSave={(unit, body) => run(() => fetchApi(`/admin/geo/units/${unit.id}`, { method: 'PUT', body: JSON.stringify(body) }))} />

      <ServicesSection services={services} offices={offices} demarches={demarches}
        onNew={(body) => run(() => fetchApi('/admin/geo/services', { method: 'POST', body: JSON.stringify(body) }))}
        onSave={(service, body) => run(() => fetchApi(`/admin/geo/services/${service.id}`, { method: 'PUT', body: JSON.stringify(body) }))}
        onDelete={(service) => run(() => fetchApi(`/admin/geo/services/${service.id}`, { method: 'DELETE' }))} />

      <SuggestionsSection suggestions={suggestions}
        onReview={(item, body) => run(() => fetchApi(`/admin/geo/suggestions/${item.id}`, { method: 'PUT', body: JSON.stringify(body) }))} />
    </AdminShell>
  );
}

interface ImportRow { line: number; data: Record<string, string | null>; problems: string[] }
interface ImportAnalysis { rows: ImportRow[]; total: number; errors: number; valid: number }

/** Parse un CSV simple (séparateur virgule, première ligne = en-têtes). */
function parseCsv(text: string): Array<Record<string, string>> {
  const lines = text.split(/\r?\n/).map((l) => l.trim()).filter(Boolean);
  if (lines.length < 2) return [];
  const headers = lines[0].split(',').map((h) => h.trim());
  return lines.slice(1).map((line) => {
    const cells = line.split(',').map((c) => c.trim());
    return Object.fromEntries(headers.map((h, i) => [h, cells[i] ?? '']));
  });
}

/** Import par aperçu : rien n'est enregistré tant que la vérification n'est pas faite, et un seul lot invalide bloque tout. */
function ImportSection({ onDone }: { onDone: () => void }) {
  const { t } = useLanguage();
  const [text, setText] = useState('');
  const [analysis, setAnalysis] = useState<ImportAnalysis | null>(null);
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');

  const rowsFromText = () => parseCsv(text);

  const preview = async () => {
    setError('');
    setMessage('');
    try {
      const res = await fetchApi<ImportAnalysis>('/admin/geo/import/preview', { method: 'POST', body: JSON.stringify({ rows: rowsFromText() }) });
      setAnalysis(res);
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : t('common.error_generic'));
    }
  };

  const commit = async () => {
    setError('');
    try {
      const res = await fetchApi<{ created: number }>('/admin/geo/import/commit', { method: 'POST', body: JSON.stringify({ rows: rowsFromText() }) });
      setMessage(`${t('qualx.import_done')} ${res.created}`);
      setAnalysis(null);
      setText('');
      onDone();
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : t('common.error_generic'));
    }
  };

  return (
    <section className="mt-8 rounded-2xl border border-slate-200 bg-white p-4">
      <h2 className="text-lg font-bold text-slate-950">{t('qualx.import_title')}</h2>
      <p className="mt-1 text-sm text-slate-600">{t('qualx.import_help')}</p>
      <textarea aria-label={t('qualx.import_title')} value={text} onChange={(e) => { setText(e.target.value); setAnalysis(null); }} rows={5}
        className="mt-3 w-full rounded-lg border border-slate-300 p-2 font-mono text-xs" />
      <div className="mt-2 flex flex-wrap gap-2">
        <button type="button" className={primaryButton} onClick={preview} disabled={!text.trim()}>{t('qualx.import_preview')}</button>
        {analysis && analysis.valid > 0 && <button type="button" className={smallButton} onClick={commit} disabled={analysis.errors > 0}>{t('qualx.import_commit')}</button>}
      </div>
      <AdminNotice message={message} error={error} />
      {analysis && (
        <>
          <p className="mt-3 text-sm font-semibold">{t('qualx.import_summary', { total: analysis.total, valid: analysis.valid, errors: analysis.errors })}</p>
          <ul className="mt-2 space-y-1 text-sm">
            {analysis.rows.map((row) => (
              <li key={row.line} className={row.problems.length ? 'text-red-800' : 'text-emerald-800'}>
                {t('qualx.import_line')} {row.line} · {row.data.name || '—'} {row.problems.length ? `· ${t('qualx.import_problems')} : ${row.problems.join(' ')}` : '✓'}
              </li>
            ))}
          </ul>
        </>
      )}
    </section>
  );
}

function UnitsSection({ units, onNew, onSave }: { units: Unit[]; onNew: (body: Record<string, unknown>) => void; onSave: (unit: Unit, body: Record<string, unknown>) => void }) {
  const { t } = useLanguage();
  const [level, setLevel] = useState<(typeof LEVELS)[number]>('locality');
  const [parentId, setParentId] = useState('');
  const [name, setName] = useState('');
  const [status, setStatus] = useState<(typeof UNIT_STATUSES)[number]>('to_verify');
  const [sourceUrl, setSourceUrl] = useState('');
  const [sourceLabel, setSourceLabel] = useState('');
  const [verifiedOn, setVerifiedOn] = useState('');
  const [localError, setLocalError] = useState('');

  const parents = units.filter((u) => ALLOWED_PARENTS[level].includes(u.level));

  const submit = (event: FormEvent) => {
    event.preventDefault();
    if (status === 'official' && (!sourceUrl || !DATE.test(verifiedOn))) {
      setLocalError(t('covx.admin_confirmed_hint'));
      return;
    }
    setLocalError('');
    onNew({ level, parent_id: parentId ? Number(parentId) : null, name, status, source_url: sourceUrl || null, source_label: sourceLabel || null, verified_on: verifiedOn || null });
    setName('');
  };

  return (
    <section className="mt-8">
      <h2 className="text-lg font-bold text-slate-950">{t('covx.admin_units')}</h2>
      <form onSubmit={submit} className="mt-3 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-3">
        <h3 className="font-semibold text-slate-800 sm:col-span-3">{t('covx.admin_add_unit')}</h3>
        <label className={adminLabel}>{t('covx.admin_level')}
          <select className={adminInput} value={level} onChange={(e) => { setLevel(e.target.value as (typeof LEVELS)[number]); setParentId(''); }}>
            {LEVELS.map((l) => <option key={l} value={l}>{l}</option>)}
          </select>
        </label>
        <label className={adminLabel}>{t('covx.admin_parent')}
          <select className={adminInput} value={parentId} onChange={(e) => setParentId(e.target.value)} disabled={level === 'province'}>
            <option value="">—</option>
            {parents.map((u) => <option key={u.id} value={u.id}>{u.path}</option>)}
          </select>
        </label>
        <label className={adminLabel}>{t('covx.admin_name')}
          <input className={adminInput} required value={name} onChange={(e) => setName(e.target.value)} />
        </label>
        <label className={adminLabel}>{t('covx.admin_status')}
          <select className={adminInput} value={status} onChange={(e) => setStatus(e.target.value as (typeof UNIT_STATUSES)[number])}>
            {UNIT_STATUSES.map((s) => <option key={s} value={s}>{s === 'official' ? t('covx.status_official') : t('covx.status_to_verify')}</option>)}
          </select>
        </label>
        <label className={adminLabel}>{t('covx.admin_source')}
          <input className={adminInput} type="url" value={sourceUrl} onChange={(e) => setSourceUrl(e.target.value)} />
        </label>
        <label className={adminLabel}>{t('covx.admin_source_label')}
          <input className={adminInput} value={sourceLabel} onChange={(e) => setSourceLabel(e.target.value)} />
        </label>
        <label className={adminLabel}>{t('covx.admin_verified')}
          <input className={adminInput} type="date" value={verifiedOn} onChange={(e) => setVerifiedOn(e.target.value)} />
        </label>
        {localError && <p role="alert" className="text-sm text-red-700 sm:col-span-3">{localError}</p>}
        <div className="sm:col-span-3"><button type="submit" className={primaryButton}>{t('covx.admin_add_unit')}</button></div>
      </form>

      <div className="mt-4 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table className="w-full min-w-[48rem] text-sm">
          <thead className="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
              <th className="px-3 py-2 text-start">{t('covx.admin_name')}</th>
              <th className="px-3 py-2 text-start">{t('covx.admin_level')}</th>
              <th className="px-3 py-2 text-start">{t('covx.admin_status')}</th>
              <th className="px-3 py-2 text-start">{t('covx.admin_source')}</th>
              <th className="px-3 py-2 text-start">{t('covx.admin_verified')}</th>
              <th />
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {units.length === 0 && <tr><td colSpan={6} className="px-3 py-4 text-slate-500">{t('covx.admin_none')}</td></tr>}
            {units.map((unit) => <UnitRow key={unit.id} unit={unit} onSave={onSave} />)}
          </tbody>
        </table>
      </div>
    </section>
  );
}

function UnitRow({ unit, onSave }: { unit: Unit; onSave: (unit: Unit, body: Record<string, unknown>) => void }) {
  const { t } = useLanguage();
  const [status, setStatus] = useState(unit.status);
  const [sourceUrl, setSourceUrl] = useState(unit.source_url ?? '');
  const [verifiedOn, setVerifiedOn] = useState(unit.verified_on ?? '');
  const [rowError, setRowError] = useState('');

  const save = () => {
    if (status === 'official' && (!sourceUrl || !DATE.test(verifiedOn))) {
      setRowError(t('covx.admin_confirmed_hint'));
      return;
    }
    setRowError('');
    onSave(unit, { name: unit.name, status, source_url: sourceUrl || null, source_label: unit.source_label, verified_on: verifiedOn || null, is_active: unit.is_active });
  };

  return (
    <tr>
      <td className="px-3 py-2 font-semibold text-slate-900">{unit.name}<span className="block text-xs font-normal text-slate-500">{unit.path}</span></td>
      <td className="px-3 py-2 text-slate-700">{unit.level}</td>
      <td className="px-3 py-2">
        <select aria-label={t('covx.admin_status')} className={adminInput} value={status} onChange={(e) => setStatus(e.target.value as Unit['status'])}>
          {UNIT_STATUSES.map((s) => <option key={s} value={s}>{s === 'official' ? t('covx.status_official') : t('covx.status_to_verify')}</option>)}
        </select>
      </td>
      <td className="px-3 py-2"><input aria-label={t('covx.admin_source')} type="url" className={adminInput} value={sourceUrl} onChange={(e) => setSourceUrl(e.target.value)} /></td>
      <td className="px-3 py-2"><input aria-label={t('covx.admin_verified')} type="date" className={adminInput} value={verifiedOn} onChange={(e) => setVerifiedOn(e.target.value)} /></td>
      <td className="px-3 py-2">
        <button type="button" onClick={save} className={smallButton}>{t('covx.admin_save')}</button>
        {rowError && <p role="alert" className="mt-1 text-xs text-red-700">{rowError}</p>}
      </td>
    </tr>
  );
}

function ServicesSection({ services, offices, demarches, onNew, onSave, onDelete }: {
  services: Service[]; offices: OfficeOption[]; demarches: Array<{ id: number; title: string }>;
  onNew: (body: Record<string, unknown>) => void; onSave: (service: Service, body: Record<string, unknown>) => void; onDelete: (service: Service) => void;
}) {
  const { t } = useLanguage();
  // Filtre par démarche : ouvert depuis la page qualité (« Bureaux de cette fiche »).
  const [demarcheFilter, setDemarcheFilter] = useState('');
  const [officeId, setOfficeId] = useState('');
  const [demarcheId, setDemarcheId] = useState('');
  useEffect(() => {
    const fromUrl = new URLSearchParams(window.location.search).get('demarche') ?? '';
    setDemarcheFilter(fromUrl);
    setDemarcheId(fromUrl);
  }, []);
  const visibleServices = demarcheFilter ? services.filter((s) => String(s.demarche_id) === demarcheFilter) : services;
  const [status, setStatus] = useState<Service['status']>('to_verify');
  const [sourceUrl, setSourceUrl] = useState('');
  const [verifiedOn, setVerifiedOn] = useState('');
  const [localError, setLocalError] = useState('');

  const submit = (event: FormEvent) => {
    event.preventDefault();
    if (status === 'confirmed' && !DATE.test(verifiedOn)) { setLocalError(t('covx.admin_confirmed_hint')); return; }
    setLocalError('');
    onNew({ office_id: Number(officeId), demarche_id: Number(demarcheId), status, source_url: sourceUrl || null, verified_on: verifiedOn || null });
  };

  return (
    <section className="mt-10">
      <h2 className="text-lg font-bold text-slate-950">{t('covx.admin_services')}</h2>
      <form onSubmit={submit} className="mt-3 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-3">
        <label className={adminLabel}>{t('covx.admin_office')}
          <select className={adminInput} required value={officeId} onChange={(e) => setOfficeId(e.target.value)}>
            <option value="">—</option>
            {offices.map((o) => <option key={o.id} value={o.id}>{o.name}{o.city ? ` · ${o.city.name}` : ''}</option>)}
          </select>
        </label>
        <label className={adminLabel}>{t('covx.admin_demarche')}
          <select className={adminInput} required value={demarcheId} onChange={(e) => setDemarcheId(e.target.value)}>
            <option value="">—</option>
            {demarches.map((d) => <option key={d.id} value={d.id}>{d.title}</option>)}
          </select>
        </label>
        <label className={adminLabel}>{t('covx.admin_service_status')}
          <select className={adminInput} value={status} onChange={(e) => setStatus(e.target.value as Service['status'])}>
            {SERVICE_STATUSES.map((s) => <option key={s} value={s}>{t(`covx.svc_${s === 'press_reported' ? 'press' : s === 'confirmed' ? 'confirmed' : 'to_verify'}`)}</option>)}
          </select>
        </label>
        <label className={adminLabel}>{t('covx.admin_source')}
          <input className={adminInput} type="url" value={sourceUrl} onChange={(e) => setSourceUrl(e.target.value)} />
        </label>
        <label className={adminLabel}>{t('covx.admin_verified')}
          <input className={adminInput} type="date" value={verifiedOn} onChange={(e) => setVerifiedOn(e.target.value)} />
        </label>
        {localError && <p role="alert" className="text-sm text-red-700 sm:col-span-3">{localError}</p>}
        <p className="text-xs text-slate-600 sm:col-span-3">{t('covx.admin_confirmed_hint')}</p>
        <div className="sm:col-span-3"><button type="submit" className={primaryButton}>{t('covx.admin_services')}</button></div>
      </form>

      <div className="mt-4 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table className="w-full min-w-[48rem] text-sm">
          <thead className="bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
              <th className="px-3 py-2 text-start">{t('covx.admin_office')}</th>
              <th className="px-3 py-2 text-start">{t('covx.admin_demarche')}</th>
              <th className="px-3 py-2 text-start">{t('covx.admin_service_status')}</th>
              <th className="px-3 py-2 text-start">{t('covx.admin_source')}</th>
              <th className="px-3 py-2 text-start">{t('covx.admin_verified')}</th>
              <th />
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {visibleServices.length === 0 && <tr><td colSpan={6} className="px-3 py-4 text-slate-500">{t('covx.admin_none')}</td></tr>}
            {visibleServices.map((service) => <ServiceRow key={service.id} service={service} onSave={onSave} onDelete={onDelete} />)}
          </tbody>
        </table>
      </div>
    </section>
  );
}

function ServiceRow({ service, onSave, onDelete }: { service: Service; onSave: (service: Service, body: Record<string, unknown>) => void; onDelete: (service: Service) => void }) {
  const { t } = useLanguage();
  const [status, setStatus] = useState(service.status);
  const [sourceUrl, setSourceUrl] = useState(service.source_url ?? '');
  const [verifiedOn, setVerifiedOn] = useState(service.verified_on ?? '');
  const [rowError, setRowError] = useState('');

  const save = () => {
    if (status === 'confirmed' && !DATE.test(verifiedOn)) { setRowError(t('covx.admin_confirmed_hint')); return; }
    setRowError('');
    onSave(service, { status, source_url: sourceUrl || null, verified_on: verifiedOn || null });
  };

  return (
    <tr>
      <td className="px-3 py-2 text-slate-900">{service.office}<span className="block text-xs text-slate-500">{service.city}</span></td>
      <td className="px-3 py-2 text-slate-700">{service.demarche}</td>
      <td className="px-3 py-2">
        <select aria-label={t('covx.admin_service_status')} className={adminInput} value={status} onChange={(e) => setStatus(e.target.value as Service['status'])}>
          {SERVICE_STATUSES.map((s) => <option key={s} value={s}>{t(`covx.svc_${s === 'press_reported' ? 'press' : s === 'confirmed' ? 'confirmed' : 'to_verify'}`)}</option>)}
        </select>
      </td>
      <td className="px-3 py-2"><input aria-label={t('covx.admin_source')} type="url" className={adminInput} value={sourceUrl} onChange={(e) => setSourceUrl(e.target.value)} /></td>
      <td className="px-3 py-2"><input aria-label={t('covx.admin_verified')} type="date" className={adminInput} value={verifiedOn} onChange={(e) => setVerifiedOn(e.target.value)} /></td>
      <td className="whitespace-nowrap px-3 py-2">
        <button type="button" onClick={save} className={smallButton}>{t('covx.admin_save')}</button>{' '}
        <button type="button" onClick={() => onDelete(service)} className={dangerButton}>✕</button>
        {rowError && <p role="alert" className="mt-1 text-xs text-red-700">{rowError}</p>}
      </td>
    </tr>
  );
}

function SuggestionsSection({ suggestions, onReview }: { suggestions: Suggestion[]; onReview: (item: Suggestion, body: Record<string, unknown>) => void }) {
  const { t } = useLanguage();
  return (
    <section className="mt-10">
      <h2 className="text-lg font-bold text-slate-950">{t('covx.admin_suggestions')}</h2>
      <p className="mt-1 text-sm text-slate-600">{t('covx.admin_accept_hint')}</p>
      <ul className="mt-3 space-y-3">
        {suggestions.length === 0 && <li className="text-slate-500">{t('covx.admin_none')}</li>}
        {suggestions.map((item) => <SuggestionRow key={item.id} item={item} onReview={onReview} />)}
      </ul>
    </section>
  );
}

function SuggestionRow({ item, onReview }: { item: Suggestion; onReview: (item: Suggestion, body: Record<string, unknown>) => void }) {
  const { t } = useLanguage();
  const [status, setStatus] = useState(item.status);
  const [note, setNote] = useState(item.admin_note ?? '');
  return (
    <li className="rounded-xl border border-slate-200 bg-white p-4">
      <p className="font-semibold text-slate-900">{item.name}{item.province && <span className="font-normal text-slate-500"> · {item.province}</span>}</p>
      {item.comment && <p className="mt-1 text-sm text-slate-700">{item.comment}</p>}
      {item.contact_email && <p className="mt-1 text-xs text-slate-500">{t('covx.admin_contact')} : {item.contact_email}</p>}
      <div className="mt-3 flex flex-wrap items-end gap-2">
        <select aria-label={t('covx.admin_status')} className={adminInput} value={status} onChange={(e) => setStatus(e.target.value as Suggestion['status'])}>
          {SUGGESTION_STATUSES.map((s) => <option key={s} value={s}>{t(`covx.admin_status_${s}`)}</option>)}
        </select>
        <input aria-label={t('covx.admin_note')} className={adminInput} value={note} onChange={(e) => setNote(e.target.value)} />
        <button type="button" onClick={() => onReview(item, { status, admin_note: note || null })} className={smallButton}>{t('covx.admin_review')}</button>
      </div>
    </li>
  );
}
