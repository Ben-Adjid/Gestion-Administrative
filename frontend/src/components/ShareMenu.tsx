'use client';

import { useEffect, useRef, useState } from 'react';
import { Check, Copy, Link2, Share2 } from 'lucide-react';
import { useLanguage } from '@/hooks/useLanguage';

interface ShareMenuProps {
  demarcheId: number;
  demarcheTitle: string;
  demarcheSlug: string;
}

const SITE_URL = (process.env.NEXT_PUBLIC_APP_URL || '').replace(/\/+$/, '');

/**
 * Partage d'une fiche publique : feuille de partage de l'appareil quand elle existe,
 * sinon (ou en cas d'échec) menu WhatsApp / Facebook / Telegram + copie du lien, avec un retour clair.
 */
export default function ShareMenu({ demarcheTitle, demarcheSlug }: ShareMenuProps) {
  const { t } = useLanguage();
  const [open, setOpen] = useState(false);
  const [feedback, setFeedback] = useState<{ text: string; ok: boolean } | null>(null);
  const [copied, setCopied] = useState(false);
  const [url, setUrl] = useState(`${SITE_URL}/demarches/${demarcheSlug}`);
  const rootRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    setUrl(`${SITE_URL || window.location.origin}/demarches/${demarcheSlug}`);
  }, [demarcheSlug]);

  useEffect(() => {
    if (!open) return;
    const close = (event: MouseEvent | KeyboardEvent) => {
      if (event instanceof KeyboardEvent ? event.key === 'Escape' : !rootRef.current?.contains(event.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', close);
    document.addEventListener('keydown', close);
    return () => {
      document.removeEventListener('mousedown', close);
      document.removeEventListener('keydown', close);
    };
  }, [open]);

  const text = t('actions.share_text', { title: demarcheTitle });
  const links = [
    { label: 'WhatsApp', href: `https://wa.me/?text=${encodeURIComponent(`${text} ${url}`)}` },
    { label: 'Facebook', href: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}` },
    { label: 'Telegram', href: `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}` },
  ];

  const toggle = async () => {
    setFeedback(null);
    if (!open && typeof navigator !== 'undefined' && typeof navigator.share === 'function') {
      try {
        await navigator.share({ title: demarcheTitle, text, url });
        setFeedback({ text: t('printx.share_ok'), ok: true });
        return;
      } catch (error) {
        // Annulé par l'utilisateur : rien à signaler. Autre échec : on propose les autres moyens.
        if (error instanceof DOMException && error.name === 'AbortError') return;
        setFeedback({ text: t('printx.share_error'), ok: false });
      }
    }
    setOpen((value) => !value);
  };

  const copy = async () => {
    try {
      await navigator.clipboard.writeText(url);
      setCopied(true);
      setFeedback({ text: t('printx.copy_ok'), ok: true });
      window.setTimeout(() => setCopied(false), 2500);
    } catch {
      // Presse-papiers refusé (navigateur ancien, page non sécurisée) : le lien est sélectionné pour une copie manuelle.
      inputRef.current?.select();
      setFeedback({ text: t('printx.copy_error'), ok: false });
    }
  };

  return (
    <div ref={rootRef} className="relative inline-block print:hidden">
      <button
        type="button"
        onClick={toggle}
        aria-haspopup="menu"
        aria-expanded={open}
        aria-label={t('printx.share')}
        title={t('printx.share')}
        className="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700"
      >
        <Share2 aria-hidden="true" size={18} />
        <span>{t('actions.share')}</span>
      </button>

      {open && (
        <div role="menu" className="absolute start-0 top-full z-30 mt-2 w-72 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
          {links.map((link) => (
            <a
              key={link.label}
              role="menuitem"
              href={link.href}
              target="_blank"
              rel="noopener noreferrer"
              onClick={() => setOpen(false)}
              className="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
            >
              {link.label}
            </a>
          ))}
          <div className="mt-1 border-t border-slate-100 p-2">
            <label className="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-2 py-1.5">
              <Link2 aria-hidden="true" size={14} className="shrink-0 text-slate-400" />
              <span className="sr-only">{t('actions.copy_link')}</span>
              <input ref={inputRef} readOnly value={url} dir="ltr" onFocus={(e) => e.currentTarget.select()} className="min-w-0 flex-1 bg-transparent text-xs text-slate-600 outline-none" />
            </label>
            <button
              type="button"
              role="menuitem"
              onClick={copy}
              className="mt-2 flex w-full items-center justify-center gap-2 rounded-lg bg-blue-800 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-900"
            >
              {copied ? <Check aria-hidden="true" size={16} /> : <Copy aria-hidden="true" size={16} />}
              {copied ? t('actions.link_copied') : t('actions.copy_link')}
            </button>
          </div>
        </div>
      )}
      {feedback && <p role="status" className={`mt-1 max-w-xs text-xs ${feedback.ok ? 'text-emerald-700' : 'text-red-700'}`}>{feedback.text}</p>}
    </div>
  );
}
