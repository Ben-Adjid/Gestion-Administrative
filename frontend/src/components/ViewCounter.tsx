'use client';

import { useEffect } from 'react';
import { API_V1_URL } from '@/lib/api';

/** Compte une consultation réelle de la fiche (la page elle-même est pré-rendue côté serveur). */
export default function ViewCounter({ slug }: { slug: string }) {
  useEffect(() => {
    const key = `viewed:${slug}`;
    try {
      if (sessionStorage.getItem(key)) return;
      sessionStorage.setItem(key, '1');
    } catch {
      // Stockage indisponible (navigation privée) : on compte quand même une fois par chargement.
    }
    void fetch(`${API_V1_URL}/demarches/${encodeURIComponent(slug)}/view`, { method: 'POST', keepalive: true }).catch(() => undefined);
  }, [slug]);

  return null;
}
