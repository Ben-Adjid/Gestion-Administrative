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
      <div className={`relative ${EMBLEM_SIZES[size]} flex items-center justify-center overflow-hidden rounded-2xl border-2 border-[#FECB00] shadow-md`} aria-hidden="true">
        <div className="absolute inset-0 flex">
          <div className="h-full w-1/3 bg-[#002664]" />
          <div className="h-full w-1/3 bg-[#FECB00]" />
          <div className="h-full w-1/3 bg-[#C60C30]" />
        </div>
        <div className="relative z-10 flex h-4/5 w-4/5 items-center justify-center rounded-xl bg-white/95">
          <span className="text-xs font-black tracking-tighter text-[#002664]">DT</span>
        </div>
      </div>
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
