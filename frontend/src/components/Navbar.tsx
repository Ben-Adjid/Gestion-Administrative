'use client';

import { useEffect, useState } from 'react';
import Link from '@/components/AppLink';
import { LiteModeToggle } from '@/components/LiteModeToggle';
import SearchAutocomplete from '@/components/SearchAutocomplete';
import { usePathname } from 'next/navigation';
import { ArrowUpRight, Globe, Menu, Search, UserRound, X } from 'lucide-react';
import { useLanguage } from '@/hooks/useLanguage';
import { isLanguage, LANGUAGE_LABELS, LANGUAGES } from '@/lib/i18n';

// Barre principale (ordinateur)
// « Mon cas » et « Parcours » forment une seule rubrique ; « À propos » présente le concepteur et le projet.
const LINKS = [
  { href: '/', key: 'nav.home' },
  { href: '/parcours', key: 'casx.nav' },
  { href: '/calendrier', key: 'navx.calendar' },
  { href: '/questions', key: 'navx.questions' },
  { href: '/about', key: 'nav.about' },
  { href: '/credits', key: 'nav.credits' },
];

// Menu mobile : toutes les pages publiques
const MOBILE_LINKS = [
  ...LINKS,
  { href: '/villes', key: 'navx.cities' },
  { href: '/faq', key: 'nav.faq' },
  { href: '/contact', key: 'nav.contact' },
];

/** Lien actif : la page elle-même ou l’une de ses sous-pages (ex. /parcours/naissance). */
const isActive = (pathname: string, href: string) => (href === '/' ? pathname === '/' : pathname === href || pathname.startsWith(`${href}/`));

export const Navbar = () => {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [loggedIn, setLoggedIn] = useState(false);
  const { language, changeLanguage, t } = useLanguage();
  const pathname = usePathname();

  useEffect(() => {
    setMobileMenuOpen(false);
    try {
      setLoggedIn(!!localStorage.getItem('token'));
    } catch {
      setLoggedIn(false);
    }
  }, [pathname]);

  const languageSelect = (
    <label className="flex items-center gap-2">
      <Globe size={16} aria-hidden="true" className="text-slate-500" />
      <span className="sr-only">{t('nav.choose_language')}</span>
      <select
        value={language}
        onChange={(event) => isLanguage(event.target.value) && changeLanguage(event.target.value)}
        className="cursor-pointer rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm font-semibold text-slate-700 outline-none transition hover:border-blue-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
      >
        {LANGUAGES.map((code) => (
          <option key={code} value={code}>{LANGUAGE_LABELS[code]}</option>
        ))}
      </select>
    </label>
  );


  return (
    <header className="sticky top-0 z-50 print:hidden border-b border-slate-200/80 bg-white/95 shadow-sm shadow-slate-900/[0.03] backdrop-blur-xl">
      <div className="h-1 bg-gradient-to-r from-blue-800 via-amber-400 to-red-600" />
      <div className="mx-auto max-w-7xl px-4 sm:px-8 lg:px-10">
        <div className="flex h-[4.5rem] items-center justify-between gap-4">
          <Link href="/" className="group flex items-center gap-3" aria-label={`DemarchesTchad — ${t('nav.home')}`}>
            <span className="grid h-10 w-10 place-items-center rounded-xl bg-blue-900 text-sm font-extrabold tracking-tight text-white shadow-md shadow-blue-900/15 transition group-hover:bg-blue-800" dir="ltr">
              DT
            </span>
            <span className="text-lg font-bold tracking-tight text-slate-900 sm:text-xl" dir="ltr">
              Demarches<span className="text-blue-800">Tchad</span>
            </span>
          </Link>

          <nav className="hidden items-center gap-1 lg:flex" aria-label={t('nav.main')}>
            {LINKS.map(({ href, key }) => (
              <Link
                key={href}
                href={href}
                aria-current={isActive(pathname, href) ? 'page' : undefined}
                className={`whitespace-nowrap rounded-lg px-3 py-2 text-sm font-medium transition hover:bg-slate-50 hover:text-blue-800 ${isActive(pathname, href) ? 'bg-blue-50 text-blue-800' : 'text-slate-600'}`}
              >
                {t(key)}
              </Link>
            ))}
            <div className="mx-3 h-6 w-px bg-slate-200" />
            {languageSelect}
            {loggedIn && (
              <Link
                href="/dashboard"
                className="ms-3 inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-800"
              >
                <UserRound size={16} aria-hidden="true" /> {t('nav.my_space')}
              </Link>
            )}
            <Link
              href="/demarches"
              className="ms-2 inline-flex items-center gap-2 rounded-xl bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-900"
            >
              {t('nav.catalog')} <ArrowUpRight size={16} aria-hidden="true" className="rtl:-scale-x-100" />
            </Link>
          </nav>

          <button
            type="button"
            aria-label={mobileMenuOpen ? t('nav.close_menu') : t('nav.open_menu')}
            aria-expanded={mobileMenuOpen}
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            className="rounded-xl p-2.5 text-slate-700 transition hover:bg-slate-100 lg:hidden"
          >
            {mobileMenuOpen ? <X size={22} /> : <Menu size={22} />}
          </button>
        </div>

        {/* Barre de recherche persistante (cahier des charges, module 2). L'accueil a déjà sa grande barre. */}
        {pathname !== '/' && (
          <div className="pb-3">
            <SearchAutocomplete compact />
          </div>
        )}

        {mobileMenuOpen && (
          <nav className="space-y-1 border-t border-slate-100 pb-4 pt-3 lg:hidden" aria-label={t('nav.main')}>
            {MOBILE_LINKS.map(({ href, key }) => (
              <Link key={href} href={href} aria-current={isActive(pathname, href) ? 'page' : undefined} className={`block rounded-xl px-4 py-3 text-base font-medium transition hover:bg-blue-50 hover:text-blue-800 ${isActive(pathname, href) ? 'bg-blue-50 text-blue-800' : 'text-slate-700'}`}>
                {t(key)}
              </Link>
            ))}
            {loggedIn && (
              <Link href="/dashboard" className="flex items-center gap-2 rounded-xl px-4 py-3 text-base font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-800">
                <UserRound size={17} aria-hidden="true" /> {t('nav.my_space')}
              </Link>
            )}
            <Link href="/demarches" className="mt-2 flex items-center justify-between rounded-xl bg-blue-800 px-4 py-3 text-base font-semibold text-white">
              <span className="flex items-center gap-2"><Search size={16} aria-hidden="true" /> {t('nav.catalog_full')}</span>
              <ArrowUpRight size={16} aria-hidden="true" className="rtl:-scale-x-100" />
            </Link>
            <div className="px-4 pt-3">{languageSelect}</div>
            <div className="px-4 pt-4"><LiteModeToggle /></div>
          </nav>
        )}
      </div>
    </header>
  );
};
