'use client';

import { useCallback, useEffect, useState } from 'react';
import { BadgeCheck, Check, Send, Trash2, UserRound, X } from 'lucide-react';
import { adminInput, AdminNotice, AdminShell, dangerButton, primaryButton, smallButton } from '@/components/admin/AdminShell';
import { fetchApi } from '@/lib/api';
import { formatDate } from '@/lib/i18n';
import { useLanguage } from '@/hooks/useLanguage';
import { useConfirm } from '@/components/ConfirmProvider';

type Filter = 'pending' | 'pending_answers' | 'published' | 'rejected';
type Status = 'pending' | 'published' | 'rejected';

interface Answer { id: number; body: string; is_official: boolean; author_name: string | null; status: Status; created_at: string; admin: { id: number; name: string } | null }
interface Question {
  id: number; body: string; author_name: string | null; author_email: string | null; status: Status; created_at: string;
  demarche: { id: number; title: string; slug: string } | null; answers: Answer[];
}

/** Modération des questions-réponses et réponses officielles. */
export default function QuestionsModerationPage() {
  const { language, t } = useLanguage();
  const confirm = useConfirm();
  const [filter, setFilter] = useState<Filter>('pending');
  const [questions, setQuestions] = useState<Question[]>([]);
  const [loaded, setLoaded] = useState(false);
  const [drafts, setDrafts] = useState<Record<number, string>>({});
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const load = useCallback(() => {
    setLoaded(false);
    fetchApi<{ data: Question[] }>(`/admin/questions?status=${filter}`)
      .then((res) => { setQuestions(res.data); setLoaded(true); })
      .catch((e: unknown) => { setError(e instanceof Error ? e.message : t('common.error_generic')); setLoaded(true); });
  }, [filter, t]);

  useEffect(load, [load]);

  const run = async (action: () => Promise<unknown>, done: string) => {
    setBusy(true);
    setError('');
    setMessage('');
    try {
      await action();
      setMessage(done);
      load();
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : t('common.error_generic'));
    } finally {
      setBusy(false);
    }
  };

  const moderateQuestion = (question: Question, status: 'published' | 'rejected') =>
    run(() => fetchApi(`/admin/questions/${question.id}`, { method: 'PATCH', body: JSON.stringify({ status }) }), t('adx.saved'));
  const moderateAnswer = (answer: Answer, status: 'published' | 'rejected') =>
    run(() => fetchApi(`/admin/answers/${answer.id}`, { method: 'PATCH', body: JSON.stringify({ status }) }), t('adx.saved'));
  const removeQuestion = async (question: Question) => {
    if (!(await confirm({ title: t('adx.confirm_delete', { name: question.body.slice(0, 40) }), tone: 'danger' }))) return;
    run(() => fetchApi(`/admin/questions/${question.id}`, { method: 'DELETE' }), t('adx.deleted'));
  };
  const answer = (question: Question) =>
    run(async () => {
      await fetchApi(`/admin/questions/${question.id}/answers`, { method: 'POST', body: JSON.stringify({ body: drafts[question.id] || '' }) });
      setDrafts((d) => ({ ...d, [question.id]: '' }));
    }, t('adx.saved'));

  const tab = (value: Filter, key: string) => (
    <button key={value} type="button" aria-pressed={filter === value} onClick={() => setFilter(value)} className={`rounded-full px-4 py-1.5 text-sm font-semibold ${filter === value ? 'bg-blue-700 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'}`}>
      {t(key)}
    </button>
  );

  return (
    <AdminShell title={t('adx.q_title')} intro={t('adx.q_intro')}>
      <div className="mb-5 flex flex-wrap gap-2">
        {tab('pending', 'adx.q_pending')}
        {tab('pending_answers', 'adx.q_pending_answers')}
        {tab('published', 'adx.q_published')}
        {tab('rejected', 'adx.q_rejected')}
      </div>
      <AdminNotice message={message} error={error} />
      {!loaded && <p role="status" className="text-slate-500">{t('adx.loading')}</p>}
      {loaded && questions.length === 0 && <p className="rounded-2xl border border-dashed border-slate-300 bg-white p-6 text-slate-600">{t('adx.q_empty')}</p>}

      <ul className="space-y-4">
        {questions.map((question) => (
          <li key={question.id} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex flex-wrap items-center gap-2 text-xs text-slate-500">
              <span className="rounded-full bg-slate-100 px-2 py-0.5 font-bold text-slate-700">{t(`adx.q_status_${question.status}`)}</span>
              <span>{question.author_name || t('qa.anonymous')} · {formatDate(language, question.created_at)}</span>
              {question.demarche && <span>· {t('adx.q_about', { title: question.demarche.title })}</span>}
            </div>
            <p className="mt-2 font-semibold text-slate-900">{question.body}</p>

            <div className="mt-3 flex flex-wrap gap-2">
              {question.status !== 'published' && <button type="button" disabled={busy} onClick={() => moderateQuestion(question, 'published')} className={smallButton}><Check size={14} aria-hidden="true" /> {t('adx.q_publish')}</button>}
              {question.status !== 'rejected' && <button type="button" disabled={busy} onClick={() => moderateQuestion(question, 'rejected')} className={smallButton}><X size={14} aria-hidden="true" /> {t('adx.q_reject')}</button>}
              <button type="button" disabled={busy} onClick={() => removeQuestion(question)} className={dangerButton}><Trash2 size={14} aria-hidden="true" /> {t('adx.delete')}</button>
            </div>

            {question.answers.length > 0 && (
              <ul className="mt-4 space-y-2">
                {question.answers.map((item) => (
                  <li key={item.id} className={`rounded-xl p-3 text-sm ${item.is_official ? 'border border-blue-200 bg-blue-50' : 'bg-slate-50'}`}>
                    <p className="mb-1 flex flex-wrap items-center gap-2 text-xs font-bold text-slate-600">
                      {item.is_official ? <><BadgeCheck size={14} aria-hidden="true" /> {t('adx.q_official')}{item.admin && ` · ${item.admin.name}`}</> : <><UserRound size={14} aria-hidden="true" /> {item.author_name || t('adx.q_community')}</>}
                      <span className="rounded-full bg-white px-2 py-0.5 font-semibold">{t(`adx.q_status_${item.status}`)}</span>
                    </p>
                    <p className="whitespace-pre-line text-slate-800">{item.body}</p>
                    {!item.is_official && (
                      <div className="mt-2 flex gap-2">
                        {item.status !== 'published' && <button type="button" disabled={busy} onClick={() => moderateAnswer(item, 'published')} className={smallButton}><Check size={14} aria-hidden="true" /> {t('adx.q_publish')}</button>}
                        {item.status !== 'rejected' && <button type="button" disabled={busy} onClick={() => moderateAnswer(item, 'rejected')} className={smallButton}><X size={14} aria-hidden="true" /> {t('adx.q_reject')}</button>}
                      </div>
                    )}
                  </li>
                ))}
              </ul>
            )}

            {question.status !== 'rejected' && (
              <div className="mt-4">
                <label className="block text-sm font-semibold text-slate-700" htmlFor={`answer-${question.id}`}>{t('adx.q_answer')}</label>
                <textarea id={`answer-${question.id}`} rows={3} maxLength={3000} value={drafts[question.id] || ''} onChange={(e) => setDrafts((d) => ({ ...d, [question.id]: e.target.value }))} placeholder={t('adx.q_answer_ph')} className={adminInput} />
                {question.author_email && <p className="mt-1 text-xs text-slate-500">{t('adx.q_email_note')}</p>}
                <button type="button" disabled={busy || (drafts[question.id] || '').trim().length < 5} onClick={() => answer(question)} className={`${primaryButton} mt-2`}>
                  <Send size={15} aria-hidden="true" className="rtl:-scale-x-100" /> {t('adx.q_send')}
                </button>
              </div>
            )}
          </li>
        ))}
      </ul>
    </AdminShell>
  );
}
