import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Mon cas et mes parcours : les démarches dans le bon ordre',
  description: 'Votre ville, votre situation (bébé, mariage, voyage, études…) : les démarches administratives au Tchad dans l’ordre, avec le budget total et les bureaux de votre ville.',
};

export default function Layout({ children }: { children: React.ReactNode }) {
  return children;
}
