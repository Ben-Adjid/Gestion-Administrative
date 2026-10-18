'use client';

import { FormEvent, useCallback, useEffect, useState } from 'react';
import { MessageSquareText, Star } from 'lucide-react';
import { CitySelect } from '@/components/CitySelect';
import { API_V1_URL, fetchApi, splitFormErrors } from '@/lib/api';
import { formatFcfa } from '@/lib/format';
import { formatDate, formatDays } from '@/lib/i18n';
import { useLanguage } from '@/hooks/useLanguage';

interface Review {
  id: number;
  visit_date: string;
  price_paid: number | null;
  duration_days: number | null;
  rating: number;
  comment: string;
  author_name: string | null;
  city: string | null;
}

interface Summary {
  count: number;
  average_rating: number;
  median_price: number | null;
  median_duration_days: number | null;
}

const FIELDS = ['visit_date', 'price_paid', 'duration_days', 'rating', 'comment', 'author_name', 'city_id'] as const;
const EMPTY = { visit_date: '', price_paid: '', duration_days: '', rating: 0, comment: '', author_name: '', city: null as string | null, website: '' };

function Stars({ value }: { value: number }) {
  return (
    <span className="inline-flex" aria-hidden="true">
      {[1, 2, 3, 4, 5].map((n) => <Star key={n} size={15} className={n <= value ? 'fill-amber-400 text-amber-400' : 'text-slate-300'} />)}
    </span>
  );
}

/** Retours d'expérience des citoyens : publiés après modération. */
export function ReviewsSection({ slug }: { slug: string }) {
  const { language, t } = useLanguage();
  const [summary, setSummary] = useState<Summary | null>(null);
  const [reviews, setReviews] = useState<Review[]>([]);
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState(EMPTY);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');
  const [sending, setSending] = useState(false);
  const [cityIds, setCityIds] = useState<Record<string, number>>({});

  const load = useCallback(() => {
    fetch(`${API_V1_URL}/demarches/${encodeURIComponent(slug)}/reviews?lang=${language}`, { headers: { Accept: 'application/json' } })
      .then((res) => (res.ok ? res.json() : null))
      .then((json: { summary: Summary; data: Review[] } | null) => {
        if (!json) return;
        setSummary(json.summary);
        setReviews(json.data);
      })
      .catch(() => { /* la section reste vide hors connexion */ });
  }, [slug, language]);

  useEffect(load, [load]);

  // Identifiants des villes (le sélecteur travaille avec les identifiants texte).
  useEffect(() => {
    if (!open) return;
    fetch(`${API_V1_URL}/cities`, { headers: { Accept: 'application/json' } })
      .then((res) => (res.ok ? res.json() : { data: [] }))
      .then((json: { data: Array<{ id: number; slug: string }> }) => setCityIds(Object.fromEntries(json.data.map((c) => [c.slug, c.id]))))
      .catch(() => setCityIds({}));
  }, [open]);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setSending(true);
    setErrors({});
    setError('');
    setMessage('');
    try {
      const res = await fetchApi<{ message: string }>(`/demarches/${encodeURIComponent(slug)}/reviews`, {
        method: 'POST',
        body: JSON.stringify({
          visit_date: form.visit_date,
          price_paid: form.price_paid === '' ? null : Number(form.price_paid),
          duration_days: form.duration_days === '' ? null : Number(form.duration_days),
          rating: form.rating || null,
          comment: form.comment,
          author_name: form.author_name || null,
          city_id: form.city ? cityIds[form.city] ?? null : null,
          website: form.website,
        }),
      });
      setMessage(res.message);
      setForm(EMPTY);
      setOpen(false);
    } catch (e: unknown) {
      const { fieldErrors, general } = splitFormErrors(e, FIELDS, t('common.error_generic'));
      setErrors(fieldErrors);
      if (general) setError(general);
    } finally {
      setSending(false);
    }
  };

  const input = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200';
  const fieldError = (name: string) => errors[name]?.[0] && <p className="mt-1 text-sm text-red-700">{errors[name][0]}</p>;
  const today = new Date().toISOString().slice(0, 10);

  return (
    <section className="print:hidden">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="flex items-center gap-2 text-xl font-bold text-slate-950">
            <MessageSquareText size={20} aria-hidden="true" className="text-blue-700" /> {t('reviews.title')}
          </h2>
          <p className="mt-1 text-sm text-slate-600">{t('reviews.intro')}</p>
        </div>
        {!open && (
          <button type="button" onClick={() => setOpen(true)} className="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
            {t('reviews.show_form')}
          </button>
        )}
      </div>

      {message && <p role="status" className="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{message}</p>}

      {summary && summary.count > 0 && (
        <div className="mt-4 flex flex-wrap gap-x-6 gap-y-2 rounded-xl bg-slate-50 p-4 text-sm text-slate-700">
          <span className="font-semibold">{t('reviews.count', { n: summary.count })}</span>
          <span className="inline-flex items-center gap-2"><Stars value={Math.round(summary.average_rating)} /> {t('reviews.average', { n: summary.average_rating })}</span>
          {summary.median_price !== null && <span>{t('reviews.median_price', { price: formatFcfa(language, summary.median_price) })}</span>}
          {summary.median_duration_days !== null && <span>{t('reviews.median_duration', { days: formatDays(language, summary.median_duration_days) })}</span>}
        </div>
      )}

      {open && (
        <form onSubmit={submit} noValidate className="mt-4 space-y-4 rounded-2xl border border-slate-200 bg-white p-5">
          <h3 className="font-bold text-slate-900">{t('reviews.form_title')}</h3>
          {error && <p role="alert" className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}
          {/* Champ piège : invisible pour les personnes, rempli par les robots. */}
          <input type="text" name="website" value={form.website} onChange={(e) => setForm({ ...form, website: e.target.value })} tabIndex={-1} autoComplete="off" aria-hidden="true" className="hidden" />
          <div className="grid gap-4 sm:grid-cols-3">
            <label className="text-sm font-semibold text-slate-700">{t('reviews.visit_date')} *
              <input type="date" required max={today} value={form.visit_date} onChange={(e) => setForm({ ...form, visit_date: e.target.value })} className={input} />
              {fieldError('visit_date')}
            </label>
            <label className="text-sm font-semibold text-slate-700">{t('reviews.price_paid')}
              <input type="number" min={0} inputMode="numeric" value={form.price_paid} onChange={(e) => setForm({ ...form, price_paid: e.target.value })} className={input} />
              {fieldError('price_paid')}
            </label>
            <label className="text-sm font-semibold text-slate-700">{t('reviews.duration_days')}
              <input type="number" min={0} inputMode="numeric" value={form.duration_days} onChange={(e) => setForm({ ...form, duration_days: e.target.value })} className={input} />
              {fieldError('duration_days')}
            </label>
          </div>
          <fieldset>
            <legend className="text-sm font-semibold text-slate-700">{t('reviews.rating')} *</legend>
            <div className="mt-1 flex gap-1">
              {[1, 2, 3, 4, 5].map((n) => (
                <label key={n} className="cursor-pointer" title={t('reviews.rating_n', { n })}>
                  <input type="radio" name="rating" value={n} checked={form.rating === n} onChange={() => setForm({ ...form, rating: n })} className="sr-only" />
                  <Star size={30} aria-hidden="true" className={n <= form.rating ? 'fill-amber-400 text-amber-400' : 'text-slate-300 hover:text-amber-300'} />
                  <span className="sr-only">{t('reviews.rating_n', { n })}</span>
                </label>
              ))}
            </div>
            {fieldError('rating')}
          </fieldset>
          <label className="block text-sm font-semibold text-slate-700">{t('reviews.comment')} *
            <textarea required minLength={10} maxLength={1000} rows={4} value={form.comment} onChange={(e) => setForm({ ...form, comment: e.target.value })} placeholder={t('reviews.comment_ph')} className={input} />
            {fieldError('comment')}
          </label>
          <div className="grid gap-4 sm:grid-cols-2">
            <label className="text-sm font-semibold text-slate-700">{t('reviews.name')}
              <input maxLength={100} value={form.author_name} onChange={(e) => setForm({ ...form, author_name: e.target.value })} className={input} />
            </label>
            <div className="pt-6">
              <CitySelect id="review-city" value={form.city} onChange={(slug) => setForm({ ...form, city: slug })} label={t('reviews.city')} emptyLabel={t('reviews.none')} />
            </div>
          </div>
          <p className="text-xs text-slate-500">{t('reviews.moderation_note')}</p>
          <div className="flex gap-3">
            <button type="submit" disabled={sending} className="rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800 disabled:opacity-50">
              {sending ? t('reviews.sending') : t('reviews.submit')}
            </button>
            <button type="button" onClick={() => setOpen(false)} className="rounded-lg border border-slate-300 px-5 py-2.5 font-semibold text-slate-700 hover:bg-slate-50">
              {t('adx.cancel')}
            </button>
          </div>
        </form>
      )}

      {reviews.length > 0 ? (
        <ul className="mt-4 space-y-3">
          {reviews.map((review) => (
            <li key={review.id} className="rounded-xl border border-slate-200 p-4">
              <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
                <Stars value={review.rating} />
                <span className="font-semibold text-slate-800">{review.author_name || t('reviews.anonymous')}</span>
                {review.city && <span>· {review.city}</span>}
                <span>· {t('reviews.visited', { date: formatDate(language, review.visit_date) })}</span>
              </div>
              <p className="mt-2 whitespace-pre-line text-slate-700">{review.comment}</p>
              {(review.price_paid !== null || review.duration_days !== null) && (
                <p className="mt-2 flex flex-wrap gap-4 text-sm font-medium text-slate-600">
                  {review.price_paid !== null && <span>{t('reviews.paid', { price: formatFcfa(language, review.price_paid) })}</span>}
                  {review.duration_days !== null && <span>{t('reviews.duration', { days: formatDays(language, review.duration_days) })}</span>}
                </p>
              )}
            </li>
          ))}
        </ul>
      ) : (
        summary && <p className="mt-4 text-sm text-slate-600">{t('reviews.empty')}</p>
      )}
    </section>
  );
}
