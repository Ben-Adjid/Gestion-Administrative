'use client';

import { useEffect, useState } from 'react';
import { API_V1_URL } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

export interface LifeEventSummary {
  slug: string;
  title: string;
  description: string | null;
  icon: string;
  steps_count: number;
}

/** Liste des parcours de vie actifs, dans la langue affichée. */
export function useLifeEvents(): { events: LifeEventSummary[]; loaded: boolean } {
  const { language } = useLanguage();
  const [events, setEvents] = useState<LifeEventSummary[]>([]);
  const [loaded, setLoaded] = useState(false);

  useEffect(() => {
    let active = true;
    fetch(`${API_V1_URL}/life-events?lang=${language}`, { headers: { Accept: 'application/json' } })
      .then((res) => (res.ok ? res.json() : { data: [] }))
      .then((json: { data: LifeEventSummary[] }) => { if (active) { setEvents(json.data); setLoaded(true); } })
      .catch(() => active && setLoaded(true));
    return () => { active = false; };
  }, [language]);

  return { events, loaded };
}
