'use client';

import { useLanguage } from '@/hooks/useLanguage';

/** Squelette de liste pendant le chargement (cahier des charges §8.4 : jamais de spinner global bloquant). */
export function ListSkeleton({ rows = 6, grid = false, className = 'mt-8' }: { rows?: number; grid?: boolean; className?: string }) {
  const { t } = useLanguage();

  return (
    <div role="status" aria-label={t('common.loading')} className={`${className} ${grid ? 'grid gap-3 sm:grid-cols-2 lg:grid-cols-3' : 'space-y-3'}`}>
      {Array.from({ length: rows }, (_, index) => (
        <div key={index} className="h-20 animate-pulse rounded-2xl bg-slate-100" aria-hidden="true" />
      ))}
    </div>
  );
}
