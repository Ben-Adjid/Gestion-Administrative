import type { Metadata } from 'next';
import RulesContent from './RulesContent';

export const metadata: Metadata = {
  title: 'Règles communes à toutes les démarches',
  description: 'Règles transversales, règles pour les mineurs et règles pour les étrangers applicables aux démarches administratives au Tchad.',
};

export default function RulesPage() {
  return <RulesContent />;
}
