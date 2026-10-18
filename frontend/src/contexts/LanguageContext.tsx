'use client';

import { createContext, ReactNode, useCallback, useContext, useEffect, useMemo, useState } from 'react';
import { usePathname } from 'next/navigation';
import { isLanguage, Language, translate } from '@/lib/i18n';

interface LanguageContextValue {
  language: Language;
  changeLanguage: (language: Language) => void;
  t: (key: string, vars?: Record<string, string | number>) => string;
  dir: 'ltr' | 'rtl';
  mounted: boolean;
}

const STORAGE_KEY = 'language';
const LanguageContext = createContext<LanguageContextValue | null>(null);

// Titre par défaut défini dans app/layout.tsx (métadonnées serveur, en français).
const DEFAULT_TITLE_PREFIX = 'DemarchesTchad — ';

function applyToDocument(language: Language) {
  document.documentElement.lang = language;
  document.documentElement.dir = language === 'ar' ? 'rtl' : 'ltr';
}

/** Traduit le titre d'onglet par défaut ; les titres propres à une page sont laissés tels quels. */
function translateDefaultTitle(language: Language) {
  if (document.title.startsWith(DEFAULT_TITLE_PREFIX)) {
    document.title = `${DEFAULT_TITLE_PREFIX}${translate(language, 'brand.tagline')}`;
  }
}

export function LanguageProvider({ children }: { children: ReactNode }) {
  const [language, setLanguage] = useState<Language>('fr');
  const [mounted, setMounted] = useState(false);
  const pathname = usePathname();

  useEffect(() => {
    let stored: string | null = null;
    try {
      stored = localStorage.getItem(STORAGE_KEY);
    } catch {
      // Stockage indisponible (navigation privée) : français par défaut.
    }
    const initial = isLanguage(stored) ? stored : 'fr';
    setLanguage(initial);
    applyToDocument(initial);
    setMounted(true);

    const onStorage = (event: StorageEvent) => {
      if (event.key === STORAGE_KEY && isLanguage(event.newValue)) {
        setLanguage(event.newValue);
        applyToDocument(event.newValue);
      }
    };
    window.addEventListener('storage', onStorage);
    return () => window.removeEventListener('storage', onStorage);
  }, []);

  // Next.js remet le titre serveur à chaque navigation : on le retraduit.
  useEffect(() => {
    if (mounted) translateDefaultTitle(language);
  }, [language, mounted, pathname]);

  const changeLanguage = useCallback((next: Language) => {
    setLanguage(next);
    applyToDocument(next);
    try {
      localStorage.setItem(STORAGE_KEY, next);
    } catch {
      // Le choix reste valable pour la session en cours.
    }
  }, []);

  const value = useMemo<LanguageContextValue>(() => ({
    language,
    changeLanguage,
    t: (key, vars) => translate(language, key, vars),
    dir: language === 'ar' ? 'rtl' : 'ltr',
    mounted,
  }), [language, changeLanguage, mounted]);

  return <LanguageContext.Provider value={value}>{children}</LanguageContext.Provider>;
}

export function useLanguage(): LanguageContextValue {
  const context = useContext(LanguageContext);
  if (!context) {
    throw new Error('useLanguage doit être utilisé à l’intérieur de <LanguageProvider>.');
  }
  return context;
}
