'use client';

import Link, { type LinkProps } from 'next/link';
import type { AnchorHTMLAttributes, ReactNode } from 'react';
import { usePreferences } from '@/contexts/PreferencesContext';

type Props = LinkProps & Omit<AnchorHTMLAttributes<HTMLAnchorElement>, keyof LinkProps> & { children: ReactNode };

/** Lien interne qui ne précharge pas la page cible en mode économie de données. */
export default function AppLink({ prefetch, ...props }: Props) {
  const { liteMode } = usePreferences();
  return <Link prefetch={liteMode ? false : prefetch} {...props} />;
}
