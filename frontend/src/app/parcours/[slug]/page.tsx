import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import JourneyPageView from './JourneyPageView';
import type { JourneyData } from '@/components/JourneyView';
import { API_V1_URL } from '@/lib/api';
import { serverApiHeaders } from '@/lib/server-api';

// Pré-rendu (SSG) et revalidé toutes les heures (ISR), comme les fiches.
export const revalidate = 3600;

const SITE_URL = (process.env.NEXT_PUBLIC_APP_URL || 'http://localhost:3000').replace(/\/+$/, '');

type Params = { params: { slug: string } };

async function getJourney(slug: string): Promise<JourneyData | null> {
  const response = await fetch(`${API_V1_URL}/life-events/${encodeURIComponent(slug)}`, { next: { revalidate }, headers: serverApiHeaders() });
  if (response.status === 404) return null;
  if (!response.ok) throw new Error(`API indisponible (${response.status})`);
  return ((await response.json()) as { data: JourneyData }).data;
}

export async function generateStaticParams(): Promise<Array<{ slug: string }>> {
  try {
    const response = await fetch(`${API_V1_URL}/life-events`, { next: { revalidate }, headers: serverApiHeaders() });
    if (!response.ok) return [];
    return ((await response.json()) as { data: Array<{ slug: string }> }).data.map(({ slug }) => ({ slug }));
  } catch {
    return []; // API injoignable pendant le build : générées à la première visite.
  }
}

export async function generateMetadata({ params }: Params): Promise<Metadata> {
  const journey = await getJourney(params.slug).catch(() => null);
  if (!journey) return { title: 'Parcours introuvable' };
  const description = journey.description || `Les démarches à faire, dans l’ordre : ${journey.steps.map((s) => s.title).join(', ')}.`;
  const url = `${SITE_URL}/parcours/${journey.slug}`;
  return { title: journey.title, description, alternates: { canonical: url }, openGraph: { title: journey.title, description, url, type: 'article', locale: 'fr_FR' } };
}

export default async function JourneyPage({ params }: Params) {
  const journey = await getJourney(params.slug);
  if (!journey) notFound();
  return <JourneyPageView initial={journey} />;
}
