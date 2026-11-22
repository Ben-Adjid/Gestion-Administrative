// Jeton anti-robot Cloudflare Turnstile : un jeton à usage unique, renouvelé après chaque envoi.
// Sans clé du site (NEXT_PUBLIC_TURNSTILE_SITE_KEY), aucun jeton n'est produit et rien n'est envoyé.

let currentToken = '';
let widgetId: string | null = null;

export const TURNSTILE_SITE_KEY = process.env.NEXT_PUBLIC_TURNSTILE_SITE_KEY || '';

export function getTurnstileToken(): string {
  return currentToken;
}

export function setTurnstileToken(token: string): void {
  currentToken = token;
}

export function setTurnstileWidgetId(id: string | null): void {
  widgetId = id;
}

/** Demande un nouveau jeton (après un envoi, le précédent n'est plus valable). */
export function resetTurnstile(): void {
  currentToken = '';
  try {
    const turnstile = (window as unknown as { turnstile?: { reset: (id?: string) => void } }).turnstile;
    if (turnstile && widgetId) turnstile.reset(widgetId);
  } catch {
    // Widget indisponible : le prochain envoi échouera proprement avec un message.
  }
}
