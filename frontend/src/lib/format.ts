import type { Language } from '@/lib/i18n';

/** Montant en francs CFA, écrit comme dans les fiches (« 75 000 FCFA », « 75,000 FCFA », « 75,000 فرنك أفريقي »). */
export function formatFcfa(language: Language, amount: number): string {
  if (language === 'fr') {
    return `${new Intl.NumberFormat('fr-FR').format(amount).replace(/ /g, ' ')} FCFA`;
  }
  const digits = new Intl.NumberFormat('en-US').format(amount);
  return language === 'ar' ? `${digits} فرنك أفريقي` : `${digits} FCFA`;
}
