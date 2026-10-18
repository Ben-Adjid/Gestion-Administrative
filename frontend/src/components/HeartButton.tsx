'use client';

import { useEffect, useState } from 'react';
import Link from '@/components/AppLink';
import { Heart } from 'lucide-react';
import { fetchApi } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

interface HeartButtonProps {
  demarcheId: number;
  withLabel?: boolean;
}

export default function HeartButton({ demarcheId, withLabel = false }: HeartButtonProps) {
  const { t } = useLanguage();
  const [loggedIn, setLoggedIn] = useState(false);
  const [isFavorite, setIsFavorite] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [needsLogin, setNeedsLogin] = useState(false);

  useEffect(() => {
    const hasToken = !!localStorage.getItem('token');
    setLoggedIn(hasToken);
    if (!hasToken) return;

    fetchApi<{ is_favorite: boolean }>(`/favorites/${demarcheId}/status`)
      .then((res) => setIsFavorite(res.is_favorite))
      .catch(() => { /* statut inconnu : le bouton reste utilisable */ });
  }, [demarcheId]);

  const toggle = async (event: React.MouseEvent) => {
    event.preventDefault();
    event.stopPropagation();
    setError('');

    if (!loggedIn) {
      setNeedsLogin(true);
      return;
    }

    setLoading(true);
    try {
      await fetchApi(`/favorites/${demarcheId}`, { method: isFavorite ? 'DELETE' : 'POST' });
      setIsFavorite(!isFavorite);
    } catch {
      setError(t('actions.failed'));
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="relative inline-block">
      <button
        type="button"
        aria-label={isFavorite ? t('actions.unsave_label') : t('actions.save_label')}
        aria-pressed={isFavorite}
        onClick={toggle}
        disabled={loading}
        className={`inline-flex items-center gap-2 rounded-full border px-3 py-2 text-sm font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 disabled:opacity-50 ${
          isFavorite ? 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'
        }`}
      >
        <Heart aria-hidden="true" size={18} fill={isFavorite ? 'currentColor' : 'none'} />
        {withLabel && <span>{isFavorite ? t('actions.saved') : t('actions.save')}</span>}
      </button>
      {needsLogin && (
        <div role="alert" className="absolute end-0 top-full z-20 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-700 shadow-lg">
          {t('actions.login_to_save')}{' '}
          <Link href="/auth/login" className="font-semibold text-blue-800 underline">{t('actions.login')}</Link>
        </div>
      )}
      {error && (
        <div role="alert" className="absolute end-0 top-full z-20 mt-2 whitespace-nowrap rounded bg-red-700 px-2 py-1 text-xs text-white">
          {error}
        </div>
      )}
    </div>
  );
}
