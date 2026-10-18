const isDev = process.env.NODE_ENV === 'development';

// Origine de l'API autorisée pour les appels fetch (connect-src).
let apiOrigin = 'http://localhost:8000';
try {
  apiOrigin = new URL(process.env.NEXT_PUBLIC_API_URL || apiOrigin).origin;
} catch {
  // URL invalide : on garde la valeur locale par défaut.
}

// Next.js injecte des scripts en ligne : 'unsafe-inline' est nécessaire sans nonce ; 'unsafe-eval' seulement en développement.
const contentSecurityPolicy = [
  "default-src 'self'",
  `script-src 'self' 'unsafe-inline'${isDev ? " 'unsafe-eval'" : ''}`,
  "style-src 'self' 'unsafe-inline'",
  "img-src 'self' data: blob:",
  "font-src 'self' data:",
  `connect-src 'self' ${apiOrigin}${isDev ? ' ws: http://localhost:*' : ''}`,
  "object-src 'none'",
  "base-uri 'self'",
  "form-action 'self'",
  "frame-ancestors 'none'",
].join('; ');

const securityHeaders = [
  { key: 'Content-Security-Policy', value: contentSecurityPolicy },
  { key: 'X-Content-Type-Options', value: 'nosniff' },
  { key: 'X-Frame-Options', value: 'DENY' },
  { key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' },
  { key: 'Permissions-Policy', value: 'geolocation=(), microphone=(), camera=()' },
  ...(isDev ? [] : [{ key: 'Strict-Transport-Security', value: 'max-age=31536000; includeSubDomains' }]),
];

/** @type {import('next').NextConfig} */
const nextConfig = {
  distDir: process.env.NEXT_DIST_DIR || (isDev ? '.next-dev' : '.next'),
  poweredByHeader: false,
  images: {
    formats: ['image/webp', 'image/avif'],
    deviceSizes: [640, 750, 828, 1080, 1200, 1920, 2048, 3840],
    imageSizes: [16, 32, 48, 64, 96, 128, 256, 384],
  },
  compress: true,
  swcMinify: true,
  productionBrowserSourceMaps: false,
  optimizeFonts: true,
  experimental: {
    optimizePackageImports: ['lucide-react'],
  },
  // Adresses du cahier des charges (§8.3) qui pointent vers les pages réelles.
  async redirects() {
    return [
      { source: '/a-propos', destination: '/about', permanent: true },
      // « Mon cas » et « Parcours » forment désormais une seule rubrique.
      { source: '/mon-cas', destination: '/parcours', permanent: true },
      // Rubrique Diaspora retirée : les anciens liens mènent au catalogue plutôt qu'à une page 404.
      { source: '/diaspora', destination: '/demarches', permanent: true },
      { source: '/', has: [{ type: 'query', key: 'search', value: '(?<q>.+)' }], destination: '/demarches?q=:q', permanent: true },
    ];
  },
  async headers() {
    return [{ source: '/:path*', headers: securityHeaders }];
  },
};

module.exports = nextConfig;
