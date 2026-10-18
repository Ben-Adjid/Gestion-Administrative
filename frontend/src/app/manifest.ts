import type { MetadataRoute } from 'next';

// Permet d'ajouter le site à l'écran d'accueil du téléphone (utile avec le mode hors connexion).
export default function manifest(): MetadataRoute.Manifest {
  return {
    name: 'DemarchesTchad — Guide des démarches au Tchad',
    short_name: 'DemarchesTchad',
    description: 'Pièces à fournir, coûts, délais et lieux des démarches administratives au Tchad.',
    start_url: '/',
    scope: '/',
    display: 'standalone',
    background_color: '#f8fafc',
    theme_color: '#1e3a8a',
    lang: 'fr',
    icons: [{ src: '/icon.svg', sizes: 'any', type: 'image/svg+xml', purpose: 'any' }],
  };
}
