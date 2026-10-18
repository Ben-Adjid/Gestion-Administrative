'use client';

import { useEffect, useId, useRef, useState } from 'react';
import Link from '@/components/AppLink';
import { useRouter } from 'next/navigation';
import { ArrowRight, Banknote, Clock, Loader2, Search, X } from 'lucide-react';
import { API_V1_URL } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

interface SuggestResult {
  id: number;
  title: string;
  slug: string;
  excerpt?: string;
  category?: string | null;
  cost?: string;
}

interface SearchAutocompleteProps {
  placeholder?: string;
  className?: string;
  /** Variante compacte pour la barre d'en-tête. */
  compact?: boolean;
}

// Cahier des charges (module 2) : autocomplétion dès 3 caractères, délai 300 ms, historique local sans compte.
const MIN_CHARS = 3;
const DEBOUNCE_MS = 300;
const HISTORY_KEY = 'search_history';
const HISTORY_SIZE = 5;

function readHistory(): string[] {
  try {
    const saved = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
    return Array.isArray(saved) ? saved.filter((item): item is string => typeof item === 'string').slice(0, HISTORY_SIZE) : [];
  } catch {
    return [];
  }
}

function writeHistory(items: string[]) {
  try {
    localStorage.setItem(HISTORY_KEY, JSON.stringify(items.slice(0, HISTORY_SIZE)));
  } catch {
    // Stockage indisponible : l'historique n'est simplement pas conservé.
  }
}

export default function SearchAutocomplete({ placeholder, className = '', compact = false }: SearchAutocompleteProps) {
  const router = useRouter();
  const { language, t } = useLanguage();
  const inputId = useId();
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<SuggestResult[]>([]);
  const [history, setHistory] = useState<string[]>([]);
  const [loading, setLoading] = useState(false);
  const [isOpen, setIsOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    setHistory(readHistory());
    function handleClickOutside(event: MouseEvent) {
      if (containerRef.current && !containerRef.current.contains(event.target as Node)) setIsOpen(false);
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  useEffect(() => {
    const trimmed = query.trim();
    if (trimmed.length < MIN_CHARS) {
      setResults([]);
      setLoading(false);
      return;
    }

    const controller = new AbortController();
    setLoading(true);
    const timer = setTimeout(async () => {
      try {
        const res = await fetch(`${API_V1_URL}/search/suggest?${new URLSearchParams({ q: trimmed, lang: language })}`, {
          signal: controller.signal,
          headers: { Accept: 'application/json' },
        });
        if (res.ok) {
          const json = await res.json();
          setResults(json.data || []);
          setIsOpen(true);
        }
      } catch (err) {
        if (err instanceof DOMException && err.name === 'AbortError') return;
      } finally {
        setLoading(false);
      }
    }, DEBOUNCE_MS);

    return () => {
      clearTimeout(timer);
      controller.abort();
    };
  }, [query, language]);

  const remember = (term: string) => {
    const value = term.trim();
    if (value.length < 2) return;
    const next = [value, ...history.filter((item) => item.toLowerCase() !== value.toLowerCase())].slice(0, HISTORY_SIZE);
    setHistory(next);
    writeHistory(next);
  };

  const go = (term: string) => {
    remember(term);
    setIsOpen(false);
    router.push(`/demarches?q=${encodeURIComponent(term.trim())}`);
  };

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault();
    if (query.trim()) go(query);
  };

  const clearHistory = () => {
    setHistory([]);
    writeHistory([]);
  };

  const trimmed = query.trim();
  const showHistory = isOpen && trimmed.length === 0 && history.length > 0;
  const showResults = isOpen && trimmed.length >= MIN_CHARS;

  return (
    <div ref={containerRef} className={`relative w-full ${className}`}>
      <form onSubmit={handleSubmit} role="search" className="relative">
        <label htmlFor={inputId} className="sr-only">{t('search.label')}</label>
        <div className={`pointer-events-none absolute top-1/2 -translate-y-1/2 text-slate-400 ${compact ? 'start-3' : 'start-4'}`}>
          {loading ? <Loader2 size={compact ? 16 : 18} className="animate-spin text-blue-600" aria-hidden="true" /> : <Search size={compact ? 16 : 18} aria-hidden="true" />}
        </div>
        <input
          id={inputId}
          type="search"
          value={query}
          onChange={(event) => { setQuery(event.target.value); setIsOpen(true); }}
          onFocus={() => setIsOpen(true)}
          onKeyDown={(event) => event.key === 'Escape' && setIsOpen(false)}
          placeholder={placeholder || t('search.placeholder')}
          autoComplete="off"
          className={compact
            ? 'w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pe-9 ps-9 text-sm text-slate-900 outline-none transition focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-100'
            : 'w-full rounded-2xl border border-slate-200 bg-white py-3.5 pe-12 ps-12 text-base text-slate-900 shadow-md shadow-slate-200/50 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-100'}
        />
        {query && (
          <button type="submit" aria-label={t('search.submit')} className={`absolute end-2 top-1/2 -translate-y-1/2 rounded-lg bg-blue-800 text-white transition hover:bg-blue-900 ${compact ? 'p-1.5' : 'end-3 p-2'}`}>
            <ArrowRight size={compact ? 13 : 15} aria-hidden="true" className="rtl:-scale-x-100" />
          </button>
        )}
      </form>

      {showHistory && (
        <div className="absolute inset-x-0 top-full z-50 mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
          <div className="flex items-center justify-between bg-slate-50 px-4 py-2 text-xs font-bold uppercase tracking-wider text-slate-500">
            {t('searchx.recent')}
            <button type="button" onClick={clearHistory} className="inline-flex items-center gap-1 normal-case tracking-normal text-slate-500 hover:text-slate-800">
              <X size={12} aria-hidden="true" /> {t('searchx.clear_history')}
            </button>
          </div>
          <ul className="divide-y divide-slate-100">
            {history.map((item) => (
              <li key={item}>
                <button type="button" onClick={() => { setQuery(item); go(item); }} className="flex w-full items-center gap-2 px-4 py-2.5 text-start text-sm text-slate-700 hover:bg-blue-50/50">
                  <Clock size={14} aria-hidden="true" className="text-slate-400" /> {item}
                </button>
              </li>
            ))}
          </ul>
        </div>
      )}

      {showResults && !loading && (
        <div className="absolute inset-x-0 top-full z-50 mt-2 min-w-[18rem] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
          {results.length > 0 ? (
            <div className="max-h-96 divide-y divide-slate-100 overflow-y-auto">
              <div className="bg-slate-50 px-4 py-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                {t('search.suggestions', { n: results.length })}
              </div>
              {results.map((item) => (
                <Link
                  key={item.id}
                  href={`/demarches/${item.slug}`}
                  onClick={() => { remember(query); setIsOpen(false); }}
                  className="group flex items-center justify-between p-3.5 transition hover:bg-blue-50/50 sm:px-4"
                >
                  <div className="min-w-0 pe-3">
                    <p className="line-clamp-1 text-sm font-semibold text-slate-900 transition group-hover:text-blue-800">{item.title}</p>
                    {item.excerpt && <p className="mt-0.5 line-clamp-1 text-xs text-slate-500">{item.excerpt}</p>}
                    <div className="mt-1 flex flex-wrap items-center gap-2 text-xs">
                      {item.category && <span className="rounded bg-blue-50 px-2 py-0.5 font-semibold text-blue-800">{item.category}</span>}
                      {item.cost && (
                        <span className="inline-flex items-center gap-1 rounded bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-700">
                          <Banknote size={12} aria-hidden="true" /> {item.cost}
                        </span>
                      )}
                    </div>
                  </div>
                  <ArrowRight size={15} aria-hidden="true" className="shrink-0 text-slate-300 transition group-hover:text-blue-600 rtl:-scale-x-100" />
                </Link>
              ))}
              <div className="bg-slate-50 p-3 text-center">
                <button type="button" onClick={() => go(query)} className="text-xs font-bold text-blue-700 hover:underline">
                  {t('search.see_all', { q: trimmed })}
                </button>
              </div>
            </div>
          ) : (
            <div className="p-6 text-center text-sm text-slate-500">
              {t('search.none', { q: trimmed })}
              <div className="mt-2">
                <button type="button" onClick={() => go(query)} className="text-xs font-bold text-blue-700 hover:underline">{t('search.search_all')}</button>
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
