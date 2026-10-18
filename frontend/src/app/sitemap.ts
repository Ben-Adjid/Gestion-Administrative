import type { MetadataRoute } from 'next';
import { API_V1_URL } from '@/lib/api';
import { serverApiHeaders } from '@/lib/server-api';

const SITE_URL = (process.env.NEXT_PUBLIC_APP_URL || 'http://localhost:3000').replace(/\/+$/, '');

// Régénéré toutes les heures, comme les fiches (module 5 du cahier des charges).
export const revalidate = 3600;

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const now = new Date();
  const pages: MetadataRoute.Sitemap = [
    { url: SITE_URL, lastModified: now, changeFrequency: 'daily', priority: 1 },
    { url: `${SITE_URL}/demarches`, lastModified: now, changeFrequency: 'daily', priority: 0.9 },
    { url: `${SITE_URL}/faq`, lastModified: now, changeFrequency: 'monthly', priority: 0.5 },
    { url: `${SITE_URL}/about`, lastModified: now, changeFrequency: 'yearly', priority: 0.3 },
    { url: `${SITE_URL}/confidentialite`, lastModified: now, changeFrequency: 'yearly', priority: 0.2 },
    { url: `${SITE_URL}/contact`, lastModified: now, changeFrequency: 'yearly', priority: 0.3 },
    { url: `${SITE_URL}/signalement`, lastModified: now, changeFrequency: 'yearly', priority: 0.3 },
  ];

  for (const path of ['/parcours', '/villes', '/calendrier', '/questions']) {
    pages.push({ url: `${SITE_URL}${path}`, lastModified: now, changeFrequency: 'weekly', priority: 0.7 });
  }

  // Pages détaillées des parcours et des villes
  for (const [endpoint, prefix] of [['life-events', 'parcours'], ['cities', 'villes']] as const) {
    try {
      const response = await fetch(`${API_V1_URL}/${endpoint}`, { headers: serverApiHeaders(), next: { revalidate } });
      if (response.ok) {
        const result = (await response.json()) as { data: Array<{ slug: string }> };
        for (const { slug } of result.data) {
          pages.push({ url: `${SITE_URL}/${prefix}/${slug}`, lastModified: now, changeFrequency: 'weekly', priority: 0.6 });
        }
      }
    } catch {
      // API injoignable : ces pages seront ajoutées à la prochaine régénération.
    }
  }

  try {
    const categories = await fetch(`${API_V1_URL}/categories`, { headers: serverApiHeaders(), next: { revalidate } });
    if (categories.ok) {
      const result = (await categories.json()) as { data: Array<{ slug: string }> };
      for (const { slug } of result.data) {
        pages.push({ url: `${SITE_URL}/categories/${slug}`, lastModified: now, changeFrequency: 'weekly', priority: 0.7 });
      }
    }

    for (let page = 1, last = 1; page <= last && page <= 20; page++) {
      const response = await fetch(`${API_V1_URL}/demarches?per_page=50&page=${page}`, {
        headers: serverApiHeaders(),
        next: { revalidate },
      });
      if (!response.ok) break;
      const result = (await response.json()) as { data: Array<{ slug: string; updated_at?: string }>; meta?: { last_page?: number } };
      last = result.meta?.last_page ?? 1;
      for (const demarche of result.data) {
        pages.push({
          url: `${SITE_URL}/demarches/${demarche.slug}`,
          lastModified: demarche.updated_at ? new Date(demarche.updated_at) : now,
          changeFrequency: 'weekly',
          priority: 0.8,
        });
      }
    }
  } catch {
    // API injoignable : le sitemap contient au moins les pages principales.
  }

  return pages;
}
