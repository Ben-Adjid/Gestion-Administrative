/**
 * En-têtes des requêtes faites par le serveur du site (pré-génération des pages, plan du site, aperçus).
 * INTERNAL_API_KEY n'est pas préfixée NEXT_PUBLIC_ : Next ne l'injecte jamais dans le code envoyé au navigateur.
 * Avec ce secret, l'API ne limite pas ces requêtes (sinon 100/min par IP, cahier des charges module 9).
 */
export function serverApiHeaders(): Record<string, string> {
  const internalKey = process.env.INTERNAL_API_KEY;
  return {
    Accept: 'application/json',
    ...(internalKey ? { 'X-Internal-Key': internalKey } : {}),
  };
}
