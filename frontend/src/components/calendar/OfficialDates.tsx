'use client';

import { useEffect, useState } from 'react';
import { CalendarClock, CalendarPlus, CalendarRange, ExternalLink, Flag } from 'lucide-react';
import AppLink from '@/components/AppLink';
import { API_V1_URL } from '@/lib/api';
import { formatDate, formatDays } from '@/lib/i18n';
import { useLanguage } from '@/hooks/useLanguage';

const CATEGORIES = ['elections', 'education', 'exams', 'identity', 'tax', 'other'] as const;
const KINDS = ['period', 'deadline', 'event'] as const;

interface CalendarItem {
  id: number;
  title: string;
  description: string | null;
  category: (typeof CATEGORIES)[number];
  kind: (typeof KINDS)[number];
  status: 'ongoing' | 'upcoming';
  days_left: number;
  starts_on: string;
  ends_on: string | null;
  source_url: string | null;
  demarche: { slug: string; title: string } | null;
}

const CATEGORY_COLORS: Record<CalendarItem['category'], string> = {
  elections: 'bg-rose-100 text-rose-800', education: 'bg-blue-100 text-blue-800', exams: 'bg-amber-100 text-amber-900',
  identity: 'bg-emerald-100 text-emerald-800', tax: 'bg-violet-100 text-violet-800', other: 'bg-slate-100 text-slate-700',
};

// Chaque type d'échéance a sa propre présentation : on ne confond pas une période, une date limite et une date unique.
const KIND_STYLE = {
  period: { icon: CalendarRange, badge: 'bg-sky-100 text-sky-900', border: 'border-s-sky-500' },
  deadline: { icon: Flag, badge: 'bg-red-100 text-red-800', border: 'border-s-red-500' },
  event: { icon: CalendarClock, badge: 'bg-slate-200 text-slate-800', border: 'border-s-slate-400' },
} as const;

/** Dates officielles : périodes, dates limites et dates uniques, filtrables et exportables vers l'agenda. */
export function OfficialDates() {
  const { language, t } = useLanguage();
  const [category, setCategory] = useState('');
  const [kind, setKind] = useState('');
  const [events, setEvents] = useState<CalendarItem[]>([]);
  const [loaded, setLoaded] = useState(false);

  useEffect(() => {
    let active = true;
    const params = new URLSearchParams({ lang: language });
    if (category) params.set('category', category);
    if (kind) params.set('kind', kind);
    fetch(`${API_V1_URL}/calendar?${params}`, { headers: { Accept: 'application/json' } })
      .then((res) => (res.ok ? res.json() : { data: [] }))
      .then((json: { data: CalendarItem[] }) => { if (active) { setEvents(json.data); setLoaded(true); } })
      .catch(() => active && setLoaded(true));
    return () => { active = false; };
  }, [language, category, kind]);

  const when = (event: CalendarItem): string => {
    if (event.kind === 'period' && event.ends_on) {
      return t('calendar.from_to', { start: formatDate(language, event.starts_on), end: formatDate(language, event.ends_on) });
    }
    return event.kind === 'deadline'
      ? t('calendar.deadline_on', { date: formatDate(language, event.starts_on) })
      : t('calendar.on', { date: formatDate(language, event.starts_on) });
  };

  const countdown = (event: CalendarItem): string => {
    if (event.kind === 'period') {
      if (event.status === 'ongoing') return event.days_left === 0 ? t('calendar.last_day') : t('calendar.ends_in', { days: formatDays(language, event.days_left) });
      return t('calendar.starts_in', { days: formatDays(language, event.days_left) });
    }
    if (event.days_left === 0) return t('calendar.today');
    return event.kind === 'deadline' ? t('calendar.days_left', { days: formatDays(language, event.days_left) }) : t('calendar.in_days', { days: formatDays(language, event.days_left) });
  };

  const chip = (value: string, current: string, set: (v: string) => void, label: string) => (
    <button
      key={value || 'all'}
      type="button"
      aria-pressed={current === value}
      onClick={() => set(value)}
      className={`rounded-full px-3.5 py-1.5 text-sm font-semibold transition ${current === value ? 'bg-blue-800 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50'}`}
    >
      {label}
    </button>
  );

  return (
    <div>
      <div className="mt-6 space-y-3">
        <div role="group" aria-label={t('calendar.filter_kind')} className="flex flex-wrap items-center gap-2">
          <span className="me-1 text-sm font-semibold text-slate-600">{t('calendar.filter_kind')}</span>
          {chip('', kind, setKind, t('calendar.all_kinds'))}
          {KINDS.map((k) => chip(k, kind, setKind, t(`calendar.kind_${k}`)))}
        </div>
        <div role="group" aria-label={t('calendar.filter_domain')} className="flex flex-wrap items-center gap-2">
          <span className="me-1 text-sm font-semibold text-slate-600">{t('calendar.filter_domain')}</span>
          {chip('', category, setCategory, t('calendar.all'))}
          {CATEGORIES.map((c) => chip(c, category, setCategory, t(`calendar.cat_${c}`)))}
        </div>
      </div>

      {loaded && events.length === 0 && <p className="mt-8 rounded-2xl border border-dashed border-slate-300 p-6 text-slate-600">{t('calendar.empty')}</p>}
      {!loaded && <div role="status" aria-label={t('common.loading')} className="mt-6 h-32 animate-pulse rounded-2xl bg-slate-100" />}

      <ul className="mt-6 space-y-4">
        {events.map((event) => {
          const style = KIND_STYLE[event.kind];
          const Icon = style.icon;
          const urgent = event.kind === 'deadline' && event.days_left <= 7;
          return (
            <li key={event.id} className={`rounded-2xl border border-s-4 border-slate-200 bg-white p-5 shadow-sm ${style.border}`}>
              <div className="flex flex-wrap items-center gap-2">
                <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-bold ${style.badge}`}>
                  <Icon size={13} aria-hidden="true" /> {t(`calendar.kind_${event.kind}`)}
                </span>
                <span className={`rounded-full px-2.5 py-0.5 text-xs font-bold ${CATEGORY_COLORS[event.category]}`}>{t(`calendar.cat_${event.category}`)}</span>
                {event.status === 'ongoing' && <span className="rounded-full bg-emerald-600 px-2.5 py-0.5 text-xs font-bold text-white">{t('calendar.ongoing')}</span>}
              </div>
              <h2 className="mt-2 text-lg font-bold text-slate-950">{event.title}</h2>
              <p className="mt-1 flex flex-wrap gap-x-3 text-sm">
                <span className="font-semibold text-slate-700">{when(event)}</span>
                <span className={urgent ? 'font-bold text-red-700' : 'text-slate-500'}>{countdown(event)}</span>
              </p>
              {event.description && <p className="mt-2 whitespace-pre-line text-slate-700">{event.description}</p>}
              <div className="mt-3 flex flex-wrap gap-3 text-sm">
                <a href={`${API_V1_URL}/calendar/${event.id}.ics?lang=${language}`} className="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-1.5 font-semibold text-slate-700 hover:bg-slate-50">
                  <CalendarPlus size={15} aria-hidden="true" /> {t('calendar.add')}
                </a>
                {event.demarche && (
                  <AppLink href={`/demarches/${event.demarche.slug}`} className="inline-flex items-center gap-1.5 px-1 py-1.5 font-semibold text-blue-800 hover:underline">
                    {t('navx.label_value', { label: t('calendar.related'), value: event.demarche.title })}
                  </AppLink>
                )}
                {event.source_url && (
                  <a href={event.source_url} target="_blank" rel="noreferrer noopener" className="inline-flex items-center gap-1.5 px-1 py-1.5 font-semibold text-blue-800 hover:underline">
                    <ExternalLink size={14} aria-hidden="true" /> {t('calendar.source')}
                  </a>
                )}
              </div>
            </li>
          );
        })}
      </ul>
    </div>
  );
}
