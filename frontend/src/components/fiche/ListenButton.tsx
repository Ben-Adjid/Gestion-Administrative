'use client';

import { useEffect, useRef, useState } from 'react';
import { Loader2, Pause, Play, Square, Volume2 } from 'lucide-react';
import { useLanguage } from '@/hooks/useLanguage';
import { LANGUAGE_LABELS, type Language } from '@/lib/i18n';

const FALLBACK_LANG = { fr: 'fr-FR', en: 'en-GB', ar: 'ar-SA' } as const;

/** Découpe le texte en phrases courtes : certains téléphones coupent les lectures trop longues. */
function chunks(text: string): string[] {
  return text
    .split(/(?<=[.!?؟:])\s+|\n+/)
    .flatMap((part) => (part.length > 220 ? part.match(/.{1,220}(\s|$)/g) ?? [part] : [part]))
    .map((part) => part.trim())
    .filter(Boolean);
}

/** Les voix arrivent de façon asynchrone sur la plupart des navigateurs : on les attend un court instant. */
function loadVoices(synth: SpeechSynthesis): Promise<SpeechSynthesisVoice[]> {
  const voices = synth.getVoices();
  if (voices.length) return Promise.resolve(voices);
  return new Promise((resolve) => {
    const done = () => { synth.removeEventListener('voiceschanged', done); resolve(synth.getVoices()); };
    synth.addEventListener('voiceschanged', done);
    window.setTimeout(done, 1500);
  });
}

/**
 * Lecture à voix haute de la fiche avec la synthèse vocale de l'appareil (aucun service externe).
 * `language` = langue du texte réellement affiché : la voix est choisie dans cette langue, jamais une autre.
 */
export function ListenButton({ text, language }: { text: string; language: Language }) {
  const { t } = useLanguage();
  const [supported, setSupported] = useState(true);
  const [state, setState] = useState<'idle' | 'loading' | 'playing' | 'paused'>('idle');
  const [noVoice, setNoVoice] = useState(false);
  const cancelled = useRef(false);

  useEffect(() => {
    setSupported(typeof window !== 'undefined' && 'speechSynthesis' in window);
    return () => {
      cancelled.current = true;
      if ('speechSynthesis' in window) window.speechSynthesis.cancel();
    };
  }, []);

  // Changer de langue ou de contenu arrête la lecture en cours : on ne lit jamais l'ancien texte.
  useEffect(() => {
    if ('speechSynthesis' in window) window.speechSynthesis.cancel();
    cancelled.current = true;
    setState('idle');
    setNoVoice(false);
  }, [language, text]);

  const play = async () => {
    const synth = window.speechSynthesis;
    synth.cancel();
    cancelled.current = false;
    setNoVoice(false);
    setState('loading');

    const voices = await loadVoices(synth);
    if (cancelled.current) return;
    const voice = voices.find((v) => v.lang.toLowerCase().startsWith(language));
    if (!voice) {
      // Pas de voix dans cette langue : lire avec une voix d'une autre langue rendrait le texte incompréhensible.
      setNoVoice(true);
      setState('idle');
      return;
    }

    const parts = chunks(text);
    parts.forEach((part, index) => {
      const utterance = new SpeechSynthesisUtterance(part);
      utterance.voice = voice;
      utterance.lang = voice.lang || FALLBACK_LANG[language];
      utterance.rate = 0.95;
      if (index === parts.length - 1) utterance.onend = () => !cancelled.current && setState('idle');
      synth.speak(utterance);
    });
    setState('playing');
  };

  const pause = () => { window.speechSynthesis.pause(); setState('paused'); };
  const resume = () => { window.speechSynthesis.resume(); setState('playing'); };
  const stop = () => { cancelled.current = true; window.speechSynthesis.cancel(); setState('idle'); };

  if (!supported) {
    return <p className="text-xs text-slate-500">{t('listen.unsupported')}</p>;
  }

  const button = 'inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-900 disabled:opacity-60';

  return (
    <div className="flex flex-col gap-1 print:hidden">
      <div className="flex flex-wrap gap-2">
        {state === 'idle' && <button type="button" onClick={play} className={button}><Volume2 size={18} aria-hidden="true" /> {t('listen.play')}</button>}
        {state === 'loading' && <button type="button" disabled className={button}><Loader2 size={18} aria-hidden="true" className="animate-spin" /> {t('printx.listen_loading')}</button>}
        {state === 'playing' && <button type="button" onClick={pause} className={button}><Pause size={18} aria-hidden="true" /> {t('listen.pause')}</button>}
        {state === 'paused' && <button type="button" onClick={resume} className={button}><Play size={18} aria-hidden="true" /> {t('listen.resume')}</button>}
        {(state === 'playing' || state === 'paused') && <button type="button" onClick={stop} className={button}><Square size={16} aria-hidden="true" /> {t('listen.stop')}</button>}
      </div>
      {noVoice && <p role="status" className="max-w-xs text-xs text-amber-800">{t('printx.listen_no_voice', { lang: LANGUAGE_LABELS[language] })}</p>}
    </div>
  );
}
