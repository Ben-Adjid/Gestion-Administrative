'use client';

import { FormEvent, useCallback, useEffect, useState } from 'react';
import { Edit3, Eye, EyeOff, KeyRound, Plus, Power, Trash2, Unlock, X } from 'lucide-react';
import { AdminSidebar, useCurrentAdmin } from '@/components/AdminSidebar';
import { fetchApi } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';
import { useConfirm } from '@/components/ConfirmProvider';

type Role = 'super_admin' | 'admin' | 'editor';

interface TeamMember {
  id: number;
  name: string;
  email: string;
  role: Role;
  is_active: boolean;
  is_locked?: boolean;
}

type Modal = { type: 'create' } | { type: 'edit'; user: TeamMember } | { type: 'password'; user: TeamMember } | null;

const input = 'mt-1.5 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-base text-slate-900 focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100';

export default function AdminUsersPage() {
  const { t } = useLanguage();
  const confirm = useConfirm();
  const currentUser = useCurrentAdmin();
  const [users, setUsers] = useState<TeamMember[]>([]);
  const [loading, setLoading] = useState(true);
  const [modal, setModal] = useState<Modal>(null);
  const [form, setForm] = useState({ name: '', email: '', password: '', role: 'editor' as Role });
  const [showPassword, setShowPassword] = useState(false);
  const [formError, setFormError] = useState('');
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [notice, setNotice] = useState('');

  const load = useCallback(async () => {
    try {
      const response = await fetchApi<{ data: TeamMember[] }>('/admin/users');
      setUsers(response.data || []);
    } catch (err) {
      setError(err instanceof Error ? err.message : t('adm.load_error'));
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    if (currentUser?.role === 'super_admin') void load();
  }, [currentUser, load]);

  const openModal = (next: Modal) => {
    setFormError('');
    setShowPassword(false);
    if (next?.type === 'edit') setForm({ name: next.user.name, email: next.user.email, password: '', role: next.user.role });
    else if (next?.type === 'password') setForm((f) => ({ ...f, password: '' }));
    else setForm({ name: '', email: '', password: '', role: 'editor' });
    setModal(next);
  };

  const run = async (action: () => Promise<unknown>, successKey: string) => {
    setError('');
    setNotice('');
    try {
      await action();
      setNotice(t(successKey));
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : t('adm.action_error'));
    }
  };

  const submit = async (event: FormEvent) => {
    event.preventDefault();
    if (!modal) return;
    setSaving(true);
    setFormError('');
    try {
      if (modal.type === 'create') {
        await fetchApi('/admin/users', { method: 'POST', body: JSON.stringify(form) });
      } else if (modal.type === 'edit') {
        await fetchApi(`/admin/users/${modal.user.id}`, { method: 'PUT', body: JSON.stringify({ name: form.name, email: form.email, role: form.role }) });
      } else {
        await fetchApi(`/admin/users/${modal.user.id}/reset-password`, { method: 'POST', body: JSON.stringify({ password: form.password }) });
      }
      setNotice(t(modal.type === 'password' ? 'adm.u_done_reset' : 'adm.u_done_saved'));
      setModal(null);
      await load();
    } catch (err) {
      setFormError(err instanceof Error ? err.message : t('adm.action_error'));
    } finally {
      setSaving(false);
    }
  };

  const toggleActive = async (user: TeamMember) => {
    if (user.is_active && !(await confirm({ title: t('adm.u_confirm_toggle_off', { name: user.name }) }))) return;
    void run(() => fetchApi(`/admin/users/${user.id}`, { method: 'PUT', body: JSON.stringify({ is_active: !user.is_active }) }), 'adm.u_done_toggled');
  };

  const remove = async (user: TeamMember) => {
    if (!(await confirm({ title: t('adm.u_confirm_delete', { name: user.name, email: user.email }), tone: 'danger' }))) return;
    void run(() => fetchApi(`/admin/users/${user.id}`, { method: 'DELETE' }), 'adm.u_done_deleted');
  };

  const unlock = (user: TeamMember) => void run(() => fetchApi(`/admin/users/${user.id}/unlock`, { method: 'POST' }), 'adm.u_done_unlocked');

  const actionButton = 'inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-bold disabled:opacity-40';

  return (
    <div className="flex min-h-screen bg-slate-50">
      <AdminSidebar user={currentUser} />
      <main className="min-w-0 flex-1 px-4 py-8 sm:px-8">
        <div className="mx-auto max-w-7xl">
          <div className="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
              <p className="text-sm font-semibold text-blue-700">{t('adm.u_kicker')}</p>
              <h1 className="mt-1 text-3xl font-bold tracking-tight text-slate-950">{t('adm.u_title', { n: users.length })}</h1>
              <p className="mt-2 text-sm text-slate-600">{t('adm.u_intro')}</p>
            </div>
            <button type="button" onClick={() => openModal({ type: 'create' })} className="inline-flex items-center gap-2 self-start rounded-xl bg-blue-800 px-5 py-3 font-bold text-white shadow hover:bg-blue-900">
              <Plus className="h-5 w-5" aria-hidden="true" /> {t('adm.u_new')}
            </button>
          </div>

          {error && <p role="alert" className="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">{error}</p>}
          {notice && <p role="status" className="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{notice}</p>}

          {loading ? (
            <p role="status" className="rounded-xl bg-white p-8 text-center text-slate-600">{t('adm.loading')}</p>
          ) : users.length === 0 ? (
            <p className="rounded-xl border border-slate-200 bg-white p-8 text-center text-slate-600">{t('adm.u_empty')}</p>
          ) : (
            <div className="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
              <table className="w-full min-w-[720px] text-start">
                <thead className="border-b border-slate-200 bg-slate-50 text-sm">
                  <tr>
                    <th scope="col" className="px-5 py-3 text-start font-bold">{t('adm.u_name')}</th>
                    <th scope="col" className="px-5 py-3 text-start font-bold">{t('adm.u_email')}</th>
                    <th scope="col" className="px-5 py-3 text-start font-bold">{t('adm.u_role')}</th>
                    <th scope="col" className="px-5 py-3 text-start font-bold">{t('adm.u_status')}</th>
                    <th scope="col" className="px-5 py-3 text-start font-bold">{t('adm.u_actions')}</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {users.map((user) => {
                    const isSelf = user.id === currentUser?.id;
                    return (
                      <tr key={user.id} className="hover:bg-slate-50">
                        <td className="px-5 py-4 text-sm font-medium">
                          {user.name} {isSelf && <span className="text-xs text-slate-500">({t('adm.u_you')})</span>}
                        </td>
                        <td className="px-5 py-4 text-sm text-slate-600" dir="ltr">{user.email}</td>
                        <td className="px-5 py-4 text-sm">
                          <span className="rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-800">{t(`admin.role_${user.role}`)}</span>
                        </td>
                        <td className="px-5 py-4 text-sm">
                          <span className={`rounded-full px-3 py-1 text-xs font-bold ${user.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>
                            {t(user.is_active ? 'adm.u_active' : 'adm.u_inactive')}
                          </span>
                          {user.is_locked && <span className="ms-2 rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-700">{t('adm.u_locked')}</span>}
                        </td>
                        <td className="px-5 py-4">
                          <div className="flex flex-wrap gap-1.5">
                            <button type="button" disabled={isSelf} onClick={() => openModal({ type: 'edit', user })} className={`${actionButton} bg-blue-50 text-blue-700 hover:bg-blue-100`}>
                              <Edit3 className="h-3.5 w-3.5" aria-hidden="true" /> {t('common.edit')}
                            </button>
                            <button type="button" onClick={() => openModal({ type: 'password', user })} className={`${actionButton} bg-purple-50 text-purple-700 hover:bg-purple-100`}>
                              <KeyRound className="h-3.5 w-3.5" aria-hidden="true" /> {t('adm.u_reset')}
                            </button>
                            <button type="button" disabled={isSelf} onClick={() => toggleActive(user)} className={`${actionButton} bg-amber-50 text-amber-800 hover:bg-amber-100`}>
                              <Power className="h-3.5 w-3.5" aria-hidden="true" /> {t(user.is_active ? 'adm.u_deactivate' : 'adm.u_activate')}
                            </button>
                            {user.is_locked && (
                              <button type="button" onClick={() => unlock(user)} className={`${actionButton} bg-emerald-50 text-emerald-700 hover:bg-emerald-100`}>
                                <Unlock className="h-3.5 w-3.5" aria-hidden="true" /> {t('adm.u_unlock')}
                              </button>
                            )}
                            <button type="button" disabled={isSelf} onClick={() => remove(user)} className={`${actionButton} bg-red-50 text-red-700 hover:bg-red-100`}>
                              <Trash2 className="h-3.5 w-3.5" aria-hidden="true" /> {t('common.delete')}
                            </button>
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </main>

      {modal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="team-modal-title">
          <div className="w-full max-w-md rounded-2xl bg-white shadow-xl">
            <div className="flex items-center justify-between border-b border-slate-200 px-6 py-4">
              <h2 id="team-modal-title" className="text-xl font-bold">
                {modal.type === 'create' ? t('adm.u_modal_add') : modal.type === 'edit' ? t('adm.u_modal_edit') : t('adm.u_modal_reset', { name: modal.user.name })}
              </h2>
              <button type="button" onClick={() => setModal(null)} aria-label={t('common.close')}><X className="h-6 w-6" /></button>
            </div>

            <form onSubmit={submit} className="space-y-5 p-6">
              {formError && <p role="alert" className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{formError}</p>}

              {modal.type !== 'password' && (
                <>
                  <label className="block text-sm font-semibold text-slate-700">{t('adm.u_name')}
                    <input required maxLength={255} value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className={input} />
                  </label>
                  <label className="block text-sm font-semibold text-slate-700">{t('adm.u_email')}
                    <input type="email" required dir="ltr" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} className={input} />
                  </label>
                  <label className="block text-sm font-semibold text-slate-700">{t('adm.u_role')}
                    <select value={form.role} onChange={(e) => setForm({ ...form, role: e.target.value as Role })} className={input}>
                      <option value="editor">{t('admin.role_editor')}</option>
                      <option value="admin">{t('admin.role_admin')}</option>
                      <option value="super_admin">{t('admin.role_super_admin')}</option>
                    </select>
                  </label>
                </>
              )}

              {modal.type !== 'edit' && (
                <label className="block text-sm font-semibold text-slate-700">{modal.type === 'password' ? t('adm.u_set_password') : t('adm.u_password')}
                  <span className="relative block">
                    <input type={showPassword ? 'text' : 'password'} required minLength={12} autoComplete="new-password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} className={`${input} pe-11`} />
                    <button type="button" onClick={() => setShowPassword(!showPassword)} aria-label={showPassword ? 'hide' : 'show'} className="absolute end-3 top-1/2 mt-0.5 -translate-y-1/2 text-slate-500">
                      {showPassword ? <EyeOff className="h-5 w-5" /> : <Eye className="h-5 w-5" />}
                    </button>
                  </span>
                  <span className="mt-1 block text-xs font-normal text-slate-500">{t('adm.u_password_hint')}</span>
                </label>
              )}

              <div className="flex gap-3 pt-2">
                <button type="submit" disabled={saving} className="flex-1 rounded-lg bg-blue-800 px-4 py-3 font-bold text-white hover:bg-blue-900 disabled:opacity-50">
                  {saving ? t('common.saving') : modal.type === 'create' ? t('adm.u_create') : t('common.save')}
                </button>
                <button type="button" onClick={() => setModal(null)} className="flex-1 rounded-lg bg-slate-100 px-4 py-3 font-bold text-slate-900 hover:bg-slate-200">
                  {t('common.cancel')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
