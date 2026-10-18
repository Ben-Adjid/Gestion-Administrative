import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import CategoryView, { CategoryPageData } from '@/components/CategoryView';
import { API_V1_URL } from '@/lib/api';
import { serverApiHeaders } from '@/lib/server-api';

// Pages catégories pré-rendues (SSG) et revalidées toutes les heures (ISR), comme les fiches.
export const revalidate = 3600;

const SITE_URL = (process.env.NEXT_PUBLIC_APP_URL || 'http://localhost:3000').replace(/\/+$/, '');

type Params = { params: { slug: string } };

async function getCategory(slug: string): Promise<CategoryPageData | null> {
  const response = await fetch(`${API_V1_URL}/categories/${encodeURIComponent(slug)}/demarches?per_page=100`, {
    next: { revalidate },
    headers: serverApiHeaders(),
  });
  if (response.status === 404) return null;
  if (!response.ok) throw new Error(`API indisponible (${response.status})`);
  return (await response.json()) as CategoryPageData;
}

export async function generateStaticParams(): Promise<Array<{ slug: string }>> {
  try {
    const response = await fetch(`${API_V1_URL}/categories`, { next: { revalidate }, headers: serverApiHeaders() });
    if (!response.ok) return [];
    const result = (await response.json()) as { data: Array<{ slug: string }> };
    return result.data.map(({ slug }) => ({ slug }));
  } catch {
    // API injoignable pendant le build : les pages seront générées à la première visite.
    return [];
  }
}

export async function generateMetadata({ params }: Params): Promise<Metadata> {
  const page = await getCategory(params.slug).catch(() => null);
  if (!page) return { title: 'Catégorie introuvable' };

  const { categorie } = page;
  const description = categorie.description || `Démarches administratives au Tchad : ${categorie.name}.`;
  const url = `${SITE_URL}/categories/${categorie.slug}`;

  return {
    title: `${categorie.name} : démarches au Tchad`,
    description,
    alternates: { canonical: url },
    openGraph: { title: categorie.name, description, url, type: 'website', locale: 'fr_FR' },
  };
}

export default async function CategoryPage({ params }: Params) {
  const page = await getCategory(params.slug);
  if (!page) notFound();

  return (
    <main className="min-h-screen bg-slate-50 text-slate-900">
      <CategoryView initial={page} />
    </main>
  );
}
