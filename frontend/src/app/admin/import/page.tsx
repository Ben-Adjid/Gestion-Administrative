'use client';

import { FormEvent, useState } from 'react';
import { Download, FileCheck2, Upload } from 'lucide-react';
import { adminInput, adminLabel, AdminNotice, AdminShell, primaryButton, secondaryButton } from '@/components/admin/AdminShell';
import { API_V1_URL, downloadAdminFile } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

// Colonnes du modèle (mêmes noms que côté serveur : App\Services\DemarcheImporter::COLUMNS)
const COLUMNS: Array<[string, boolean]> = [
  ['titre', true], ['categorie', true], ['institution', true], ['cout', true], ['description', true],
  ['delai', false], ['resume', false], ['pieces', false], ['etapes', false], ['conditions', false],
  ['lieu', false], ['telephone', false], ['email', false], ['source', false],
];

interface Report {
  rows: number;
  valid: number;
  errors: Array<{ line: number; messages: string[] }>;
  preview: Array<{ line: number; title: string; cost: string }>;
  created: number;
}

/** Import de démarches depuis Excel/CSV : vérification ligne par ligne, puis création en brouillon. */
export default function ImportPage() {
  const { language, t } = useLanguage();
  const [file, setFile] = useState<File | null>(null);
  const [report, setReport] = useState<Report | null>(null);
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  // Envoi multipart : fetchApi impose du JSON, on appelle donc l'API directement avec le jeton admin.
  const send = async (dryRun: boolean) => {
    if (!file) return;
    setBusy(true);
    setError('');
    setMessage('');
    try {
      const body = new FormData();
      body.append('file', file);
      if (dryRun) body.append('dry_run', '1');
      const res = await fetch(`${API_V1_URL}/admin/import`, {
        method: 'POST',
        body,
        headers: { Accept: 'application/json', 'Accept-Language': language, Authorization: `Bearer ${localStorage.getItem('admin_token') || ''}` },
      });
      const json = await res.json().catch(() => ({}));
      if (!res.ok) {
        const first = json.errors ? Object.values(json.errors as Record<string, string[]>)[0]?.[0] : null;
        throw new Error(first || json.message || t('common.error_generic'));
      }
      setReport(json.data);
      if (!dryRun && json.data.created > 0) {
        setMessage(t('adx.i_done', { n: json.data.created }));
        setFile(null);
      }
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : t('common.error_generic'));
    } finally {
      setBusy(false);
    }
  };

  const check = (e: FormEvent) => {
    e.preventDefault();
    setReport(null);
    send(true);
  };

  const template = () => downloadAdminFile('/admin/import/template', 'modele-import-demarches.xlsx').catch(() => setError(t('common.error_generic')));

  return (
    <AdminShell
      title={t('adx.i_title')}
      intro={t('adx.i_intro')}
      actions={<button type="button" onClick={template} className={secondaryButton}><Download size={16} aria-hidden="true" /> {t('adx.i_template')}</button>}
    >
      <AdminNotice message={message} error={error} />

      <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
        <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
          <form onSubmit={check} className="space-y-4">
            <label className={adminLabel}>{t('adx.i_file')}
              <input type="file" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" onChange={(e) => { setFile(e.target.files?.[0] ?? null); setReport(null); }} className={adminInput} />
            </label>
            <button type="submit" disabled={!file || busy} className={primaryButton}>
              <FileCheck2 size={16} aria-hidden="true" /> {busy ? t('adx.i_checking') : t('adx.i_check')}
            </button>
          </form>

          {report && (
            <div className="mt-6 space-y-4">
              <p className="flex flex-wrap gap-4 text-sm font-semibold">
                <span className="text-slate-700">{t('adx.i_rows', { n: report.rows })}</span>
                <span className="text-emerald-700">{t('adx.i_valid', { n: report.valid })}</span>
              </p>

              {report.errors.length > 0 && (
                <div className="rounded-xl border border-red-200 bg-red-50 p-4">
                  <p className="font-bold text-red-900">{t('adx.i_errors')}</p>
                  <ul className="mt-2 space-y-1 text-sm text-red-800">
                    {report.errors.map((item) => <li key={item.line}><strong>{t('adx.i_line', { n: item.line })}</strong> : {item.messages.join(' ')}</li>)}
                  </ul>
                  <p className="mt-3 text-sm text-red-900">{t('adx.i_fix_first')}</p>
                </div>
              )}

              {report.preview.length > 0 && report.created === 0 && (
                <div>
                  <p className="text-sm font-bold text-slate-800">{t('adx.i_preview')}</p>
                  <ul className="mt-2 divide-y divide-slate-100 rounded-xl border border-slate-200 text-sm">
                    {report.preview.map((row) => (
                      <li key={row.line} className="flex justify-between gap-4 px-3 py-2"><span>{row.title}</span><span className="text-slate-500">{row.cost}</span></li>
                    ))}
                  </ul>
                </div>
              )}

              {report.errors.length === 0 && report.valid > 0 && report.created === 0 && (
                <button type="button" disabled={busy} onClick={() => send(false)} className={primaryButton}>
                  <Upload size={16} aria-hidden="true" /> {t('adx.i_import', { n: report.valid })}
                </button>
              )}
            </div>
          )}
        </section>

        <aside className="rounded-2xl border border-slate-200 bg-white p-5 text-sm shadow-sm">
          <p className="font-bold text-slate-900">{t('adx.i_columns')}</p>
          <ul className="mt-3 space-y-1.5">
            {COLUMNS.map(([name, required]) => (
              <li key={name} className="flex flex-wrap gap-x-2">
                <code className="font-semibold text-slate-800">{name}</code>
                {required && <span className="text-xs font-semibold text-red-700">({t('adx.i_required')})</span>}
                {name === 'categorie' && <span className="text-xs text-slate-500">— {t('adx.i_category_hint')}</span>}
              </li>
            ))}
          </ul>
          <p className="mt-4 text-xs text-slate-500">{t('adx.i_lists_hint')}</p>
        </aside>
      </div>
    </AdminShell>
  );
}
