'use client';

import { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react';
import { useLanguage } from '@/hooks/useLanguage';

export type ConfirmTone = 'default' | 'danger';

export interface ConfirmOptions {
  /** Titre court, par ex. « Supprimer cette démarche ? ». */
  title: string;
  /** Explication facultative (conséquences, réversibilité). */
  message?: string;
  /** Libellé du bouton de validation (par défaut : « Confirmer »). */
  confirmLabel?: string;
  /** Libellé du bouton d’annulation (par défaut : « Annuler »). */
  cancelLabel?: string;
  /** « danger » pour les actions destructives (suppression) : bouton rouge. */
  tone?: ConfirmTone;
}

type ConfirmFn = (options: ConfirmOptions) => Promise<boolean>;

const ConfirmContext = createContext<ConfirmFn | null>(null);

/**
 * Remplace `window.confirm` par une fenêtre cohérente avec le site.
 * Usage : `if (!(await confirm({ title: '…', tone: 'danger' }))) return;`
 */
/** Repli sans fournisseur (ex. rechargement à chaud du serveur de développement) : confirmation native. */
const nativeConfirm: ConfirmFn = async ({ title, message }) =>
  typeof window !== 'undefined' && window.confirm(message ? `${title}\n\n${message}` : title);

export function useConfirm(): ConfirmFn {
  return useContext(ConfirmContext) ?? nativeConfirm;
}

interface Pending extends ConfirmOptions {
  resolve: (value: boolean) => void;
}

export function ConfirmProvider({ children }: { children: React.ReactNode }) {
  const { t } = useLanguage();
  const [pending, setPending] = useState<Pending | null>(null);
  const cancelRef = useRef<HTMLButtonElement>(null);
  const confirmRef = useRef<HTMLButtonElement>(null);
  const returnFocusRef = useRef<HTMLElement | null>(null);

  const confirm = useCallback<ConfirmFn>((options) => {
    returnFocusRef.current = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    return new Promise<boolean>((resolve) => {
      setPending({ ...options, resolve });
    });
  }, []);

  const close = useCallback((value: boolean) => {
    setPending((current) => {
      current?.resolve(value);
      return null;
    });
  }, []);

  // Focus initial : bouton d’annulation pour les actions destructives (choix prudent), sinon confirmation.
  useEffect(() => {
    if (!pending) {
      returnFocusRef.current?.focus?.();
      return;
    }
    const target = pending.tone === 'danger' ? cancelRef.current : confirmRef.current;
    target?.focus();
  }, [pending]);

  // Échap annule ; Tab reste piégé dans la fenêtre.
  useEffect(() => {
    if (!pending) return;
    const onKey = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        close(false);
      } else if (event.key === 'Tab') {
        const focusables = [cancelRef.current, confirmRef.current].filter(Boolean) as HTMLButtonElement[];
        if (focusables.length === 0) return;
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      }
    };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [pending, close]);

  const danger = pending?.tone === 'danger';

  return (
    <ConfirmContext.Provider value={confirm}>
      {children}
      {pending && (
        <div className="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/50 p-4" onMouseDown={(e) => { if (e.target === e.currentTarget) close(false); }}>
          <div
            role="alertdialog"
            aria-modal="true"
            aria-labelledby="confirm-title"
            aria-describedby={pending.message ? 'confirm-message' : undefined}
            className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
          >
            <div className="flex items-start gap-3">
              <span
                aria-hidden="true"
                className={`mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-lg font-bold ${danger ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-800'}`}
              >
                {danger ? '!' : '?'}
              </span>
              <div className="min-w-0">
                <h2 id="confirm-title" className="text-lg font-bold text-slate-950">{pending.title}</h2>
                {pending.message && <p id="confirm-message" className="mt-2 text-sm leading-6 text-slate-700">{pending.message}</p>}
              </div>
            </div>
            <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
              <button
                ref={cancelRef}
                type="button"
                onClick={() => close(false)}
                className="min-h-11 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2"
              >
                {pending.cancelLabel ?? t('confx.cancel')}
              </button>
              <button
                ref={confirmRef}
                type="button"
                onClick={() => close(true)}
                className={`min-h-11 rounded-xl px-5 py-2.5 text-sm font-semibold text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 ${danger ? 'bg-red-700 hover:bg-red-800 focus-visible:ring-red-600' : 'bg-blue-800 hover:bg-blue-900 focus-visible:ring-blue-600'}`}
              >
                {pending.confirmLabel ?? t('confx.confirm')}
              </button>
            </div>
          </div>
        </div>
      )}
    </ConfirmContext.Provider>
  );
}
