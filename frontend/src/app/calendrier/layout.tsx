import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Calendrier administratif',
  description: 'Inscriptions électorales, examens, rentrée : les dates administratives à ne pas manquer au Tchad.',
};

export default function Layout({ children }: { children: React.ReactNode }) {
  return children;
}
