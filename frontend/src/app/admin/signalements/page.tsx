'use client';

import { FormEvent, useCallback, useEffect, useState } from 'react';
import { Ban, Check, Download, MessageSquareText, Send } from 'lucide-react';
import Button from '@/components/Button';
import { AdminSidebar, isModeratorRole, useCurrentAdmin } from '@/components/AdminSidebar';
import { downloadAdminFile, fetchApi } from '@/lib/api';
import { formatDate } from '@/lib/i18n';
import { useLanguage } from '@/hooks/useLanguage';
import { useConfirm } from '@/components/ConfirmProvider';
import { Signalement } from '@/types';

type AdminSignalement = Signalement & { has_contact?: boolean };
const STATUSES: Array<Signalement['status']> = ['pending', 'investigating', 'resolved', 'rejected'];
const STATUS_STYLE: Record<Signalement['status'], string> = {
  pending: 'bg-amber-50 text-amber-800',
  investigating: 'bg-blue-50 text-blue-800',
  resolved: 'bg-emerald-50 text-emerald-800',
  rejected: 'bg-slate-100 text-slate-700',
};

export default function AdminSignalementsPage() {
  const { language, t } = useLanguage();
  const confirm = useConfirm();
  const currentUser = useCurrentAdmin();
  const isModerator = isModeratorRole(currentUser?.role);
  const [signalements, setSignalements] = useState<AdminSignalement[]>([]);
  const [statusFilter, setStatusFilter] = useState('');
  const [loading, setLoading] = useState(true);
  const [updatingId, setUpdatingId] = useState<number | null>(null);
  const [replyId, setReplyId] = useState<number | null>(null);
  const [reply, setReply] = useState('');
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');

  const load = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const query = new URLSearchParams({ per_page: '100' });
      if (statusFilter) query.set('status', statusFilter);
      const result = await fetchApi<{ data: AdminSignalement[] }>(`/admin/signalements?${query}`);
      setSignalements(result.data);
    } catch (err) {
      setError(err instanceof Error ? err.message : t('adm.load_error'));
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [statusFilter]);

  useEffect(() => { void load(); }, [load]);

  const update = async (signalement: AdminSignalement, status: Signalement['status'], adminNotes?: string) => {
    setUpdatingId(signalement.id);
    setError('');
    setNotice('');
    try {
      await fetchApi(`/admin/signalements/${signalement.id}/status`, {
        method: 'PATCH',
        body: JSON.stringify({ status, ...(adminNotes === undefined ? {} : { admin_notes: adminNotes }) }),
      });
      setReplyId(null);
      setReply('');
      setNotice(t(adminNotes !== undefined ? 'adm.s_done_replied' : status === 'rejected' ? 'adm.s_done_rejected' : 'adm.s_done_resolved'));
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : t('adm.action_error'));
    } finally {
      setUpdatingId(null);
    }
  };

  const submitReply = (event: FormEvent<HTMLFormElement>, signalement: AdminSignalement) => {
    event.preventDefault();
    const message = reply.trim();
    if (!message) {
      setError(t('adm.s_reply_empty'));
      return;
    }
    void update(signalement, 'investigating', message);
  };

  const exportCsv = async () => {
    try {
      await downloadAdminFile(`/admin/export/signalements${statusFilter ? `?status=${statusFilter}` : ''}`, `signalements_${new Date().toISOString().slice(0, 10)}.csv`);
    } catch {
      setError(t('adm.s_export_error'));
    }
  };

  return (
    <div className="flex min-h-screen bg-slate-50">
      <AdminSidebar user={currentUser} />
      <main className="min-w-0 flex-1 px-4 py-8 sm:px-8">
        <div className="mx-auto max-w-6xl">
          <div className="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
              <p className="text-sm font-semibold text-blue-700">{t('adm.kicker')}</p>
              <h1 className="mt-1 text-3xl font-bold tracking-tight text-slate-950">{t('adm.s_title')}</h1>
              <p className="mt-2 text-slate-600">{t(isModerator ? 'adm.s_intro_mod' : 'adm.s_intro_editor')}</p>
            </div>
            {isModerator && (
              <button type="button" onClick={exportCsv} className="inline-flex items-center gap-2 self-start rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                <Download size={17} aria-hidden="true" /> {t('adm.s_export')}
              </button>
            )}
          </div>

          <div className="mb-6 flex flex-wrap gap-2" role="group" aria-label={t('adm.s_filter')}>
            {['', ...STATUSES].map((status) => (
              <button
                key={status || 'all'}
                type="button"
                aria-pressed={statusFilter === status}
                onClick={() => setStatusFilter(status)}
                className={`rounded-full px-4 py-2 text-sm font-semibold transition ${statusFilter === status ? 'bg-blue-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:border-blue-300'}`}
              >
                {status ? t(`adm.s_status_${status}`) : t('adm.s_all')}
              </button>
            ))}
          </div>

          {error && <p role="alert" className="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{error}</p>}
          {notice && <p role="status" className="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{notice}</p>}

          {loading ? (
            <p role="status" className="rounded-xl bg-white p-8 text-center text-slate-600">{t('adm.loading')}</p>
          ) : signalements.length === 0 ? (
            <div className="rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-600">{t('adm.s_empty')}</div>
          ) : (
            <div className="space-y-4">
              {signalements.map((s) => {
                const open = s.status === 'pending' || s.status === 'investigating';
                return (
                  <article key={s.id} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div className="flex flex-col justify-between gap-4 sm:flex-row">
                      <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                          <span className={`rounded-full px-3 py-1 text-xs font-semibold ${STATUS_STYLE[s.status]}`}>{t(`adm.s_status_${s.status}`)}</span>
                          <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{t(`signal.type_${s.type}`)}</span>
                        </div>
                        <h2 className="mt-3 text-lg font-bold text-slate-900">{s.demarche_title || t('adm.s_general')}</h2>
                        <p className="mt-2 whitespace-pre-wrap text-sm leading-6 text-slate-700">{s.message}</p>
                        <p className="mt-3 text-sm text-slate-500">
                          {s.author_name || t('adm.s_citizen')}
                          {s.author_email ? <> · <span dir="ltr">{s.author_email}</span></> : ''}
                          {' · '}{formatDate(language, s.created_at)}
                        </p>
                        {s.admin_notes && (
                          <p className="mt-4 rounded-xl bg-blue-50 p-3 text-sm text-blue-950">
                            <span className="font-semibold">{t('adm.s_last_reply')} </span>{s.admin_notes}
                          </p>
                        )}
                      </div>

                      <div className="flex shrink-0 flex-wrap items-start gap-2">
                        {open && (isModerator ? (
                          <>
                            <Button variant="secondary" size="sm" onClick={() => { setReplyId(replyId === s.id ? null : s.id); setReply(''); }}>
                              <MessageSquareText size={15} aria-hidden="true" /> {t('adm.s_reply')}
                            </Button>
                            <Button size="sm" loading={updatingId === s.id} onClick={() => void update(s, 'resolved')}>
                              <Check size={15} aria-hidden="true" /> {t('adm.s_resolve')}
                            </Button>
                            <Button variant="danger" size="sm" loading={updatingId === s.id} onClick={async () => { if (await confirm({ title: t('adm.s_confirm_reject') })) void update(s, 'rejected'); }}>
                              <Ban size={15} aria-hidden="true" /> {t('adm.s_reject')}
                            </Button>
                          </>
                        ) : (
                          <span className="inline-flex items-center rounded-lg border border-slate-200 bg-slate-100 px-3 py-1.5 text-xs text-slate-600">{t('adm.s_readonly')}</span>
                        ))}
                      </div>
                    </div>

                    {replyId === s.id && (
                      <form onSubmit={(event) => submitReply(event, s)} className="mt-5 border-t border-slate-100 pt-5">
                        <label htmlFor={`reply-${s.id}`} className="mb-2 block text-sm font-semibold text-slate-800">
                          {t('adm.s_reply_label')}
                          {!s.author_email && <span className="ms-2 font-normal text-slate-500">({t('adm.s_no_email')})</span>}
                        </label>
                        <textarea
                          id={`reply-${s.id}`}
                          value={reply}
                          onChange={(event) => setReply(event.target.value)}
                          maxLength={1000}
                          rows={4}
                          required
                          placeholder={t('adm.s_reply_ph')}
                          className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-base text-slate-900 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                        />
                        <Button type="submit" loading={updatingId === s.id} className="mt-3">
                          <Send size={15} aria-hidden="true" className="rtl:-scale-x-100" /> {t('adm.s_send')}
                        </Button>
                      </form>
                    )}
                  </article>
                );
              })}
            </div>
          )}
        </div>
      </main>
    </div>
  );
}
