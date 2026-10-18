'use client';

import Link from '@/components/AppLink';
import { usePathname, useRouter } from 'next/navigation';
import { Bell, Heart, KeyRound, LayoutDashboard, LogOut, UserRound } from 'lucide-react';
import { fetchApi } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

const LINKS = [
  { href: '/dashboard', key: 'citizen.nav_dashboard', icon: LayoutDashboard },
  { href: '/dashboard/documents', key: 'citizen.nav_documents', icon: Bell },
  { href: '/dashboard/favorites', key: 'citizen.nav_favorites', icon: Heart },
  { href: '/dashboard/profile', key: 'citizen.nav_profile', icon: UserRound },
  { href: '/dashboard/change-password', key: 'citizen.nav_password', icon: KeyRound },
];

export function clearCitizenSession() {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  localStorage.removeItem('demarches_token');
}

export default function CitizenNav() {
  const pathname = usePathname();
  const router = useRouter();
  const { t } = useLanguage();

  const logout = async () => {
    try {
      await fetchApi('/auth/logout', { method: 'POST' });
    } catch {
      // Le jeton est supprimé localement même si le serveur est injoignable.
    } finally {
      clearCitizenSession();
      router.replace('/auth/login');
    }
  };

  return (
    <nav aria-label={t('citizen.nav_label')} className="mb-8 border-b border-slate-200">
      <ul className="-mb-px flex gap-1 overflow-x-auto">
        {LINKS.map(({ href, key, icon: Icon }) => {
          const active = pathname === href;
          return (
            <li key={href} className="shrink-0">
              <Link
                href={href}
                aria-current={active ? 'page' : undefined}
                className={`flex items-center gap-2 border-b-2 px-3 py-3 text-sm font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-700 ${
                  active ? 'border-blue-700 text-blue-800' : 'border-transparent text-slate-600 hover:border-slate-300 hover:text-slate-900'
                }`}
              >
                <Icon aria-hidden="true" size={17} />
                {t(key)}
              </Link>
            </li>
          );
        })}
        <li className="ms-auto shrink-0">
          <button
            type="button"
            onClick={logout}
            className="flex items-center gap-2 px-3 py-3 text-sm font-semibold text-red-700 hover:text-red-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-700"
          >
            <LogOut aria-hidden="true" size={17} className="rtl:-scale-x-100" />
            {t('citizen.logout')}
          </button>
        </li>
      </ul>
    </nav>
  );
}
