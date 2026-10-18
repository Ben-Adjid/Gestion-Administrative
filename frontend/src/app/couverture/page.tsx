'use client';

import { useEffect, useState } from 'react';
import { API_V1_URL } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';
import { ListSkeleton } from '@/components/ListSkeleton';

interface CoverageRow {
  slug: string;
  name: string;
  province: string | null;
  status: 'official' | 'to_verify';
  offices_attested: number;
  demarches_documented: number;
  selectable: boolean;
}

interface Totals {
  localities: number;
  localities_with_attested_office: number;
  localities_without_attested_office: number;
  localities_to_verify: number;
  offices_attested: number;
  demarches_documented: number;
  services_confirmed: number;
  services_press_reported: number;
  departments: number;
  communes: number;
  official_units: number;
}

/**
 * Couverture publique : localités recensées, bureaux attestés et démarches documentées sont comptés séparément.
 * Une localité sans bureau attesté est affichée comme telle : jamais de bureau inventé pour la remplir.
 */
export default function CoveragePage() {
  const { t, language } = useLanguage();
  const [rows, setRows] = useState<CoverageRow[] | null>(null);
  const [totals, setTotals] = useState<Totals | null>(null);
  const [error, setError] = useState(false);

  useEffect(() => {
    let active = true;
    fetch(`${API_V1_URL}/geo/coverage?lang=${language}`, { headers: { Accept: 'application/json' } })
      .then((res) => { if (!res.ok) throw new Error(`HTTP ${res.status}`); return res.json(); })
      .then((json: { data: CoverageRow[]; totals: Totals }) => { if (active) { setRows(json.data); setTotals(json.totals); } })
      .catch(() => { if (active) setError(true); });
    return () => { active = false; };
  }, [language]);

  const cards: Array<[string, number]> = totals ? [
    [t('covx.t_localities'), totals.localities],
    [t('covx.t_with_office'), totals.localities_with_attested_office],
    [t('covx.t_without'), totals.localities_without_attested_office],
    [t('covx.t_offices'), totals.offices_attested],
    [t('covx.t_demarches'), totals.demarches_documented],
    [t('covx.t_to_verify'), totals.localities_to_verify],
    [t('covx.t_confirmed'), totals.services_confirmed],
    [t('covx.t_press'), totals.services_press_reported],
  ] : [];

  return (
    <main className="mx-auto max-w-5xl px-4 py-10">
      <h1 className="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{t('covx.title')}</h1>
      <p className="mt-3 text-lg text-slate-600">{t('covx.intro')}</p>

      {!rows && !error && <div className="mt-8"><ListSkeleton rows={6} /></div>}
      {error && <p role="alert" className="mt-8 rounded-2xl border border-red-200 bg-red-50 p-5 text-red-800">{t('common.error_generic')}</p>}

      {totals && (
        <dl className="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {cards.map(([label, value]) => (
            <div key={label} className="rounded-2xl border border-slate-200 bg-white p-4">
              <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</dt>
              <dd className="mt-1 text-2xl font-bold text-slate-950">{value}</dd>
            </div>
          ))}
        </dl>
      )}
      {totals && <p className="mt-3 text-sm text-amber-900">{t('covx.departments_missing')} ({totals.departments} / {totals.communes})</p>}

      {rows && rows.length === 0 && <p className="mt-8 rounded-2xl border border-dashed border-slate-300 p-6 text-slate-600">{t('covx.empty')}</p>}
      {rows && rows.length > 0 && (
        <div className="mt-8 overflow-x-auto rounded-2xl border border-slate-200 bg-white">
          <table className="w-full min-w-[36rem] text-start text-sm">
            <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
              <tr>
                <th scope="col" className="px-4 py-3 text-start">{t('covx.col_locality')}</th>
                <th scope="col" className="px-4 py-3 text-start">{t('covx.col_province')}</th>
                <th scope="col" className="px-4 py-3 text-start">{t('covx.col_status')}</th>
                <th scope="col" className="px-4 py-3 text-start">{t('covx.col_offices')}</th>
                <th scope="col" className="px-4 py-3 text-start">{t('covx.col_demarches')}</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {rows.map((row) => (
                <tr key={row.slug}>
                  <td className="px-4 py-3 font-semibold text-slate-900">{row.name}</td>
                  <td className="px-4 py-3 text-slate-700">{row.province ?? '—'}</td>
                  <td className="px-4 py-3 text-slate-700">{row.status === 'official' ? t('covx.status_official') : t('covx.status_to_verify')}</td>
                  <td className="px-4 py-3 text-slate-700">{row.offices_attested > 0 ? row.offices_attested : t('covx.no_office')}</td>
                  <td className="px-4 py-3 text-slate-700">{row.demarches_documented}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </main>
  );
}
