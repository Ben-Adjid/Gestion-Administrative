'use client';

import { useEffect, useRef } from 'react';
import { TURNSTILE_SITE_KEY, setTurnstileToken, setTurnstileWidgetId } from '@/lib/turnstile';

const SCRIPT_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';

type TurnstileApi = {
  render: (el: HTMLElement, options: Record<string, unknown>) => string;
};

declare global {
  interface Window {
    turnstile?: TurnstileApi;
  }
}

/**
 * Anti-robot discret : Cloudflare n'affiche rien pour un visiteur normal
 * et ne demande une case à cocher que si le trafic paraît suspect.
 * Ne fait rien tant que la clé du site n'est pas configurée.
 */
export function TurnstileWidget() {
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!TURNSTILE_SITE_KEY || !ref.current) return;
    let cancelled = false;

    const render = () => {
      if (cancelled || !ref.current || !window.turnstile) return;
      const id = window.turnstile.render(ref.current, {
        sitekey: TURNSTILE_SITE_KEY,
        appearance: 'interaction-only',
        callback: (token: string) => setTurnstileToken(token),
        'expired-callback': () => setTurnstileToken(''),
        'error-callback': () => setTurnstileToken(''),
      });
      setTurnstileWidgetId(id);
    };

    if (window.turnstile) {
      render();
    } else {
      let script = document.querySelector<HTMLScriptElement>(`script[src="${SCRIPT_URL}"]`);
      if (!script) {
        script = document.createElement('script');
        script.src = SCRIPT_URL;
        script.async = true;
        document.head.appendChild(script);
      }
      script.addEventListener('load', render);
    }

    return () => {
      cancelled = true;
      setTurnstileWidgetId(null);
      setTurnstileToken('');
    };
  }, []);

  if (!TURNSTILE_SITE_KEY) return null;
  return <div ref={ref} className="fixed bottom-3 left-3 z-40" aria-hidden="true" />;
}
