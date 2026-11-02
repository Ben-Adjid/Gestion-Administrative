import type { Metadata } from 'next';
import { Inter } from 'next/font/google';
import './globals.css';
import { SiteChrome } from '@/components/SiteChrome';

const inter = Inter({ subsets: ['latin'] });

export const metadata: Metadata = {
  metadataBase: new URL(process.env.NEXT_PUBLIC_APP_URL || 'http://localhost:3000'),
  title: {
    default: 'DemarchesTchad — pièces, coûts et délais des démarches au Tchad',
    template: '%s | DemarchesTchad',
  },
  description: 'Guide citoyen indépendant : pièces à fournir, coût, délai et lieu de dépôt des démarches administratives au Tchad (CNI, passeport, actes d’état civil…).',
  keywords: ['démarches Tchad', 'passeport Tchad', 'carte d’identité Tchad', 'acte de naissance', 'casier judiciaire', 'permis de conduire'],
  icons: { icon: '/icon.svg', apple: '/icon.svg' },
  authors: [{ name: 'Aboubakar Assedick Mahamat Adjid Oumar' }],
  creator: 'Aboubakar Assedick Mahamat Adjid Oumar',
  openGraph: {
    title: 'DemarchesTchad',
    description: 'Pièces à fournir, coûts et délais des démarches administratives au Tchad.',
    type: 'website',
    locale: 'fr_FR',
    alternateLocale: ['en_GB', 'ar_TD'],
  },
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    // lang/dir sont ajustés côté client selon la langue choisie (voir LanguageProvider).
    <html lang="fr" dir="ltr" suppressHydrationWarning>
      <body className={inter.className}>
        <SiteChrome>{children}</SiteChrome>
      </body>
    </html>
  );
}
