/** Raccourcit un texte à `max` caractères sans couper un mot, avec « … » à la fin. */
export function clip(text: string, max = 40): string {
  const clean = text.replace(/\s+/g, ' ').trim();
  if (clean.length <= max) return clean;
  const cut = clean.slice(0, max);
  const lastSpace = cut.lastIndexOf(' ');
  return (lastSpace > max * 0.5 ? cut.slice(0, lastSpace) : cut).trimEnd() + '…';
}
