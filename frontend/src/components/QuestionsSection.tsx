'use client';

import { FormEvent, useCallback, useEffect, useState } from 'react';
import { BadgeCheck, ChevronLeft, ChevronRight, HelpCircle, Search, UserRound } from 'lucide-react';
import AppLink from '@/components/AppLink';
import { API_V1_URL, fetchApi, splitFormErrors } from '@/lib/api';
import { formatDate } from '@/lib/i18n';
import { useLanguage } from '@/hooks/useLanguage';

interface AnswerItem { id: number; body: string; is_official: boolean; author_name: string | null; created_at: string }
interface QuestionItem {
  id: number;
  body: string;
  author_name: string | null;
  created_at: string;
  demarche: { slug: string; title: string } | null;
  answers: AnswerItem[];
}

const QUESTION_FIELDS = ['body', 'author_name', 'author_email'] as const;

/** Réponse de la communauté à une question publiée (modérée avant publication). */
function AnswerForm({ questionId, onDone }: { questionId: number; onDone: (message: string) => void }) {
  const { t } = useLanguage();
  const [body, setBody] = useState('');
  const [name, setName] = useState('');
  const [website, setWebsite] = useState('');
  const [error, setError] = useState('');
  const [sending, setSending] = useState(false);

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    setSending(true);
    setError('');
    try {
      const res = await fetchApi<{ message: string }>(`/questions/${questionId}/answers`, {
        method: 'POST',
        body: JSON.stringify({ body, author_name: name || null, website }),
      });
      onDone(res.message);
    } catch (e: unknown) {
      const { fieldErrors, general } = splitFormErrors(e, [], t('common.error_generic'));
      setError(Object.values(fieldErrors)[0]?.[0] || general || t('common.error_generic'));
    } finally {
      setSending(false);
    }
  };

  return (
    <form onSubmit={submit} noValidate className="mt-3 space-y-2 rounded-xl bg-slate-50 p-3">
      <input type="text" value={website} onChange={(e) => setWebsite(e.target.value)} tabIndex={-1} autoComplete="off" aria-hidden="true" className="hidden" />
      <label className="sr-only" htmlFor={`answer-${questionId}`}>{t('qa.answer')}</label>
      <textarea id={`answer-${questionId}`} rows={3} maxLength={1500} value={body} onChange={(e) => setBody(e.target.value)} placeholder={t('qa.answer_ph')} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
      <input value={name} maxLength={100} onChange={(e) => setName(e.target.value)} placeholder={t('qa.name')} aria-label={t('qa.name')} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
      {error && <p role="alert" className="text-sm text-red-700">{error}</p>}
      <button type="submit" disabled={sending || body.trim().length < 10} className="rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-50">
        {sending ? t('qa.sending') : t('qa.answer_submit')}
      </button>
    </form>
  );
}

/**
 * Questions-réponses publiques. Avec demarcheSlug : questions de cette fiche (aperçu) ;
 * sans : page complète avec recherche et pagination.
 */
export function QuestionsSection({ demarcheSlug, full = false }: { demarcheSlug?: string; full?: boolean }) {
  const { language, t } = useLanguage();
  const [questions, setQuestions] = useState<QuestionItem[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [search, setSearch] = useState('');
  const [query, setQuery] = useState('');
  const [loaded, setLoaded] = useState(false);
  const [answering, setAnswering] = useState<number | null>(null);
  const [notice, setNotice] = useState('');
  const [form, setForm] = useState({ body: '', author_name: '', author_email: '', website: '' });
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [error, setError] = useState('');
  const [sending, setSending] = useState(false);

  const load = useCallback(() => {
    const params = new URLSearchParams({ lang: language, page: String(page), per_page: full ? '20' : '5' });
    if (demarcheSlug) params.set('demarche', demarcheSlug);
    if (query) params.set('q', query);
    fetch(`${API_V1_URL}/questions?${params}`, { headers: { Accept: 'application/json' } })
      .then((res) => (res.ok ? res.json() : null))
      .then((json: { data: QuestionItem[]; meta: { last_page: number } } | null) => {
        if (!json) return;
        setQuestions(json.data);
        setLastPage(json.meta.last_page);
        setLoaded(true);
      })
      .catch(() => setLoaded(true));
  }, [language, page, demarcheSlug, query, full]);

  useEffect(load, [load]);

  // Recherche avec un court délai pendant la frappe.
  useEffect(() => {
    const timer = window.setTimeout(() => { setPage(1); setQuery(search.trim()); }, 350);
    return () => window.clearTimeout(timer);
  }, [search]);

  const ask = async (event: FormEvent) => {
    event.preventDefault();
    setSending(true);
    setErrors({});
    setError('');
    setNotice('');
    try {
      const res = await fetchApi<{ message: string }>('/questions', {
        method: 'POST',
        body: JSON.stringify({
          body: form.body,
          author_name: form.author_name || null,
          author_email: form.author_email || null,
          demarche_slug: demarcheSlug || null,
          website: form.website,
        }),
      });
      setNotice(res.message);
      setForm({ body: '', author_name: '', author_email: '', website: '' });
    } catch (e: unknown) {
      const { fieldErrors, general } = splitFormErrors(e, QUESTION_FIELDS, t('common.error_generic'));
      setErrors(fieldErrors);
      if (general) setError(general);
    } finally {
      setSending(false);
    }
  };

  const input = 'mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200';

  return (
    <section className="print:hidden">
      {!full && (
        <h2 className="flex items-center gap-2 text-xl font-bold text-slate-950">
          <HelpCircle size={20} aria-hidden="true" className="text-blue-700" /> {t('qa.fiche_title')}
        </h2>
      )}

      {notice && <p role="status" className="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{notice}</p>}

      {full && (
        <label className="relative mt-2 block">
          <span className="sr-only">{t('qa.search_ph')}</span>
          <Search size={18} aria-hidden="true" className="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-slate-400" />
          <input type="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder={t('qa.search_ph')} className="w-full rounded-xl border border-slate-300 py-3 pe-3 ps-10 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200" />
        </label>
      )}

      {loaded && questions.length === 0 && <p className="mt-4 text-sm text-slate-600">{t('qa.empty')}</p>}

      <ul className="mt-4 space-y-4">
        {questions.map((question) => (
          <li key={question.id} id={`question-${question.id}`} className="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
            <p className="font-semibold text-slate-900">{question.body}</p>
            <p className="mt-1 text-xs text-slate-500">
              {question.author_name || t('qa.anonymous')} · {formatDate(language, question.created_at)}
              {full && question.demarche && (
                <> · <AppLink href={`/demarches/${question.demarche.slug}`} className="text-blue-800 hover:underline">{t('qa.about', { title: question.demarche.title })}</AppLink></>
              )}
            </p>
            {question.answers.length > 0 ? (
              <ul className="mt-3 space-y-2">
                {question.answers.map((answer) => (
                  <li key={answer.id} className={`rounded-xl p-3 text-sm ${answer.is_official ? 'border border-blue-200 bg-blue-50 text-blue-950' : 'bg-slate-50 text-slate-700'}`}>
                    <p className="mb-1 flex items-center gap-1.5 text-xs font-bold">
                      {answer.is_official
                        ? <><BadgeCheck size={14} aria-hidden="true" /> {t('qa.official')}</>
                        : <><UserRound size={14} aria-hidden="true" /> {answer.author_name || t('qa.community')}</>}
                    </p>
                    <p className="whitespace-pre-line">{answer.body}</p>
                  </li>
                ))}
              </ul>
            ) : <p className="mt-2 text-sm text-slate-500">{t('qa.no_answer')}</p>}
            {answering === question.id ? (
              <AnswerForm questionId={question.id} onDone={(message) => { setAnswering(null); setNotice(message); }} />
            ) : (
              <button type="button" onClick={() => setAnswering(question.id)} className="mt-3 text-sm font-semibold text-blue-800 hover:underline">{t('qa.answer')}</button>
            )}
          </li>
        ))}
      </ul>

      {full && lastPage > 1 && (
        <nav className="mt-6 flex items-center justify-center gap-3" aria-label={t('qa.page', { page, pages: lastPage })}>
          <button type="button" disabled={page <= 1} onClick={() => setPage(page - 1)} className="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold disabled:opacity-40">
            <ChevronLeft size={16} aria-hidden="true" className="rtl:-scale-x-100" /> {t('qa.previous')}
          </button>
          <span className="text-sm text-slate-600">{t('qa.page', { page, pages: lastPage })}</span>
          <button type="button" disabled={page >= lastPage} onClick={() => setPage(page + 1)} className="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold disabled:opacity-40">
            {t('qa.next')} <ChevronRight size={16} aria-hidden="true" className="rtl:-scale-x-100" />
          </button>
        </nav>
      )}

      {!full && questions.length > 0 && (
        <AppLink href="/questions" className="mt-3 inline-block text-sm font-semibold text-blue-800 hover:underline">{t('qa.see_all')}</AppLink>
      )}

      <form onSubmit={ask} noValidate className="mt-6 space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-5">
        <h3 className="font-bold text-slate-900">{t('qa.ask_title')}</h3>
        {error && <p role="alert" className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{error}</p>}
        <input type="text" value={form.website} onChange={(e) => setForm({ ...form, website: e.target.value })} tabIndex={-1} autoComplete="off" aria-hidden="true" className="hidden" />
        <label className="block text-sm font-semibold text-slate-700">{t('qa.question')} *
          <textarea required minLength={10} maxLength={1000} rows={3} value={form.body} onChange={(e) => setForm({ ...form, body: e.target.value })} placeholder={t('qa.question_ph')} className={input} />
          {errors.body && <p className="mt-1 text-sm text-red-700">{errors.body[0]}</p>}
        </label>
        <div className="grid gap-3 sm:grid-cols-2">
          <label className="text-sm font-semibold text-slate-700">{t('qa.name')}
            <input maxLength={100} value={form.author_name} onChange={(e) => setForm({ ...form, author_name: e.target.value })} className={input} />
          </label>
          <label className="text-sm font-semibold text-slate-700">{t('qa.email')}
            <input type="email" dir="ltr" maxLength={150} value={form.author_email} onChange={(e) => setForm({ ...form, author_email: e.target.value })} className={input} />
            <span className="mt-1 block text-xs font-normal text-slate-500">{t('qa.email_hint')}</span>
            {errors.author_email && <span className="mt-1 block text-sm text-red-700">{errors.author_email[0]}</span>}
          </label>
        </div>
        <button type="submit" disabled={sending || form.body.trim().length < 10} className="rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800 disabled:opacity-50">
          {sending ? t('qa.sending') : t('qa.submit')}
        </button>
      </form>
    </section>
  );
}
