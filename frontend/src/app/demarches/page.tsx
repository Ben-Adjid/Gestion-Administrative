'use client';

import { Suspense, useEffect, useState } from 'react';
import Link from '@/components/AppLink';
import { useSearchParams } from 'next/navigation';
import { ChevronLeft, ChevronRight, Clock3, FileText, RefreshCw, Search } from 'lucide-react';
import HeartButton from '@/components/HeartButton';
import ShareMenu from '@/components/ShareMenu';
import { API_V1_URL } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';
import { Categorie, Demarche } from '@/types';

type CollectionResponse<T> = {
  data: T[];
  meta?: { current_page?: number; last_page?: number; total?: number };
};

function DemarchesContent() {
  const { language, t } = useLanguage();
  const searchParams = useSearchParams();
  const initialQ = searchParams?.get('q') || '';
  const initialCategory = searchParams?.get('categorie') || '';

  const [demarches, setDemarches] = useState<Demarche[]>([]);
  const [categories, setCategories] = useState<Categorie[]>([]);
  const [meta, setMeta] = useState({ currentPage: 1, lastPage: 1, total: 0 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [filter, setFilter] = useState(initialCategory);
  const [search, setSearch] = useState(initialQ);
  const [debouncedSearch, setDebouncedSearch] = useState(initialQ);
  const [page, setPage] = useState(1);
  const [reloadKey, setReloadKey] = useState(0);
  const [sort, setSort] = useState('');
  const [popular, setPopular] = useState<Demarche[]>([]);

  // Nouvelle recherche lancée depuis la barre d'en-tête alors qu'on est déjà sur le catalogue.
  useEffect(() => {
    setSearch(initialQ);
    setDebouncedSearch(initialQ);
    setPage(1);
  }, [initialQ]);

  useEffect(() => {
    const timeout = window.setTimeout(() => setDebouncedSearch(search.trim()), 300);
    return () => window.clearTimeout(timeout);
  }, [search]);

  // Aucun résultat : on propose les démarches les plus consultées (cahier des charges, module 2).
  useEffect(() => {
    if (loading || demarches.length > 0 || error) return;
    fetch(`${API_V1_URL}/demarches?per_page=6&sort=views&lang=${language}`, { headers: { Accept: 'application/json' } })
      .then((res) => (res.ok ? res.json() : { data: [] }))
      .then((json: CollectionResponse<Demarche>) => setPopular(json.data))
      .catch(() => setPopular([]));
  }, [loading, demarches.length, error, language]);

  useEffect(() => {
    const controller = new AbortController();

    async function loadData() {
      setLoading(true);
      setError('');
      try {
        const proceduresUrl = new URL(`${API_V1_URL}/demarches`);
        if (filter) proceduresUrl.searchParams.set('categorie', filter);
        if (debouncedSearch) proceduresUrl.searchParams.set('q', debouncedSearch);
        proceduresUrl.searchParams.set('per_page', '15');
        proceduresUrl.searchParams.set('page', String(page));
        proceduresUrl.searchParams.set('lang', language);
        // Sans choix explicite : pertinence si une recherche est saisie, sinon popularité.
        if (sort && (sort !== 'relevance' || debouncedSearch)) proceduresUrl.searchParams.set('sort', sort);

        const headers = { Accept: 'application/json' };
        const [proceduresResponse, categoriesResponse] = await Promise.all([
          fetch(proceduresUrl, { signal: controller.signal, headers }),
          fetch(`${API_V1_URL}/categories?lang=${language}`, { signal: controller.signal, headers }),
        ]);

        const failed = !proceduresResponse.ok ? proceduresResponse : !categoriesResponse.ok ? categoriesResponse : null;
        if (failed) throw new Error(t('catalog.error_load', { status: failed.status }));

        const [proceduresResult, categoriesResult] = await Promise.all([
          proceduresResponse.json() as Promise<CollectionResponse<Demarche>>,
          categoriesResponse.json() as Promise<CollectionResponse<Categorie>>,
        ]);
        setDemarches(proceduresResult.data);
        setCategories(categoriesResult.data.filter((c) => (c.demarches_count ?? 0) > 0 || c.slug === filter));
        setMeta({
          currentPage: proceduresResult.meta?.current_page ?? page,
          lastPage: proceduresResult.meta?.last_page ?? 1,
          total: proceduresResult.meta?.total ?? proceduresResult.data.length,
        });
      } catch (err) {
        if (err instanceof DOMException && err.name === 'AbortError') return;
        // Une erreur sans message (ex. « Failed to fetch » vide) doit tout de même dire quelque chose à l'utilisateur.
        setError(err instanceof Error && err.message.trim() ? err.message : t('common.error_generic'));
        setDemarches([]);
        setMeta({ currentPage: 1, lastPage: 1, total: 0 });
      } finally {
        if (!controller.signal.aborted) setLoading(false);
      }
    }

    void loadData();
    return () => controller.abort();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [filter, debouncedSearch, page, reloadKey, language, sort]);

  const pill = (active: boolean) =>
    `rounded-full px-4 py-2 text-sm font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2 ${
      active ? 'bg-blue-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:border-blue-300 hover:text-blue-800'
    }`;

  return (
    <main className="min-h-screen bg-slate-50 text-slate-900">
      <section className="border-b border-slate-200 bg-white">
        <div className="mx-auto max-w-7xl px-4 py-12 sm:px-8 sm:py-16 lg:px-10">
          <p className="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">{t('catalog.kicker')}</p>
          <h1 className="mt-3 text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">{t('catalog.title')}</h1>
          <p className="mt-4 max-w-2xl text-lg leading-7 text-slate-600">{t('catalog.intro')}</p>

          <label className="relative mt-8 block max-w-2xl">
            <span className="sr-only">{t('catalog.search_label')}</span>
            <Search aria-hidden="true" size={20} className="absolute start-4 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              type="search"
              placeholder={t('catalog.search_placeholder')}
              value={search}
              onChange={(event) => {
                setSearch(event.target.value);
                setPage(1);
              }}
              className="w-full rounded-xl border border-slate-200 bg-slate-50 py-3.5 pe-4 ps-12 text-base text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100"
            />
          </label>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-10 sm:px-8 lg:px-10">
        <div className="mb-8">
          <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 className="text-sm font-semibold text-slate-700">{t('catalog.filter_title')}</h2>
            <label className="flex items-center gap-2 text-sm font-semibold text-slate-700">
              {t('searchx.sort')}
              <select value={sort} onChange={(e) => { setSort(e.target.value); setPage(1); }} className="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium">
                <option value="">{debouncedSearch ? t('searchx.sort_relevance') : t('searchx.sort_views')}</option>
                {debouncedSearch && <option value="views">{t('searchx.sort_views')}</option>}                <option value="title">{t('searchx.sort_title')}</option>
                <option value="recent">{t('searchx.sort_recent')}</option>
              </select>
            </label>
          </div>
          <div className="flex flex-wrap gap-2">
            <button type="button" aria-pressed={filter === ''} onClick={() => { setFilter(''); setPage(1); }} className={pill(filter === '')}>
              {t('catalog.all')}
            </button>
            {categories.map((category) => (
              <button
                type="button"
                key={category.id}
                aria-pressed={filter === category.slug}
                onClick={() => { setFilter(category.slug); setPage(1); }}
                className={pill(filter === category.slug)}
              >
                {category.name}
                {typeof category.demarches_count === 'number' && <span className="ms-1.5 opacity-70">({category.demarches_count})</span>}
              </button>
            ))}
          </div>
        </div>

        {error && (
          <div role="alert" className="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p>{error}</p>
            <button type="button" onClick={() => setReloadKey((key) => key + 1)} className="inline-flex items-center gap-2 font-semibold hover:underline">
              <RefreshCw size={15} aria-hidden="true" /> {t('common.retry')}
            </button>
          </div>
        )}

        {loading ? (
          <div role="status" className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <span className="sr-only">{t('catalog.loading')}</span>
            {[1, 2, 3, 4, 5, 6].map((item) => (
              <div key={item} className="h-64 animate-pulse rounded-2xl border border-slate-200 bg-white p-6">
                <div className="h-5 w-2/3 rounded bg-slate-100" />
                <div className="mt-5 h-3 w-full rounded bg-slate-100" />
                <div className="mt-2 h-3 w-4/5 rounded bg-slate-100" />
              </div>
            ))}
          </div>
        ) : demarches.length === 0 && !error ? (
          <div className="rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center">
            <FileText aria-hidden="true" className="mx-auto h-10 w-10 text-slate-300" />
            <h2 className="mt-4 text-lg font-bold text-slate-900">{t('catalog.empty_title')}</h2>
            <p className="mt-2 text-sm text-slate-600">{t('catalog.empty_text')}</p>
            <div className="mt-4 flex flex-wrap justify-center gap-4">
              {search && (
                <button type="button" onClick={() => setSearch('')} className="text-sm font-semibold text-blue-800 hover:underline">
                  {t('catalog.clear_search')}
                </button>
              )}
              <Link href="/signalement?type=missing" className="text-sm font-semibold text-amber-700 hover:underline">
                {t('catalog.suggest_missing')}
              </Link>
            </div>
            {popular.length > 0 && (
              <div className="mx-auto mt-8 max-w-2xl text-start">
                <p className="text-sm font-bold text-slate-800">{t('searchx.popular')}</p>
                <ul className="mt-3 grid gap-2 sm:grid-cols-2">
                  {popular.map((item) => (
                    <li key={item.id}>
                      <Link href={`/demarches/${item.slug}`} className="block rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-800 hover:border-blue-300 hover:text-blue-800">
                        {item.title}
                      </Link>
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </div>
        ) : (
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {demarches.map((demarche) => (
              <article key={demarche.id} className="flex min-h-64 flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-blue-200 hover:shadow-lg hover:shadow-slate-900/5">
                <div className="mb-4 flex items-center justify-between gap-3">
                  <span className="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-800">
                    {demarche.categorie?.name || t('catalog.default_category')}
                  </span>
                </div>
                <Link href={`/demarches/${demarche.slug}`} className="rounded-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600">
                  <h2 className="text-lg font-bold leading-6 text-slate-900 transition hover:text-blue-800">{demarche.title}</h2>
                </Link>
                <p className="mt-1 text-xs text-slate-500">{demarche.institution}</p>
                <p className="mt-3 line-clamp-3 flex-1 text-sm leading-6 text-slate-600">{demarche.summary || demarche.description}</p>
                <div className="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                  <div className="min-w-0">
                    <p className="break-words text-sm font-semibold text-slate-900"><bdi>{demarche.cost || t('catalog.cost_unknown')}</bdi></p>
                    <p className="mt-1 flex items-center gap-1 text-xs text-slate-500">
                      <Clock3 size={13} aria-hidden="true" /> {demarche.processing_time || t('catalog.delay_unknown')}
                    </p>
                  </div>
                  <div className="flex items-center gap-2">
                    <HeartButton demarcheId={demarche.id} />
                    <ShareMenu demarcheId={demarche.id} demarcheTitle={demarche.title} demarcheSlug={demarche.slug} />
                  </div>
                </div>
              </article>
            ))}
          </div>
        )}

        {!loading && !error && meta.total > 0 && (
          <nav aria-label={t('catalog.pagination')} className="mt-8 flex flex-col items-center justify-between gap-3 border-t border-slate-200 pt-5 sm:flex-row">
            <p className="text-sm text-slate-600">{t('catalog.count', { n: meta.total, page: meta.currentPage, pages: meta.lastPage })}</p>
            <div className="flex items-center gap-2">
              <button
                type="button"
                disabled={meta.currentPage <= 1}
                onClick={() => setPage((current) => Math.max(1, current - 1))}
                className="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
              >
                <ChevronLeft size={16} aria-hidden="true" className="rtl:-scale-x-100" /> {t('catalog.previous')}
              </button>
              <button
                type="button"
                disabled={meta.currentPage >= meta.lastPage}
                onClick={() => setPage((current) => Math.min(meta.lastPage, current + 1))}
                className="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
              >
                {t('catalog.next')} <ChevronRight size={16} aria-hidden="true" className="rtl:-scale-x-100" />
              </button>
            </div>
          </nav>
        )}
      </section>
    </main>
  );
}

export default function DemarchesPage() {
  return (
    <Suspense fallback={<div role="status" className="flex min-h-screen items-center justify-center bg-slate-50 text-slate-500">…</div>}>
      <DemarchesContent />
    </Suspense>
  );
}
