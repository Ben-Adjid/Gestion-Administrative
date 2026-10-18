'use client';

import { useEffect, useState } from 'react';
import Link from '@/components/AppLink';
import { LifeEventIcon } from '@/components/LifeEventIcon';
import { useLifeEvents } from '@/hooks/useLifeEvents';
import { AlertTriangle, ArrowRight, ArrowUpRight, BellRing, Building2, Clock3, FileCheck2, Sparkles } from 'lucide-react';
import SearchAutocomplete from '@/components/SearchAutocomplete';
import { API_V1_URL } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';
import { Language } from '@/lib/i18n';

interface Category {
  id: number;
  name: string;
  slug: string;
  demarches_count?: number;
}

const QUICK_LINKS: Array<{ slug: string; color: string; label: Record<Language, string> }> = [
  { slug: 'passeport-biometrique', color: 'bg-blue-100 text-blue-800', label: { fr: 'Passeport', en: 'Passport', ar: 'جواز السفر' } },
  { slug: 'carte-didentite-nationale', color: 'bg-amber-100 text-amber-800', label: { fr: 'Carte nationale d’identité', en: 'National identity card', ar: 'بطاقة التعريف الوطنية' } },
  { slug: 'acte-de-naissance', color: 'bg-emerald-100 text-emerald-800', label: { fr: 'Acte de naissance', en: 'Birth certificate', ar: 'شهادة الميلاد' } },
];

const BENEFITS = [
  { icon: FileCheck2, title: 'home.b1_title', text: 'home.b1_text', style: 'bg-blue-50 text-blue-800 ring-blue-100' },
  { icon: Clock3, title: 'home.b2_title', text: 'home.b2_text', style: 'bg-amber-50 text-amber-700 ring-amber-100' },
  { icon: Building2, title: 'home.b3_title', text: 'home.b3_text', style: 'bg-emerald-50 text-emerald-700 ring-emerald-100' },
];

export default function HomePage() {
  const { language, t } = useLanguage();
  const { events: lifeEvents } = useLifeEvents();
  const [categories, setCategories] = useState<Category[] | null>(null);

  useEffect(() => {
    const controller = new AbortController();
    fetch(`${API_V1_URL}/categories?lang=${language}`, { signal: controller.signal, headers: { Accept: 'application/json' } })
      .then((res) => (res.ok ? res.json() : { data: [] }))
      .then((json: { data: Category[] }) => setCategories(json.data.filter((c) => (c.demarches_count ?? 0) > 0)))
      .catch(() => setCategories([]));
    return () => controller.abort();
  }, [language]);

  return (
    <main className="overflow-hidden bg-[#f8fafc] text-slate-900">
      <section className="relative isolate">
        <div className="absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-blue-100/80 via-[#f8fafc] to-[#f8fafc]" />
        <div className="mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-8 sm:py-24 lg:grid-cols-[1.1fr_0.9fr] lg:px-10">
          <div>
            <div className="mb-6 inline-flex items-center gap-2 rounded-full border border-blue-200/80 bg-white/80 px-4 py-2 text-sm font-semibold text-blue-900 shadow-sm">
              <span className="h-2 w-2 rounded-full bg-amber-400" aria-hidden="true" />
              {t('home.badge')}
            </div>
            <h1 className="max-w-3xl text-4xl font-bold leading-[1.1] tracking-tight text-slate-950 sm:text-6xl">
              {t('home.title_1')}
              <span className="mt-2 block text-blue-800">{t('home.title_2')}</span>
            </h1>
            <p className="mt-6 max-w-xl text-lg leading-8 text-slate-600">{t('home.intro')}</p>

            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
              <Link href="/demarches" className="group inline-flex items-center justify-center gap-2 rounded-xl bg-blue-800 px-6 py-3.5 text-base font-semibold text-white shadow-lg shadow-blue-900/15 transition hover:bg-blue-900">
                {t('home.cta_explore')}
                <ArrowRight size={18} aria-hidden="true" className="transition-transform group-hover:translate-x-1 rtl:-scale-x-100 rtl:group-hover:-translate-x-1" />
              </Link>
              <Link href="/faq" className="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/80 px-6 py-3.5 text-base font-semibold text-slate-700 transition hover:border-blue-200 hover:text-blue-800">
                {t('home.cta_how')}
              </Link>
            </div>
            <p className="mt-6 text-sm text-slate-500">{t('home.reassurance')}</p>
          </div>

          <div className="relative mx-auto w-full max-w-lg">
            <div className="relative rounded-[1.75rem] border border-white bg-white p-6 shadow-2xl shadow-blue-950/10 sm:p-8">
              <p className="text-sm font-semibold text-blue-800">{t('home.card_kicker')}</p>
              <h2 className="mt-2 text-2xl font-bold tracking-tight text-slate-900">{t('home.card_title')}</h2>
              <div className="mt-6">
                <SearchAutocomplete placeholder={t('home.search_placeholder')} />
              </div>
              <h3 className="mt-6 text-xs font-bold uppercase tracking-wider text-slate-500">{t('home.quick_title')}</h3>
              <div className="mt-2 space-y-2">
                {QUICK_LINKS.map((item) => (
                  <Link key={item.slug} href={`/demarches/${item.slug}`} className="group flex items-center gap-4 rounded-xl p-3 transition hover:bg-slate-50">
                    <span className={`grid h-11 w-11 shrink-0 place-items-center rounded-xl ${item.color}`}>
                      <FileCheck2 size={20} aria-hidden="true" />
                    </span>
                    <span className="min-w-0 flex-1 truncate text-sm font-semibold text-slate-800">{item.label[language]}</span>
                    <ArrowUpRight size={17} aria-hidden="true" className="text-slate-400 transition group-hover:text-blue-700 rtl:-scale-x-100" />
                  </Link>
                ))}
              </div>
              <p className="mt-5 border-t border-slate-100 pt-5 text-xs leading-5 text-slate-500">{t('home.card_footer')}</p>
            </div>
          </div>
        </div>
      </section>

      <section className="px-4 py-12 sm:px-8 lg:px-10">
        <div className="mx-auto max-w-7xl">
          <div className="flex flex-col gap-5 rounded-3xl bg-gradient-to-br from-blue-900 to-blue-700 p-7 text-white shadow-lg sm:flex-row sm:items-center sm:justify-between sm:p-10">
            <div>
              <h2 className="text-2xl font-bold sm:text-3xl">{t('mycase.title')}</h2>
              <p className="mt-2 max-w-2xl text-blue-100">{t('mycase.home_text')}</p>
            </div>
            <Link href="/parcours" className="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-amber-400 px-6 py-3 font-bold text-slate-950 hover:bg-amber-300">
              {t('mycase.home_button')} <ArrowRight size={18} aria-hidden="true" className="rtl:-scale-x-100" />
            </Link>
          </div>

          {lifeEvents.length > 0 && (
            <div className="mt-10">
              <div className="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                  <h2 className="text-2xl font-bold text-slate-950">{t('journeys.title')}</h2>
                  <p className="mt-1 text-slate-600">{t('journeys.intro')}</p>
                </div>
                <Link href="/parcours" className="font-semibold text-blue-800 hover:underline">{t('journeys.see_all')}</Link>
              </div>
              <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {lifeEvents.slice(0, 6).map((event) => (
                  <li key={event.slug}>
                    <Link href={`/parcours/${event.slug}`} className="flex h-full items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 font-semibold text-slate-800 shadow-sm transition hover:border-blue-300 hover:text-blue-800">
                      <span className="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-100 text-blue-800"><LifeEventIcon name={event.icon} size={20} /></span>
                      {event.title}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          )}
        </div>
      </section>

      <section className="border-y border-slate-200/80 bg-white py-16 sm:py-20">
        <div className="mx-auto max-w-7xl px-4 sm:px-8 lg:px-10">
          <div className="mx-auto mb-10 max-w-2xl text-center">
            <p className="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">{t('home.why_kicker')}</p>
            <h2 className="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{t('home.why_title')}</h2>
            <p className="mt-4 text-lg leading-7 text-slate-600">{t('home.why_text')}</p>
          </div>
          <div className="grid gap-5 md:grid-cols-3">
            {BENEFITS.map(({ icon: Icon, title, text, style }) => (
              <article key={title} className="rounded-2xl border border-slate-200/80 bg-white p-7 shadow-sm">
                <span className={`mb-5 grid h-12 w-12 place-items-center rounded-2xl ring-1 ${style}`}><Icon size={23} aria-hidden="true" /></span>
                <h3 className="text-lg font-bold text-slate-900">{t(title)}</h3>
                <p className="mt-2 leading-7 text-slate-600">{t(text)}</p>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="py-16 sm:py-20">
        <div className="mx-auto max-w-7xl px-4 sm:px-8 lg:px-10">
          <div className="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
              <p className="text-sm font-bold uppercase tracking-[0.18em] text-blue-700">{t('home.domains_kicker')}</p>
              <h2 className="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{t('home.domains_title')}</h2>
            </div>
            <Link href="/demarches" className="inline-flex items-center gap-2 font-semibold text-blue-800 transition hover:text-blue-950">
              {t('home.see_all')} <ArrowRight size={17} aria-hidden="true" className="rtl:-scale-x-100" />
            </Link>
          </div>

          {categories === null ? (
            <div role="status" aria-label={t('common.loading')} className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {[0, 1, 2].map((i) => <div key={i} className="h-24 animate-pulse rounded-2xl bg-slate-100" />)}
            </div>
          ) : (
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {categories.map((category) => (
                <Link
                  key={category.id}
                  href={`/categories/${category.slug}`}
                  className="group flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-200 hover:shadow-lg"
                >
                  <span>
                    <span className="block text-lg font-bold text-slate-900">{category.name}</span>
                    <span className="mt-1 block text-sm text-slate-500">{t('home.procedures_count', { n: category.demarches_count ?? 0 })}</span>
                  </span>
                  <ArrowUpRight size={20} aria-hidden="true" className="text-slate-300 transition group-hover:text-blue-700 rtl:-scale-x-100" />
                </Link>
              ))}
            </div>
          )}
        </div>
      </section>

      <section className="px-4 pb-6 sm:px-8 lg:px-10">
        <div className="mx-auto grid max-w-7xl gap-5 lg:grid-cols-2">
          <div className="rounded-[2rem] bg-blue-950 p-8 text-white sm:p-10">
            <BellRing size={28} aria-hidden="true" className="text-amber-300" />
            <h2 className="mt-4 text-2xl font-bold sm:text-3xl">{t('home.space_title')}</h2>
            <p className="mt-3 leading-7 text-blue-100/85">{t('home.space_text')}</p>
            <Link href="/auth/register" className="mt-6 inline-flex items-center gap-2 rounded-xl bg-amber-400 px-5 py-3 font-bold text-blue-950 transition hover:bg-amber-300">
              {t('home.space_cta')} <Sparkles size={17} aria-hidden="true" />
            </Link>
          </div>
          <div className="rounded-[2rem] border border-amber-200 bg-amber-50 p-8 sm:p-10">
            <AlertTriangle size={28} aria-hidden="true" className="text-amber-600" />
            <h2 className="mt-4 text-2xl font-bold text-slate-950 sm:text-3xl">{t('home.cta_title')}</h2>
            <p className="mt-3 leading-7 text-slate-700">{t('home.cta_text')}</p>
            <Link href="/signalement" className="mt-6 inline-flex items-center gap-2 rounded-xl bg-amber-600 px-5 py-3 font-bold text-white transition hover:bg-amber-700">
              {t('home.cta_button')}
            </Link>
          </div>
        </div>
      </section>
    </main>
  );
}
