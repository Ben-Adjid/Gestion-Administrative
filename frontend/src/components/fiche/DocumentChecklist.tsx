'use client';

import { useEffect, useState } from 'react';
import { CheckCircle2, RotateCcw } from 'lucide-react';
import { useLanguage } from '@/hooks/useLanguage';

/**
 * Liste des pièces à cocher. L'état est gardé sur l'appareil (par fiche), sans compte.
 * Les cases sont repérées par leur position, identique dans les trois langues.
 */
export function DocumentChecklist({ slug, documents }: { slug: string; documents: string[] }) {
  const { t } = useLanguage();
  const storageKey = `checklist:${slug}`;
  const [checked, setChecked] = useState<number[]>([]);

  useEffect(() => {
    try {
      const saved = JSON.parse(localStorage.getItem(storageKey) || '[]');
      setChecked(Array.isArray(saved) ? saved.filter((i): i is number => Number.isInteger(i) && i >= 0 && i < documents.length) : []);
    } catch {
      setChecked([]);
    }
  }, [storageKey, documents.length]);

  const save = (next: number[]) => {
    setChecked(next);
    try {
      localStorage.setItem(storageKey, JSON.stringify(next));
    } catch {
      // Stockage indisponible : la liste reste valable pendant la visite.
    }
  };

  const toggle = (index: number) => save(checked.includes(index) ? checked.filter((i) => i !== index) : [...checked, index]);
  const done = checked.length;
  const total = documents.length;

  return (
    <div>
      <p className="mt-1 text-sm text-slate-600 print:hidden">{t('checklist.hint')}</p>
      <div className="mt-3 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <p role="status" className={`text-sm font-semibold ${done === total ? 'text-emerald-700' : 'text-slate-700'}`}>
          {done === total ? t('checklist.all_ready') : t('checklist.progress', { done, total })}
        </p>
        {done > 0 && (
          <button type="button" onClick={() => save([])} className="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-slate-900">
            <RotateCcw size={15} aria-hidden="true" /> {t('checklist.reset')}
          </button>
        )}
      </div>
      <div className="mt-2 h-2 overflow-hidden rounded-full bg-slate-100 print:hidden" aria-hidden="true">
        <div className="h-full rounded-full bg-emerald-500 transition-all" style={{ width: `${total ? (done / total) * 100 : 0}%` }} />
      </div>
      <ul className="mt-4 space-y-3 print:mt-2 print:space-y-1">
        {documents.map((document, index) => {
          const isChecked = checked.includes(index);
          return (
            <li key={`${index}-${document}`}>
              <label className={`flex cursor-pointer break-inside-avoid items-start gap-3 rounded-xl p-4 transition print:bg-transparent print:p-0 print:text-sm print:text-slate-900 print:no-underline ${isChecked ? 'bg-emerald-50 text-emerald-900' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'}`}>
                <input type="checkbox" checked={isChecked} onChange={() => toggle(index)} className="mt-0.5 h-5 w-5 shrink-0 accent-emerald-600" />
                <span className={isChecked ? 'line-through decoration-emerald-600/60' : ''}>{document}</span>
                {isChecked && <CheckCircle2 size={19} aria-hidden="true" className="ms-auto shrink-0 text-emerald-600" />}
              </label>
            </li>
          );
        })}
      </ul>
    </div>
  );
}
