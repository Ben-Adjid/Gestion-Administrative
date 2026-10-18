'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import {
  Activity, AlertTriangle, ArrowLeftRight, BarChart3, CalendarDays, ChevronDown, ExternalLink, FileClock, FileSpreadsheet, FileText, Folder,
  Globe, HelpCircle, KeyRound, LayoutDashboard, LogOut, MapPin, Menu, MessageSquareText, Route, SearchX, UserCog, Users, X,
  type LucideIcon,
} from 'lucide-react';
import { fetchApi } from '@/lib/api';
import { isLanguage, LANGUAGE_LABELS, LANGUAGES } from '@/lib/i18n';
import { useLanguage } from '@/hooks/useLanguage';

export interface AdminUser {
  id: number;
  name: string;
  email: string;
  role: 'super_admin' | 'admin' | 'editor';
}

/** Compte connecté, tel que validé par le serveur dans le layout admin (/admin/auth/me). */
export function useCurrentAdmin(): AdminUser | null {
  const [admin, setAdmin] = useState<AdminUser | null>(null);
  useEffect(() => {
    try {
      const raw = localStorage.getItem('admin');
      if (raw) setAdmin(JSON.parse(raw));
    } catch {
      setAdmin(null);
    }
  }, []);
  return admin;
}

export function isModeratorRole(role?: AdminUser['role']): boolean {
  return role === 'super_admin' || role === 'admin';
}

const ROLE_BADGES: Record<AdminUser['role'], string> = {
  super_admin: 'bg-purple-100 text-purple-800 border-purple-200',
  admin: 'bg-blue-100 text-blue-800 border-blue-200',
  editor: 'bg-amber-100 text-amber-800 border-amber-200',
};

type Access = 'all' | 'moderator' | 'super';
interface Item { href: string; key: string; icon: LucideIcon; access: Access }
interface Group { id: string; key: string; items: Item[] }

// Menu rangé par groupes repliables : la barre reste courte même avec beaucoup de pages.
const GROUPS: Group[] = [
  {
    id: 'content', key: 'adx.g_content', items: [
      { href: '/admin/demarches', key: 'admin.demarches', icon: FileText, access: 'all' },
      { href: '/admin/categories', key: 'admin.categories', icon: Folder, access: 'moderator' },
      { href: '/admin/parcours', key: 'adx.journeys', icon: Route, access: 'moderator' },
      { href: '/admin/villes', key: 'adx.cities', icon: MapPin, access: 'moderator' },
      { href: '/admin/calendrier', key: 'adx.calendar', icon: CalendarDays, access: 'moderator' },
      { href: '/admin/validite', key: 'adx.dv_menu', icon: FileClock, access: 'moderator' },
      { href: '/admin/synonymes', key: 'adx.synonyms', icon: ArrowLeftRight, access: 'moderator' },
      { href: '/admin/import', key: 'adx.import', icon: FileSpreadsheet, access: 'moderator' },
    ],
  },
  {
    id: 'public', key: 'adx.g_public', items: [
      { href: '/admin/signalements', key: 'admin.signalements', icon: AlertTriangle, access: 'all' },
      { href: '/admin/avis', key: 'adx.reviews', icon: MessageSquareText, access: 'moderator' },
      { href: '/admin/questions', key: 'adx.questions', icon: HelpCircle, access: 'moderator' },
      { href: '/admin/recherches', key: 'adx.searches', icon: SearchX, access: 'all' },
    ],
  },
  {
    id: 'steering', key: 'adx.g_steering', items: [
      { href: '/admin/analytics', key: 'admin.analytics', icon: BarChart3, access: 'moderator' },
      { href: '/admin/users', key: 'admin.team', icon: Users, access: 'super' },
      { href: '/admin/activity-logs', key: 'admin.audit', icon: Activity, access: 'super' },
      { href: '/admin/cles-api', key: 'adx.api_keys', icon: KeyRound, access: 'super' },
    ],
  },
];

const OPEN_KEY = 'admin_menu_open';

export function AdminSidebar({ user }: { user: AdminUser | null }) {
  const pathname = usePathname();
  const router = useRouter();
  const { language, changeLanguage, t } = useLanguage();
  const [open, setOpen] = useState<string[]>([]);
  const [drawer, setDrawer] = useState(false);

  // Groupes dépliés : celui de la page courante + ceux ouverts précédemment.
  useEffect(() => {
    let saved: string[] = [];
    try {
      saved = JSON.parse(localStorage.getItem(OPEN_KEY) || '[]');
    } catch {
      saved = [];
    }
    const current = GROUPS.find((group) => group.items.some((item) => pathname.startsWith(item.href)))?.id;
    setOpen(Array.from(new Set([...(Array.isArray(saved) ? saved : []), ...(current ? [current] : [])])));
    setDrawer(false);
  }, [pathname]);

  const toggleGroup = (id: string) => {
    const next = open.includes(id) ? open.filter((g) => g !== id) : [...open, id];
    setOpen(next);
    try {
      localStorage.setItem(OPEN_KEY, JSON.stringify(next));
    } catch {
      // Préférence non conservée : sans conséquence.
    }
  };

  const handleLogout = async () => {
    try {
      await fetchApi('/admin/auth/logout', { method: 'POST' });
    } catch {
      // Le jeton est supprimé localement même si le serveur est injoignable.
    } finally {
      localStorage.removeItem('admin_token');
      localStorage.removeItem('admin');
      router.replace('/admin/login');
    }
  };

  if (!user) return null;

  const allowed = (access: Access) => access === 'all'
    || (access === 'moderator' && isModeratorRole(user.role))
    || (access === 'super' && user.role === 'super_admin');

  const link = (href: string, key: string, Icon: LucideIcon) => {
    const active = pathname === href || pathname.startsWith(`${href}/`);
    return (
      <Link
        key={href}
        href={href}
        aria-current={active ? 'page' : undefined}
        className={`flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold transition ${active ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800/80 hover:text-white'}`}
      >
        <Icon size={17} aria-hidden="true" />
        <span className="truncate">{t(key)}</span>
      </Link>
    );
  };

  const menu = (
    <nav aria-label={t('admin.menu')} className="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 py-3">
      {link('/admin/dashboard', 'admin.dashboard', LayoutDashboard)}
      {GROUPS.map((group) => {
        const items = group.items.filter((item) => allowed(item.access));
        if (items.length === 0) return null;
        const expanded = open.includes(group.id);
        return (
          <div key={group.id} className="pt-1">
            <button
              type="button"
              onClick={() => toggleGroup(group.id)}
              aria-expanded={expanded}
              className="flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-500 hover:text-slate-300"
            >
              {t(group.key)}
              <ChevronDown size={14} aria-hidden="true" className={`transition ${expanded ? 'rotate-180' : ''}`} />
            </button>
            {expanded && <div className="mt-0.5 space-y-0.5">{items.map((item) => link(item.href, item.key, item.icon))}</div>}
          </div>
        );
      })}
      <div className="pt-1">{link('/admin/profile', 'adm.p_title', UserCog)}</div>
    </nav>
  );

  const footer = (
    <div className="space-y-1 border-t border-slate-800 px-3 py-3">
      <div className="flex items-center gap-2 px-1">
        <label className="flex flex-1 items-center gap-2 text-xs text-slate-400">
          <Globe size={14} aria-hidden="true" />
          <span className="sr-only">{t('nav.choose_language')}</span>
          <select
            value={language}
            onChange={(event) => isLanguage(event.target.value) && changeLanguage(event.target.value)}
            className="w-full rounded-lg border border-slate-700 bg-slate-800 px-2 py-1.5 text-xs font-semibold text-slate-200"
          >
            {LANGUAGES.map((code) => <option key={code} value={code}>{LANGUAGE_LABELS[code]}</option>)}
          </select>
        </label>
        <Link href="/" target="_blank" title={t('admin.public_site')} aria-label={t('admin.public_site')} className="rounded-lg p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white">
          <ExternalLink size={15} aria-hidden="true" />
        </Link>
      </div>
      <button type="button" onClick={handleLogout} className="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-xs font-bold text-red-400 transition hover:bg-red-950/40 hover:text-red-300">
        <LogOut size={16} aria-hidden="true" className="rtl:-scale-x-100" /> {t('admin.logout')}
      </button>
    </div>
  );

  const header = (
    <div className="border-b border-slate-800 px-4 py-4">
      <Link href="/" className="flex items-center gap-2" dir="ltr">
        <span className="grid h-7 w-7 place-items-center rounded-lg bg-blue-600 text-[11px] font-black text-white">DT</span>
        <span className="text-base font-extrabold tracking-tight text-white">Demarches<span className="text-amber-400">Tchad</span></span>
      </Link>
      <div className="mt-3">
        <p className="truncate text-sm font-semibold text-white" title={`${t('admin.connected_as')} ${user.name}`}>{user.name}</p>
        <span className={`mt-1 inline-block rounded-full border px-2 py-0.5 text-[10px] font-bold ${ROLE_BADGES[user.role]}`}>
          {t(`admin.role_${user.role}`)}
        </span>
      </div>
    </div>
  );

  return (
    <>
      {/* Mobile : bouton Menu fixé en bas d'écran, qui ouvre le menu complet */}
      <div className="fixed inset-x-0 bottom-0 z-40 flex items-center justify-between border-t border-slate-800 bg-slate-900 px-4 py-2 md:hidden">
        <Link href="/admin/dashboard" className="flex items-center gap-2 text-sm font-semibold text-slate-300">
          <LayoutDashboard size={18} aria-hidden="true" /> {t('admin.dashboard')}
        </Link>
        <button type="button" onClick={() => setDrawer(true)} aria-expanded={drawer} className="flex items-center gap-2 rounded-lg bg-slate-800 px-4 py-2 text-sm font-bold text-white">
          <Menu size={18} aria-hidden="true" /> {t('admin.menu')}
        </button>
      </div>
      {drawer && (
        <div className="fixed inset-0 z-50 md:hidden" role="dialog" aria-modal="true" aria-label={t('admin.menu')}>
          <button type="button" aria-label={t('nav.close_menu')} onClick={() => setDrawer(false)} className="absolute inset-0 bg-slate-950/60" />
          <aside className="absolute inset-y-0 start-0 flex w-72 max-w-[85vw] flex-col bg-slate-900 text-slate-200 shadow-xl">
            <button type="button" onClick={() => setDrawer(false)} aria-label={t('nav.close_menu')} className="absolute end-3 top-3 rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white">
              <X size={20} aria-hidden="true" />
            </button>
            {header}
            {menu}
            {footer}
          </aside>
        </div>
      )}

      {/* Bureau : hauteur de l'écran, fixée pendant le défilement ; seul le menu défile s'il manque de place. */}
      <aside className="sticky top-0 hidden h-screen w-60 shrink-0 flex-col self-start border-e border-slate-800 bg-slate-900 text-slate-200 md:flex">
        {header}
        {menu}
        {footer}
      </aside>
    </>
  );
}
