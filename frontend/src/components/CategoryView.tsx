'use client';

import { useEffect, useState } from 'react';
import Link from '@/components/AppLink';
import { ArrowLeft, ArrowRight, Clock3, Wallet } from 'lucide-react';
import { API_V1_URL } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';
import { Categorie, Demarche } from '@/types';

export interface CategoryPageData {
  categorie: Categorie;
  data: Demarche[];
}

/** Liste d'une catégorie ; la version française vient du serveur (SEO), les autres langues sont chargées à la demande. */
export default function CategoryView({ initial }: { initial: CategoryPageData }) {
  const { language, t } = useLanguage();
  const [page, setPage] = useState<CategoryPageData>(initial);

  useEffect(() => {
    if (language === 'fr') {
      setPage(initial);
      return;
    }
    const controller = new AbortController();
    fetch(`${API_V1_URL}/categories/${encodeURIComponent(initial.categorie.slug)}/demarches?lang=${language}`, {
      signal: controller.signal,
      headers: { Accept: 'application/json' },
    })
      .then((res) => (res.ok ? res.json() : null))
      .then((json: CategoryPageData | null) => json && setPage(json))
      .catch(() => { /* la version française reste affichée */ });
    return () => controller.abort();
  }, [language, initial]);

  const { categorie, data: demarches } = page;

  return (
    <div className="mx-auto max-w-5xl px-4 py-10 sm:px-8">
      <Link href="/demarches" className="inline-flex items-center gap-2 font-semibold text-blue-800 hover:underline">
        <ArrowLeft size={18} aria-hidden="true" className="rtl:-scale-x-100" /> {t('category.all')}
      </Link>

      <header className="mt-6">
        <h1 className="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{categorie.name}</h1>
        {categorie.description && <p className="mt-3 text-lg text-slate-600">{categorie.description}</p>}
        <p className="mt-2 text-sm font-medium text-slate-500">{t('category.count', { n: demarches.length })}</p>
      </header>

      {demarches.length === 0 ? (
        <p className="mt-8 rounded-2xl border border-slate-200 bg-white p-6 text-slate-600">{t('category.empty')}</p>
      ) : (
        <ul className="mt-8 grid gap-4 sm:grid-cols-2">
          {demarches.map((demarche) => (
            <li key={demarche.id}>
              <Link
                href={`/demarches/${demarche.slug}`}
                className="group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md"
              >
                <h2 className="text-lg font-bold text-slate-900 group-hover:text-blue-800">{demarche.title}</h2>
                {demarche.summary && <p className="mt-2 line-clamp-2 text-sm text-slate-600">{demarche.summary}</p>}
                <div className="mt-4 flex flex-wrap gap-3 text-sm text-slate-700">
                  <span className="inline-flex items-center gap-1.5"><Wallet size={15} aria-hidden="true" className="text-emerald-700" /> {demarche.cost}</span>
                  {demarche.processing_time && (
                    <span className="inline-flex items-center gap-1.5"><Clock3 size={15} aria-hidden="true" className="text-amber-700" /> {demarche.processing_time}</span>
                  )}
                </div>
                <span className="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-blue-800">
                  {t('category.see')} <ArrowRight size={15} aria-hidden="true" className="rtl:-scale-x-100" />
                </span>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
