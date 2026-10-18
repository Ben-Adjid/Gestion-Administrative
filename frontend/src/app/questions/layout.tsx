import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Questions-réponses',
  description: 'Posez vos questions sur les démarches administratives au Tchad : l’équipe et les citoyens vous répondent.',
};

export default function Layout({ children }: { children: React.ReactNode }) {
  return children;
}
