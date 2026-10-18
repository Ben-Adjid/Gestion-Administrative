'use client';

import { useEffect, useState } from 'react';
import { WifiOff } from 'lucide-react';
import { useLanguage } from '@/hooks/useLanguage';

/** Active le mode hors connexion (service worker, en production) et prévient l'utilisateur quand il perd internet. */
export function OfflineSupport() {
  const { t } = useLanguage();
  const [offline, setOffline] = useState(false);

  useEffect(() => {
    if (process.env.NODE_ENV === 'production' && 'serviceWorker' in navigator) {
      navigator.serviceWorker.register('/sw.js').catch(() => {
        // Navigateur incompatible ou mode privé : le site fonctionne normalement en ligne.
      });
    }

    const update = () => setOffline(!navigator.onLine);
    update();
    window.addEventListener('online', update);
    window.addEventListener('offline', update);
    return () => {
      window.removeEventListener('online', update);
      window.removeEventListener('offline', update);
    };
  }, []);

  if (!offline) return null;

  return (
    <p role="status" className="flex items-center justify-center gap-2 bg-amber-100 px-4 py-2 text-center text-sm font-medium text-amber-900 print:hidden">
      <WifiOff size={16} aria-hidden="true" /> {t('common.offline')}
    </p>
  );
}
