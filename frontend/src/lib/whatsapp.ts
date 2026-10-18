/** Numéro WhatsApp de l'assistant (chiffres seulement, indicatif compris), défini au déploiement. */
const NUMBER = (process.env.NEXT_PUBLIC_WHATSAPP_NUMBER || '').replace(/\D/g, '');

/** Lien wa.me vers l'assistant, avec un message pré-rempli ; null tant qu'aucun numéro n'est configuré. */
export function whatsappLink(message?: string): string | null {
  if (!NUMBER) return null;
  return `https://wa.me/${NUMBER}${message ? `?text=${encodeURIComponent(message)}` : ''}`;
}
