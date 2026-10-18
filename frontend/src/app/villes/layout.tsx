import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Les villes : où faire ses démarches au Tchad',
  description: 'Bureaux, adresses, horaires et itinéraires pour faire vos démarches administratives dans les villes du Tchad.',
};

export default function Layout({ children }: { children: React.ReactNode }) {
  return children;
}
