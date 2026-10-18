'use client';

import { FormEvent, useEffect, useRef, useState } from 'react';
import Link from '@/components/AppLink';
import { MessageCircle, Send, X } from 'lucide-react';
import { API_V1_URL } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

interface Suggestion {
  id: number;
  title: string;
  slug: string;
  institution: string | null;
}

// Les messages du bot sont des clés de traduction : ils suivent la langue choisie.
type Message =
  | { from: 'bot'; key: string; vars?: Record<string, number>; results?: Suggestion[] }
  | { from: 'user'; text: string };

/**
 * Assistant de recherche : chaque réponse provient de l'API /search/suggest, jamais d'un texte inventé.
 */
export default function ChatBox() {
  const { language, t } = useLanguage();
  const [open, setOpen] = useState(false);
  const [messages, setMessages] = useState<Message[]>([{ from: 'bot', key: 'chat.welcome' }]);
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);
  const endRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    endRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, open]);

  const ask = async (event: FormEvent) => {
    event.preventDefault();
    const question = input.trim();
    if (question.length < 2 || loading) return;

    setMessages((list) => [...list, { from: 'user', text: question }]);
    setInput('');
    setLoading(true);

    try {
      const response = await fetch(`${API_V1_URL}/search/suggest?${new URLSearchParams({ q: question, lang: language })}`, {
        headers: { Accept: 'application/json' },
      });
      if (!response.ok) throw new Error(String(response.status));
      const { data } = (await response.json()) as { data: Suggestion[] };

      setMessages((list) => [
        ...list,
        data.length
          ? { from: 'bot', key: 'chat.found', vars: { n: data.length }, results: data }
          : { from: 'bot', key: 'chat.none' },
      ]);
    } catch {
      setMessages((list) => [...list, { from: 'bot', key: 'chat.unavailable' }]);
    } finally {
      setLoading(false);
    }
  };

  if (!open) {
    return (
      <button
        type="button"
        onClick={() => setOpen(true)}
        aria-label={t('chat.open')}
        className="fixed bottom-5 end-5 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-blue-700 via-yellow-500 to-red-600 text-white shadow-xl transition hover:scale-105 focus-visible:outline focus-visible:outline-4 focus-visible:outline-offset-2 focus-visible:outline-blue-700 print:hidden"
      >
        <MessageCircle aria-hidden="true" size={26} />
      </button>
    );
  }

  return (
    <section
      aria-label={t('chat.title')}
      className="fixed inset-x-3 bottom-3 z-50 flex max-h-[80vh] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl sm:inset-x-auto sm:bottom-5 sm:end-5 sm:w-96 print:hidden"
    >
      <header className="flex items-center justify-between bg-gradient-to-r from-blue-800 via-yellow-600 to-red-700 px-4 py-3 text-white">
        <div>
          <h2 className="font-bold">{t('chat.title')}</h2>
          <p className="text-xs text-white/85">{t('chat.subtitle')}</p>
        </div>
        <button type="button" onClick={() => setOpen(false)} aria-label={t('chat.close')} className="rounded-full p-2 hover:bg-white/20">
          <X aria-hidden="true" size={20} />
        </button>
      </header>

      <div className="flex-1 space-y-3 overflow-y-auto p-4" aria-live="polite">
        {messages.map((message, index) => (
          <div key={index} className={message.from === 'user' ? 'flex justify-end' : 'flex justify-start'}>
            <div className={`max-w-[85%] rounded-2xl px-4 py-2.5 text-sm ${message.from === 'user' ? 'bg-blue-700 text-white' : 'bg-slate-100 text-slate-800'}`}>
              <p>{message.from === 'user' ? message.text : t(message.key, message.vars)}</p>
              {message.from === 'bot' && message.results && (
                <ul className="mt-2 space-y-1.5">
                  {message.results.map((result) => (
                    <li key={result.id}>
                      <Link href={`/demarches/${result.slug}`} onClick={() => setOpen(false)} className="block rounded-lg bg-white px-3 py-2 font-semibold text-blue-800 hover:underline">
                        {result.title}
                        {result.institution && <span className="block text-xs font-normal text-slate-500">{result.institution}</span>}
                      </Link>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </div>
        ))}
        {loading && <p role="status" className="text-sm text-slate-500">{t('chat.searching')}</p>}
        <div ref={endRef} />
      </div>

      <div className="border-t border-slate-100 px-4 py-2 text-xs text-slate-500">
        <Link href="/demarches" className="font-semibold text-blue-800 hover:underline">{t('chat.catalog')}</Link>
        {' · '}
        <Link href="/signalement" className="font-semibold text-red-700 hover:underline">{t('chat.report')}</Link>
      </div>

      <form onSubmit={ask} className="flex gap-2 border-t border-slate-200 p-3">
        <label htmlFor="chat-question" className="sr-only">{t('chat.input_label')}</label>
        <input
          id="chat-question"
          value={input}
          onChange={(event) => setInput(event.target.value)}
          placeholder={t('chat.placeholder')}
          maxLength={100}
          className="flex-1 rounded-full border border-slate-300 px-4 py-2 text-base focus:border-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200"
        />
        <button type="submit" disabled={loading || input.trim().length < 2} aria-label={t('chat.send')} className="rounded-full bg-blue-700 p-2.5 text-white hover:bg-blue-800 disabled:opacity-50">
          <Send aria-hidden="true" size={18} className="rtl:-scale-x-100" />
        </button>
      </form>
    </section>
  );
}
