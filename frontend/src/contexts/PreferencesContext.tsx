'use client';

import { createContext, ReactNode, useCallback, useContext, useEffect, useMemo, useState } from 'react';

/**
 * Préférences du visiteur, gardées sur l'appareil (sans compte) :
 * - mode économie de données (activé automatiquement si le téléphone le demande) ;
 * - ville choisie, pour afficher les bureaux proches.
 */
interface PreferencesValue {
  liteMode: boolean;
  liteModeAuto: boolean;
  setLiteMode: (enabled: boolean) => void;
  city: string | null;
  setCity: (slug: string | null) => void;
}

const LITE_KEY = 'lite_mode';
const CITY_KEY = 'city';
const PreferencesContext = createContext<PreferencesValue | null>(null);

function read(key: string): string | null {
  try {
    return localStorage.getItem(key);
  } catch {
    return null; // stockage indisponible (navigation privée)
  }
}

function write(key: string, value: string | null) {
  try {
    if (value === null) localStorage.removeItem(key);
    else localStorage.setItem(key, value);
  } catch {
    // La préférence reste valable pour la page en cours.
  }
}

export function PreferencesProvider({ children }: { children: ReactNode }) {
  const [liteMode, setLiteModeState] = useState(false);
  const [liteModeAuto, setLiteModeAuto] = useState(false);
  const [city, setCityState] = useState<string | null>(null);

  useEffect(() => {
    const stored = read(LITE_KEY);
    const connection = (navigator as Navigator & { connection?: { saveData?: boolean } }).connection;
    if (stored !== null) {
      setLiteModeState(stored === '1');
    } else if (connection?.saveData) {
      setLiteModeState(true);
      setLiteModeAuto(true);
    }
    setCityState(read(CITY_KEY));
  }, []);

  useEffect(() => {
    document.documentElement.dataset.lite = liteMode ? 'on' : 'off';
  }, [liteMode]);

  const setLiteMode = useCallback((enabled: boolean) => {
    setLiteModeState(enabled);
    setLiteModeAuto(false);
    write(LITE_KEY, enabled ? '1' : '0');
  }, []);

  const setCity = useCallback((slug: string | null) => {
    setCityState(slug);
    write(CITY_KEY, slug);
  }, []);

  const value = useMemo(() => ({ liteMode, liteModeAuto, setLiteMode, city, setCity }), [liteMode, liteModeAuto, setLiteMode, city, setCity]);

  return <PreferencesContext.Provider value={value}>{children}</PreferencesContext.Provider>;
}

export function usePreferences(): PreferencesValue {
  const context = useContext(PreferencesContext);
  if (!context) throw new Error('usePreferences doit être utilisé à l’intérieur de <PreferencesProvider>.');
  return context;
}
