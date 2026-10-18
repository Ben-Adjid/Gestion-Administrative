import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import CityView, { type CityData } from './CityView';
import { API_V1_URL } from '@/lib/api';
import { serverApiHeaders } from '@/lib/server-api';

export const revalidate = 3600;

const SITE_URL = (process.env.NEXT_PUBLIC_APP_URL || 'http://localhost:3000').replace(/\/+$/, '');

type Params = { params: { slug: string } };

async function getCity(slug: string): Promise<CityData | null> {
  const response = await fetch(`${API_V1_URL}/cities/${encodeURIComponent(slug)}`, { next: { revalidate }, headers: serverApiHeaders() });
  if (response.status === 404) return null;
  if (!response.ok) throw new Error(`API indisponible (${response.status})`);
  return ((await response.json()) as { data: CityData }).data;
}

export async function generateStaticParams(): Promise<Array<{ slug: string }>> {
  try {
    const response = await fetch(`${API_V1_URL}/cities`, { next: { revalidate }, headers: serverApiHeaders() });
    if (!response.ok) return [];
    return ((await response.json()) as { data: Array<{ slug: string }> }).data.map(({ slug }) => ({ slug }));
  } catch {
    return [];
  }
}

export async function generateMetadata({ params }: Params): Promise<Metadata> {
  const city = await getCity(params.slug).catch(() => null);
  if (!city) return { title: 'Ville introuvable' };
  const url = `${SITE_URL}/villes/${city.slug}`;
  const description = `Bureaux, adresses et itinéraires pour faire vos démarches administratives à ${city.name}.`;
  return { title: `Démarches à ${city.name}`, description, alternates: { canonical: url }, openGraph: { title: `Démarches à ${city.name}`, description, url, locale: 'fr_FR' } };
}

export default async function CityPage({ params }: Params) {
  const city = await getCity(params.slug);
  if (!city) notFound();
  return <CityView initial={city} />;
}
