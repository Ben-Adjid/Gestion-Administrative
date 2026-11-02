'use client';

import { AlertTriangle, CalendarDays, HelpCircle, Mail, MapPin, MessageCircle, PlusCircle, Route } from 'lucide-react';
import AppLink from '@/components/AppLink';
import { AdminLink } from '@/components/AdminLink';
import { LiteModeToggle } from '@/components/LiteModeToggle';
import { Logo } from '@/components/Logo';
import { useLanguage } from '@/hooks/useLanguage';
import { whatsappLink } from '@/lib/whatsapp';

// Fiches réelles du catalogue (slugs normalisés).
const FREQUENT = [
  { slug: 'passeport-biometrique', fr: 'Passeport', en: 'Passport', ar: 'جواز السفر' },
  { slug: 'carte-didentite-nationale', fr: 'Carte nationale d’identité', en: 'National identity card', ar: 'بطاقة التعريف الوطنية' },
  { slug: 'acte-de-naissance', fr: 'Acte de naissance', en: 'Birth certificate', ar: 'شهادة الميلاد' },
  { slug: 'casier-judiciaire', fr: 'Casier judiciaire', en: 'Criminal record extract', ar: 'السجل العدلي' },
  { slug: 'permis-de-conduire', fr: 'Permis de conduire', en: 'Driving licence', ar: 'رخصة السياقة' },
] as const;

const EXPLORE = [
  { href: '/parcours', key: 'casx.nav', icon: Route },
  { href: '/villes', key: 'navx.cities', icon: MapPin },
  { href: '/calendrier', key: 'navx.calendar', icon: CalendarDays },
  { href: '/questions', key: 'navx.questions', icon: HelpCircle },
] as const;

const CONTACT_EMAIL = process.env.NEXT_PUBLIC_CONTACT_EMAIL || '';
const link = 'inline-flex items-center gap-2 text-slate-300 transition hover:text-white hover:underline';

export const Footer = () => {
  const { language, t } = useLanguage();
  const whatsapp = whatsappLink();

  return (
    <footer className="mt-20 print:hidden border-t border-slate-800 bg-gradient-to-br from-slate-950 via-[#0b1329] to-blue-950 text-slate-200">
      <div aria-hidden="true" className="h-1 bg-gradient-to-r from-blue-700 via-amber-400 to-red-600" />
      <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-4">
          <div className="space-y-4">
            <Logo size="md" className="brightness-110" inverted />
            <p className="leading-relaxed text-slate-300">{t('footer.description')}</p>
            <p className="text-sm text-amber-200/90">{t('footer.disclaimer')}</p>
          </div>

          <div>
            <h2 className="mb-4 border-b border-slate-700/60 pb-2 text-lg font-bold text-white">{t('footer.procedures')}</h2>
            <ul className="space-y-3">
              {FREQUENT.map((item) => (
                <li key={item.slug}>
                  <AppLink href={`/demarches/${item.slug}`} className="text-slate-300 transition hover:text-white hover:underline">
                    {item[language]}
                  </AppLink>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h2 className="mb-4 border-b border-slate-700/60 pb-2 text-lg font-bold text-white">{t('navx.explore')}</h2>
            <ul className="space-y-3">
              {EXPLORE.map(({ href, key, icon: Icon }) => (
                <li key={href}>
                  <AppLink href={href} className={link}><Icon size={17} aria-hidden="true" className="text-amber-400" /> {t(key)}</AppLink>
                </li>
              ))}
              {whatsapp && (
                <li>
                  <a href={whatsapp} target="_blank" rel="noreferrer noopener" className={link}>
                    <MessageCircle size={17} aria-hidden="true" className="text-amber-400" /> {t('navx.whatsapp')}
                  </a>
                </li>
              )}
            </ul>
          </div>

          <div>
            <h2 className="mb-4 border-b border-slate-700/60 pb-2 text-lg font-bold text-white">{t('footer.help')}</h2>
            <ul className="space-y-3">
              <li><AppLink href="/signalement" className={link}><AlertTriangle size={17} aria-hidden="true" className="text-amber-400" /> {t('footer.report')}</AppLink></li>
              <li><AppLink href="/signalement?type=missing" className={link}><PlusCircle size={17} aria-hidden="true" className="text-amber-400" /> {t('footer.missing')}</AppLink></li>
              <li><AppLink href="/contact" className={link}><Mail size={17} aria-hidden="true" className="text-amber-400" /> {t('footer.contact')}</AppLink></li>
              {CONTACT_EMAIL && <li className="ps-6 text-sm text-slate-400" dir="ltr">{CONTACT_EMAIL}</li>}
            </ul>
            <div className="mt-6 rounded-xl border border-slate-700/60 p-3">
              <LiteModeToggle inverted />
            </div>
          </div>
        </div>

        <div className="mt-12 flex flex-col items-center justify-between gap-4 border-t border-slate-800 pt-8 text-sm text-slate-400 sm:flex-row">
          <p>© {new Date().getFullYear()} {t('footer.rights')}</p>
          <div className="flex flex-wrap items-center justify-center gap-5">
            <AppLink href="/demarches" className="transition hover:text-white">{t('footer.catalog')}</AppLink>
            <AppLink href="/faq" className="transition hover:text-white">{t('nav.faq')}</AppLink>
            <AppLink href="/regles" className="transition hover:text-white">Règles communes</AppLink>
            <AppLink href="/accessibility" className="transition hover:text-white">{t('nav.accessibility')}</AppLink>
            <AppLink href="/confidentialite" className="transition hover:text-white">{t('nav.privacy')}</AppLink>
            <AdminLink />
          </div>
        </div>
      </div>
    </footer>
  );
};
