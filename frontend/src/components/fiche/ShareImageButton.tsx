'use client';

import { useState } from 'react';
import { ImageDown, Loader2 } from 'lucide-react';
import { useLanguage } from '@/hooks/useLanguage';
import { translate, type Language } from '@/lib/i18n';

interface Props {
  slug: string;
  title: string;
  cost: string;
  delay: string | null;
  documents: string[];
  /** Langue du contenu affiché : l'image est entièrement rédigée dans cette langue. */
  language: Language;
}

// Format portrait 4:5 (1080×1350), le mieux affiché par WhatsApp et les réseaux sociaux.
const WIDTH = 1080;
const HEIGHT = 1350;
const MARGIN = 80;
const CONFIGURED_SITE_URL = (process.env.NEXT_PUBLIC_APP_URL || '').replace(/\/+$/, '');

/** Adresse publique de la fiche : celle configurée, sinon celle du site réellement ouvert (jamais « localhost » en dur). */
function ficheUrl(slug: string): string {
  const origin = CONFIGURED_SITE_URL || (typeof window !== 'undefined' ? window.location.origin : '');
  return `${origin}/demarches/${slug}`;
}

/** Coupe un texte en lignes qui tiennent dans la largeur donnée. */
function wrap(ctx: CanvasRenderingContext2D, text: string, maxWidth: number, maxLines: number): string[] {
  const words = text.split(/\s+/);
  const lines: string[] = [];
  let line = '';
  for (const word of words) {
    const candidate = line ? `${line} ${word}` : word;
    if (ctx.measureText(candidate).width > maxWidth && line) {
      lines.push(line);
      line = word;
      if (lines.length === maxLines) break;
    } else {
      line = candidate;
    }
  }
  if (lines.length < maxLines && line) lines.push(line);
  if (lines.length === maxLines && words.join(' ') !== lines.join(' ')) {
    lines[maxLines - 1] = `${lines[maxLines - 1].replace(/\s*\S*$/, '')} …`;
  }
  return lines;
}

function roundRect(ctx: CanvasRenderingContext2D, x: number, y: number, w: number, h: number, r: number) {
  ctx.beginPath();
  ctx.moveTo(x + r, y);
  ctx.arcTo(x + w, y, x + w, y + h, r);
  ctx.arcTo(x + w, y + h, x, y + h, r);
  ctx.arcTo(x, y + h, x, y, r);
  ctx.arcTo(x, y, x + w, y, r);
  ctx.closePath();
}

/** Fiche en image à partager, dans la langue de la fiche affichée, aux couleurs du site (aucun élément officiel imité). */
export function ShareImageButton({ slug, title, cost, delay, documents, language }: Props) {
  const { t } = useLanguage();
  const [busy, setBusy] = useState(false);
  const [message, setMessage] = useState<{ text: string; ok: boolean } | null>(null);
  const tc = (key: string, vars?: Record<string, string | number>) => translate(language, key, vars);

  const draw = (): HTMLCanvasElement => {
    const canvas = document.createElement('canvas');
    canvas.width = WIDTH;
    canvas.height = HEIGHT;
    const ctx = canvas.getContext('2d');
    if (!ctx) throw new Error('canvas');

    const rtl = language === 'ar';
    ctx.direction = rtl ? 'rtl' : 'ltr';
    ctx.textAlign = rtl ? 'right' : 'left';
    const x = rtl ? WIDTH - MARGIN : MARGIN;
    const font = (size: number, weight = 400) => `${weight} ${size}px "Segoe UI", Roboto, "Noto Sans Arabic", Tahoma, Arial, sans-serif`;
    const textWidth = WIDTH - MARGIN * 2;

    // Fond clair et bandeau aux couleurs du site (bleu, filet ambre).
    ctx.fillStyle = '#f8fafc';
    ctx.fillRect(0, 0, WIDTH, HEIGHT);
    const band = ctx.createLinearGradient(0, 0, WIDTH, 0);
    band.addColorStop(0, '#1e3a8a');
    band.addColorStop(1, '#1e40af');
    ctx.fillStyle = band;
    ctx.fillRect(0, 0, WIDTH, 160);
    ctx.fillStyle = '#fbbf24';
    ctx.fillRect(0, 160, WIDTH, 10);
    ctx.fillStyle = '#ffffff';
    ctx.font = font(50, 800);
    ctx.direction = 'ltr';
    ctx.textAlign = rtl ? 'right' : 'left';
    ctx.fillText('DemarchesTchad', x, 100);
    ctx.direction = rtl ? 'rtl' : 'ltr';

    // Carte blanche principale
    const cardY = 220;
    ctx.fillStyle = '#ffffff';
    ctx.shadowColor = 'rgba(15, 23, 42, 0.08)';
    ctx.shadowBlur = 30;
    roundRect(ctx, MARGIN - 30, cardY, textWidth + 60, HEIGHT - cardY - 200, 36);
    ctx.fill();
    ctx.shadowBlur = 0;

    // Titre
    let y = cardY + 90;
    ctx.fillStyle = '#0f172a';
    ctx.font = font(60, 800);
    for (const line of wrap(ctx, title, textWidth - 20, 3)) {
      ctx.fillText(line, x, y);
      y += 74;
    }

    // Coût et délai : deux pastilles de même taille
    y += 16;
    const boxWidth = (textWidth - 24) / 2;
    const boxes: Array<[string, string, string, string]> = [
      [tc('shareimg.cost'), cost, '#ecfdf5', '#047857'],
      [tc('shareimg.delay'), delay || '—', '#fffbeb', '#b45309'],
    ];
    boxes.forEach(([label, value, background, accent], index) => {
      const boxX = rtl ? WIDTH - MARGIN - boxWidth - index * (boxWidth + 24) : MARGIN + index * (boxWidth + 24);
      ctx.fillStyle = background;
      roundRect(ctx, boxX, y, boxWidth, 140, 24);
      ctx.fill();
      const innerX = rtl ? boxX + boxWidth - 28 : boxX + 28;
      ctx.fillStyle = accent;
      ctx.font = font(30, 700);
      ctx.fillText(label, innerX, y + 50);
      ctx.fillStyle = '#0f172a';
      ctx.font = font(40, 800);
      ctx.fillText(wrap(ctx, value, boxWidth - 56, 1)[0] ?? '', innerX, y + 106);
    });
    y += 200;

    // Pièces à fournir
    ctx.fillStyle = '#1e3a8a';
    ctx.font = font(40, 800);
    ctx.fillText(tc('shareimg.documents'), x, y);
    y += 62;
    ctx.font = font(34, 400);
    const shown = documents.slice(0, 6);
    let drawn = 0;
    for (const document of shown) {
      const lines = wrap(ctx, document, textWidth - 70, 2);
      if (y + 44 * lines.length > HEIGHT - 260) break;
      ctx.fillStyle = '#059669';
      ctx.font = font(34, 700);
      ctx.fillText('✓', x, y);
      ctx.fillStyle = '#334155';
      ctx.font = font(34, 400);
      lines.forEach((line, i) => ctx.fillText(line, rtl ? x - 55 : x + 55, y + i * 44));
      y += 44 * lines.length + 20;
      drawn++;
    }
    if (documents.length > drawn) {
      ctx.fillStyle = '#64748b';
      ctx.font = font(32, 600);
      ctx.fillText(tc('shareimg.more_docs', { n: documents.length - drawn }), x, y);
    }

    // Pied : adresse de la fiche complète
    ctx.fillStyle = '#0b1329';
    ctx.fillRect(0, HEIGHT - 150, WIDTH, 150);
    ctx.fillStyle = '#fbbf24';
    ctx.font = font(30, 600);
    ctx.fillText(tc('shareimg.footer'), x, HEIGHT - 94);
    // L'adresse n'a pas d'espace (impossible à couper) : on réduit la police jusqu'à ce qu'elle tienne.
    const url = ficheUrl(slug);
    let urlSize = 34;
    ctx.font = font(urlSize, 700);
    while (ctx.measureText(url).width > textWidth && urlSize > 18) {
      urlSize -= 2;
      ctx.font = font(urlSize, 700);
    }
    ctx.fillStyle = '#ffffff';
    ctx.direction = 'ltr';
    ctx.textAlign = 'left';
    ctx.fillText(url, MARGIN, HEIGHT - 44, textWidth);

    return canvas;
  };

  const share = async () => {
    setBusy(true);
    setMessage(null);
    try {
      const canvas = draw();
      const blob = await new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, 'image/png'));
      if (!blob) throw new Error('blob');
      const file = new File([blob], `${slug}.png`, { type: 'image/png' });
      const nav = navigator as Navigator & { canShare?: (data: ShareData) => boolean };

      if (nav.canShare?.({ files: [file] })) {
        await navigator.share({ files: [file], title, text: ficheUrl(slug) });
        setMessage({ text: t('printx.image_shared'), ok: true });
      } else {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = file.name;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
        setMessage({ text: t('printx.image_saved'), ok: true });
      }
    } catch (error) {
      // Partage annulé par l'utilisateur : ce n'est pas une erreur.
      if (!(error instanceof DOMException && error.name === 'AbortError')) setMessage({ text: t('shareimg.error'), ok: false });
    } finally {
      setBusy(false);
    }
  };

  return (
    <div className="print:hidden">
      <button
        type="button"
        onClick={share}
        disabled={busy}
        className="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-900 disabled:opacity-60"
      >
        {busy ? <Loader2 size={18} aria-hidden="true" className="animate-spin" /> : <ImageDown size={18} aria-hidden="true" />}
        {busy ? t('shareimg.generating') : t('shareimg.button')}
      </button>
      {message && <p role="status" className={`mt-1 max-w-xs text-xs ${message.ok ? 'text-emerald-700' : 'text-red-700'}`}>{message.text}</p>}
    </div>
  );
}
