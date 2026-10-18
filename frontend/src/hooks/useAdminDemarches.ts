'use client';

import { useEffect, useState } from 'react';
import { fetchApi } from '@/lib/api';

export interface DemarcheOption { id: number; title: string; slug: string; status: string }

/** Toutes les démarches (toutes pages confondues) pour les listes de choix de l'administration. */
export function useAdminDemarches(): DemarcheOption[] {
  const [options, setOptions] = useState<DemarcheOption[]>([]);

  useEffect(() => {
    let active = true;
    (async () => {
      const all: DemarcheOption[] = [];
      for (let page = 1, last = 1; page <= last && page <= 40; page++) {
        const res = await fetchApi<{ data: DemarcheOption[]; meta: { last_page: number } }>(`/admin/demarches?per_page=50&page=${page}`);
        all.push(...res.data.map(({ id, title, slug, status }) => ({ id, title, slug, status })));
        last = res.meta.last_page;
      }
      if (active) setOptions(all.sort((a, b) => a.title.localeCompare(b.title, 'fr')));
    })().catch(() => { /* liste vide : les formulaires restent utilisables sans liaison */ });
    return () => { active = false; };
  }, []);

  return options;
}
