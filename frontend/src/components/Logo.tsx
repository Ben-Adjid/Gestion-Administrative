'use client';

import { useLanguage } from '@/hooks/useLanguage';

interface LogoProps {
  className?: string;
  size?: 'sm' | 'md' | 'lg';
  inverted?: boolean;
}

const EMBLEM_SIZES = {
  sm: 'w-8 h-8',
  md: 'w-11 h-11',
  lg: 'w-14 h-14',
};

export function Logo({ className = '', size = 'md', inverted = false }: LogoProps) {
  const { t } = useLanguage();

  return (
    <div className={`flex items-center gap-3.5 ${className}`}>
      <img src="/icon.svg" alt="" aria-hidden="true" width={56} height={56} className={`${EMBLEM_SIZES[size]} shrink-0 rounded-2xl shadow-md`} />

      <div className="flex flex-col">
        <span className="text-2xl font-extrabold tracking-tight" dir="ltr">
          <span className={inverted ? 'text-white' : 'text-[#002664]'}>Demarches</span>
          <span className="text-[#C60C30]">Tchad</span>
        </span>
        <span className={`text-xs font-semibold ${inverted ? 'text-slate-400' : 'text-slate-500'}`}>
          {t('brand.tagline')}
        </span>
      </div>
    </div>
  );
}
