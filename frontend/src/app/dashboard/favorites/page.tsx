'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { Heart, Trash2 } from 'lucide-react';
import CitizenNav from '@/components/CitizenNav';
import { fetchApi } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

interface Favorite {
  id: number;
  note: string | null;
  demarche: {
    id: number;
    title: string;
    slug: string;
    cost: string | null;
    processing_time: string | null;
    categorie: string | null;
  };
}

export default function FavoritesPage() {
  const { language, t } = useLanguage();
  const [favorites, setFavorites] = useState<Favorite[] | null>(null);
  const [notes, setNotes] = useState<Record<number, string>>({});
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  useEffect(() => {
    fetchApi<{ data: Favorite[] }>(`/favorites?per_page=50&lang=${language}`)
      .then((res) => {
        setFavorites(res.data);
        setNotes(Object.fromEntries(res.data.map((f) => [f.demarche.id, f.note || ''])));
      })
      .catch((e: unknown) => setError(e instanceof Error ? e.message : t('common.error_generic')));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [language]);

  const remove = async (fav: Favorite) => {
    setError('');
    try {
      await fetchApi(`/favorites/${fav.demarche.id}`, { method: 'DELETE' });
      setMessage(t('favorites.removed'));
      setFavorites((list) => list?.filter((f) => f.id !== fav.id) ?? null);
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : t('common.error_generic'));
    }
  };

  const saveNote = async (fav: Favorite) => {
    setError('');
    try {
      await fetchApi(`/favorites/${fav.demarche.id}/note`, {
        method: 'PATCH',
        body: JSON.stringify({ note: notes[fav.demarche.id] || null }),
      });
      setMessage(t('favorites.note_saved'));
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : t('common.error_generic'));
    }
  };

  return (
    <main className="mx-auto max-w-5xl px-4 py-10">
      <h1 className="text-3xl font-bold tracking-tight text-slate-950">{t('favorites.title')}</h1>
      <p className="mt-2 text-slate-600">{t('favorites.intro')}</p>
      <div className="mt-6"><CitizenNav /></div>

      {message && <p role="status" className="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">{message}</p>}
      {error && <p role="alert" className="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">{error}</p>}

      {favorites === null && !error && (
        <div role="status" aria-label={t('common.loading')} className="space-y-3">
          {[0, 1].map((i) => <div key={i} className="h-32 animate-pulse rounded-2xl bg-slate-100" />)}
        </div>
      )}

      {favorites && favorites.length === 0 && (
        <div className="rounded-2xl border border-dashed border-slate-300 p-10 text-center">
          <Heart aria-hidden="true" className="mx-auto text-slate-400" size={40} />
          <p className="mt-3 font-semibold text-slate-800">{t('favorites.empty_title')}</p>
          <p className="mt-1 text-slate-600">{t('favorites.empty_text')}</p>
          <Link href="/demarches" className="mt-5 inline-block rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">
            {t('citizen.browse')}
          </Link>
        </div>
      )}

      {favorites && favorites.length > 0 && (
        <ul className="space-y-4">
          {favorites.map((fav) => (
            <li key={fav.id} className="rounded-2xl border border-slate-200 bg-white p-5">
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  {fav.demarche.categorie && <span className="text-xs font-bold uppercase tracking-wide text-blue-800">{fav.demarche.categorie}</span>}
                  <Link href={`/demarches/${fav.demarche.slug}`} className="block text-lg font-bold text-slate-900 hover:text-blue-800 hover:underline">
                    {fav.demarche.title}
                  </Link>
                  <p className="mt-1 text-sm text-slate-600">{[fav.demarche.cost, fav.demarche.processing_time].filter(Boolean).join(' · ')}</p>
                </div>
                <button type="button" onClick={() => remove(fav)} className="inline-flex items-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                  <Trash2 aria-hidden="true" size={16} /> {t('favorites.remove')}
                </button>
              </div>
              <label htmlFor={`note-${fav.id}`} className="mt-4 block text-sm font-semibold text-slate-700">{t('favorites.note_label')}</label>
              <div className="mt-1 flex flex-col gap-2 sm:flex-row">
                <textarea
                  id={`note-${fav.id}`}
                  rows={2}
                  maxLength={1000}
                  value={notes[fav.demarche.id] ?? ''}
                  onChange={(e) => setNotes({ ...notes, [fav.demarche.id]: e.target.value })}
                  placeholder={t('favorites.note_ph')}
                  className="w-full rounded-lg border border-slate-300 px-3 py-2 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
                />
                <button type="button" onClick={() => saveNote(fav)} className="shrink-0 self-start rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
                  {t('favorites.save_note')}
                </button>
              </div>
            </li>
          ))}
        </ul>
      )}
    </main>
  );
}
