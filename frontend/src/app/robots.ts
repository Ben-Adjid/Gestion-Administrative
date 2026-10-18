import type { MetadataRoute } from 'next';

const SITE_URL = (process.env.NEXT_PUBLIC_APP_URL || 'http://localhost:3000').replace(/\/+$/, '');

export default function robots(): MetadataRoute.Robots {
  return {
    rules: {
      userAgent: '*',
      allow: '/',
      // Espaces privés : aucune valeur pour les moteurs de recherche.
      disallow: ['/admin', '/dashboard', '/auth'],
    },
    sitemap: `${SITE_URL}/sitemap.xml`,
  };
}
