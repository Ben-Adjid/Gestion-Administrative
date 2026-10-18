import { isLanguage, translate } from '@/lib/i18n';

const API_URL = (process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000')
  .replace(/\/+$/, '')
  .replace(/\/api\/v1$/, '');

export const API_V1_URL = `${API_URL}/api/v1`;

export class ApiError extends Error {
  constructor(message: string, public status: number, public errors: Record<string, string[]> = {}) {
    super(message);
  }

  firstError(): string {
    const first = Object.values(this.errors)[0];
    return first?.[0] || this.message;
  }
}

/**
 * Répartit une erreur d'API pour un formulaire : les erreurs des champs affichés vont sous ces champs,
 * et le message général n'est montré que s'il reste une erreur sans champ visible (jamais en double).
 */
export function splitFormErrors(
  error: unknown,
  fields: readonly string[],
  fallback: string,
): { fieldErrors: Record<string, string[]>; general: string | null } {
  if (error instanceof ApiError && error.status === 422 && Object.keys(error.errors).length > 0) {
    const entries = Object.entries(error.errors);
    const other = entries.find(([key]) => !fields.includes(key));
    return {
      fieldErrors: Object.fromEntries(entries.filter(([key]) => fields.includes(key))),
      general: other ? other[1][0] : null,
    };
  }
  return { fieldErrors: {}, general: error instanceof Error ? error.message : fallback };
}

export async function fetchApi<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const isAdminEndpoint = endpoint.startsWith('/admin/');
  const token = typeof window !== 'undefined'
    ? isAdminEndpoint
      ? localStorage.getItem('admin_token')
      : localStorage.getItem('token') || localStorage.getItem('demarches_token')
    : null;
  const url = endpoint.startsWith('http://') || endpoint.startsWith('https://')
    ? endpoint
    : endpoint.startsWith('/api/v1/')
      ? `${API_URL}${endpoint}`
      : `${API_V1_URL}${endpoint.startsWith('/') ? endpoint : `/${endpoint}`}`;

  let language = 'fr';
  if (typeof window !== 'undefined') {
    try {
      language = localStorage.getItem('language') || 'fr';
    } catch {
      // Stockage indisponible : messages de l'API en français.
    }
  }

  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Accept-Language': language,
    ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    ...(options.headers || {}),
  };

  const response = await fetch(url, {
    ...options,
    credentials: 'include', // Inclure cookies httpOnly
    cache: options.cache ?? 'no-store',
    headers,
  });

  if (response.status === 401 && token) {
    if (typeof window !== 'undefined') {
      localStorage.removeItem('demarches_token');
      localStorage.removeItem('demarches_admin');
      localStorage.removeItem(isAdminEndpoint ? 'admin_token' : 'token');
      localStorage.removeItem(isAdminEndpoint ? 'admin' : 'user');
      window.location.href = isAdminEndpoint ? '/admin/login' : '/auth/login';
    }
  }

  if (!response.ok) {
    let errorData: { message?: string; errors?: Record<string, string[]> } = {};
    try {
      errorData = await response.json();
    } catch {
      // Réponse non JSON : message générique ci-dessous.
    }
    const lang = isLanguage(language) ? language : 'fr';
    // 403/429/5xx : message du site (le serveur peut répondre en anglais ou de façon technique).
    const generic = response.status === 403
      ? translate(lang, 'common.forbidden')
      : response.status === 429
        // Les limites métier (ex. signalements) renvoient un message traduit et précis.
        ? errorData.message && !/too many attempts/i.test(errorData.message) ? errorData.message : translate(lang, 'common.throttled')
        : response.status >= 500
          ? translate(lang, 'common.server_error')
          : null;
    throw new ApiError(generic || errorData.message || translate(lang, 'common.http_error', { status: response.status }), response.status, errorData.errors || {});
  }

  if (response.status === 204) {
    return undefined as T;
  }

  return response.json();
}

/** Télécharge un fichier protégé de l'administration (export CSV) avec le jeton admin. */
export async function downloadAdminFile(endpoint: string, filename: string): Promise<void> {
  const token = localStorage.getItem('admin_token') || '';
  const response = await fetch(`${API_V1_URL}${endpoint}`, { headers: { Authorization: `Bearer ${token}` } });
  if (!response.ok) throw new ApiError(`HTTP ${response.status}`, response.status);
  const url = window.URL.createObjectURL(await response.blob());
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
}

// Fonction pour obtenir le token CSRF (sera utilisé en prod)
export async function getCsrfToken(): Promise<string> {
  const response = await fetch(`${API_V1_URL}/csrf-token`, {
    credentials: 'include',
    cache: 'no-store',
  });
  if (!response.ok) {
    throw new Error(`Impossible de récupérer le token CSRF (${response.status}).`);
  }

  const data = await response.json() as { token?: string };
  if (!data.token) {
    throw new Error('Le serveur n’a pas retourné de token CSRF.');
  }
  return data.token;
}
