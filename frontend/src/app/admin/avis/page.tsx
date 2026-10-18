'use client';

import { useCallback, useEffect, useState } from 'react';
import { Check, Star, Trash2, X } from 'lucide-react';
import { AdminNotice, AdminShell, dangerButton, smallButton } from '@/components/admin/AdminShell';
import { fetchApi } from '@/lib/api';
import { formatFcfa } from '@/lib/format';
import { formatDate, formatDays } from '@/lib/i18n';
import { useLanguage } from '@/hooks/useLanguage';

type Status = 'pending' | 'approved' | 'rejected';

interface Review {
  id: number;
  visit_date: string;
  price_paid: number | null;
  duration_days: number | null;
  rating: number;
  comment: string;
  author_name: string | null;
  status: Status;
  demarche: { id: number; title: string; slug: string } | null;
  city: { id: number; name: string } | null;
}

/** Modération des avis des citoyens. */
export default function ReviewsModerationPage() {
  const { language, t } = useLanguage();
  const [status, setStatus] = useState<Status>('pending');
  const [reviews, setReviews] = useState<Review[]>([]);
  const [loaded, setLoaded] = useState(false);
  const [busy, setBusy] = useState<number | null>(null);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const load = useCallback(() => {
    setLoaded(false);
    fetchApi<{ data: Review[] }>(`/admin/reviews?status=${status}`)
      .then((res) => { setReviews(res.data); setLoaded(true); })
      .catch((e: unknown) => { setError(e instanceof Error ? e.message : t('common.error_generic')); setLoaded(true); });
  }, [status, t]);

  useEffect(load, [load]);

  const act = async (review: Review, action: 'approved' | 'rejected' | 'delete') => {
    if (action === 'delete' && !window.confirm(t('adx.confirm_delete', { name: review.comment.slice(0, 40) }))) return;
    setBusy(review.id);
    setError('');
    try {
      if (action === 'delete') await fetchApi(`/admin/reviews/${review.id}`, { method: 'DELETE' });
      else await fetchApi(`/admin/reviews/${review.id}`, { method: 'PATCH', body: JSON.stringify({ status: action }) });
      setMessage(action === 'delete' ? t('adx.deleted') : t('adx.saved'));
      setReviews((list) => list.filter((r) => r.id !== review.id));
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : t('common.error_generic'));
    } finally {
      setBusy(null);
    }
  };

  const tab = (value: Status, key: string) => (
    <button key={value} type="button" aria-pressed={status === value} onClick={() => setStatus(value)} className={`rounded-full px-4 py-1.5 text-sm font-semibold ${status === value ? 'bg-blue-700 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'}`}>
      {t(key)}
    </button>
  );

  return (
    <AdminShell title={t('adx.v_title')} intro={t('adx.v_intro')}>
      <div className="mb-5 flex flex-wrap gap-2">
        {tab('pending', 'adx.v_pending')}
        {tab('approved', 'adx.v_approved')}
        {tab('rejected', 'adx.v_rejected')}
      </div>
      <AdminNotice message={message} error={error} />
      {!loaded && <p role="status" className="text-slate-500">{t('adx.loading')}</p>}
      {loaded && reviews.length === 0 && <p className="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-slate-600">{t('adx.v_empty')}</p>}
      <ul className="space-y-3">
        {reviews.map((review) => (
          <li key={review.id} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex flex-wrap items-center gap-2 text-sm text-slate-500">
              <span className="font-bold text-slate-900">{review.demarche?.title}</span>
              <span className="inline-flex" aria-label={`${review.rating}/5`}>
                {[1, 2, 3, 4, 5].map((n) => <Star key={n} size={14} aria-hidden="true" className={n <= review.rating ? 'fill-amber-400 text-amber-400' : 'text-slate-300'} />)}
              </span>
            </div>
            <p className="mt-1 text-xs text-slate-500">
              {t('adx.v_meta', { date: formatDate(language, review.visit_date), rating: review.rating })}
              {review.author_name && ` · ${review.author_name}`}{review.city && ` · ${review.city.name}`}
            </p>
            <p className="mt-3 whitespace-pre-line text-slate-800">{review.comment}</p>
            {(review.price_paid !== null || review.duration_days !== null) && (
              <p className="mt-2 flex flex-wrap gap-4 text-sm text-slate-600">
                {review.price_paid !== null && <span>{t('reviews.paid', { price: formatFcfa(language, review.price_paid) })}</span>}
                {review.duration_days !== null && <span>{t('reviews.duration', { days: formatDays(language, review.duration_days) })}</span>}
              </p>
            )}
            <div className="mt-4 flex flex-wrap gap-2">
              {review.status !== 'approved' && <button type="button" disabled={busy === review.id} onClick={() => act(review, 'approved')} className={smallButton}><Check size={14} aria-hidden="true" /> {t('adx.v_approve')}</button>}
              {review.status !== 'rejected' && <button type="button" disabled={busy === review.id} onClick={() => act(review, 'rejected')} className={smallButton}><X size={14} aria-hidden="true" /> {t('adx.v_reject')}</button>}
              <button type="button" disabled={busy === review.id} onClick={() => act(review, 'delete')} className={dangerButton}><Trash2 size={14} aria-hidden="true" /> {t('adx.delete')}</button>
            </div>
          </li>
        ))}
      </ul>
    </AdminShell>
  );
}
