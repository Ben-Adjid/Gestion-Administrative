import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import DemarcheView from '@/components/DemarcheView';
import ViewCounter from '@/components/ViewCounter';
import { API_V1_URL } from '@/lib/api';
import { serverApiHeaders } from '@/lib/server-api';
import { Demarche } from '@/types';

// Module 5 du cahier des charges : fiches pré-rendues (SSG) et revalidées toutes les heures (ISR).
export const revalidate = 3600;

const SITE_URL = (process.env.NEXT_PUBLIC_APP_URL || 'http://localhost:3000').replace(/\/+$/, '');

type Params = { params: { slug: string } };

async function getDemarche(slug: string): Promise<Demarche | null> {
  const response = await fetch(`${API_V1_URL}/demarches/${encodeURIComponent(slug)}`, {
    next: { revalidate },
    headers: serverApiHeaders(),
  });
  if (response.status === 404) return null;
  if (!response.ok) throw new Error(`API indisponible (${response.status})`);
  return ((await response.json()) as { data: Demarche }).data;
}

export async function generateStaticParams(): Promise<Array<{ slug: string }>> {
  try {
    const response = await fetch(`${API_V1_URL}/demarches?per_page=50`, { next: { revalidate }, headers: serverApiHeaders() });
    if (!response.ok) return [];
    const result = (await response.json()) as { data: Array<{ slug: string }> };
    return result.data.map(({ slug }) => ({ slug }));
  } catch {
    // API injoignable pendant le build : les fiches seront générées à la première visite.
    return [];
  }
}

export async function generateMetadata({ params }: Params): Promise<Metadata> {
  const demarche = await getDemarche(params.slug).catch(() => null);
  if (!demarche) return { title: 'Démarche introuvable' };

  const description = (demarche.summary || demarche.description || '').slice(0, 160);
  const url = `${SITE_URL}/demarches/${demarche.slug}`;

  return {
    // Le suffixe « | DemarchesTchad » est ajouté par le modèle de titre du layout racine.
    title: `${demarche.title} : pièces, coût et délai`,
    description,
    alternates: { canonical: url },
    openGraph: { title: demarche.title, description, url, type: 'article', locale: 'fr_FR' },
  };
}

/** JSON-LD HowTo. Les valeurs libres (« 5 000 – 10 000 FCFA ») ne sont pas converties en faux nombres. */
function howToJsonLd(demarche: Demarche): string {
  const documents = Array.isArray(demarche.required_documents) ? demarche.required_documents : [];
  const steps = Array.isArray(demarche.procedure_steps) ? demarche.procedure_steps : [];
  const schema = {
    '@context': 'https://schema.org',
    '@type': 'HowTo',
    name: demarche.title,
    description: demarche.summary || demarche.description,
    url: `${SITE_URL}/demarches/${demarche.slug}`,
    inLanguage: 'fr',
    ...(typeof demarche.cost_amount === 'number'
      ? { estimatedCost: { '@type': 'MonetaryAmount', currency: 'XAF', value: demarche.cost_amount } }
      : {}),
    supply: documents.map((name) => ({ '@type': 'HowToSupply', name })),
    step: steps.map((text, index) => ({ '@type': 'HowToStep', position: index + 1, name: `Étape ${index + 1}`, text })),
  };
  // Empêche la fermeture prématurée de la balise <script> par un contenu saisi en administration.
  return JSON.stringify(schema).replace(/</g, '\\u003c');
}

export default async function DemarcheDetailPage({ params }: Params) {
  const demarche = await getDemarche(params.slug);
  if (!demarche) notFound();

  return (
    <main className="min-h-screen bg-slate-50 text-slate-900">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: howToJsonLd(demarche) }} />
      <ViewCounter slug={demarche.slug} />
      <DemarcheView initial={demarche} />
    </main>
  );
}
