'use client';

import { ReactNode, useEffect, useState } from 'react';
import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { ShieldAlert } from 'lucide-react';
import { ApiError, fetchApi } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

type Capabilities = Record<string, boolean>;

interface MeResponse {
  admin: { id: number; name: string; email: string; role: string; capabilities: Capabilities };
}

// Pages réservées -> capacité requise (miroir des middlewares serveur ; la protection réelle reste l'API).
const PAGE_CAPABILITIES: Array<[string, string]> = [
  ['/admin/users', 'manage_team'],
  ['/admin/activity-logs', 'view_audit_log'],
  ['/admin/cles-api', 'manage_api_keys'],
  ['/admin/categories', 'manage_categories'],
  ['/admin/analytics', 'view_analytics'],
  ['/admin/villes', 'manage_reference'],
  ['/admin/geo', 'manage_reference'],
  ['/admin/qualite', 'manage_reference'],
  ['/admin/parcours', 'manage_reference'],
  ['/admin/calendrier', 'manage_reference'],
  ['/admin/validite', 'manage_reference'],
  ['/admin/synonymes', 'manage_reference'],
  ['/admin/avis', 'moderate_community'],
  ['/admin/questions', 'moderate_community'],
  ['/admin/import', 'import_demarches'],
  ['/admin/recherches', 'view_search_misses'],
];

export default function AdminLayout({ children }: { children: ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();
  const { t } = useLanguage();
  const isLoginPage = pathname === '/admin/login';
  const [capabilities, setCapabilities] = useState<Capabilities | null>(null);
  const [failure, setFailure] = useState<'session' | 'network' | null>(null);
  const [attempt, setAttempt] = useState(0);

  useEffect(() => {
    if (isLoginPage) return;

    if (!localStorage.getItem('admin_token')) {
      router.replace('/admin/login');
      return;
    }

    let active = true;
    const check = (retriesLeft: number) => {
      fetchApi<MeResponse>('/admin/auth/me')
        .then(({ admin }) => {
          // Le rôle affiché dans les menus vient toujours du serveur, jamais d'une valeur locale périmée.
          localStorage.setItem('admin', JSON.stringify(admin));
          if (!active) return;
          setFailure(null);
          setCapabilities(admin.capabilities);
        })
        .catch((e: unknown) => {
          if (!active) return;
          // 401 : session réellement expirée (fetchApi redirige déjà vers la connexion).
          if (e instanceof ApiError && (e.status === 401 || e.status === 403)) {
            setFailure('session');
          } else if (retriesLeft > 0) {
            // Coupure réseau ou serveur momentanément indisponible : nouvel essai avant d'alerter.
            window.setTimeout(() => active && check(retriesLeft - 1), 1500);
          } else {
            setFailure('network');
          }
        });
    };
    check(1);

    return () => { active = false; };
  }, [isLoginPage, pathname, router, attempt]);

  if (isLoginPage) return children;

  if (failure) {
    return (
      <main className="mx-auto max-w-3xl px-4 py-12">
        <p role="alert" className="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
          {t(failure === 'session' ? 'citizen.session_invalid' : 'admin.server_unreachable')}
        </p>
        {failure === 'session' ? (
          <Link href="/admin/login" className="mt-4 inline-block font-semibold text-blue-800 underline">{t('admin.relogin')}</Link>
        ) : (
          <button type="button" onClick={() => { setFailure(null); setAttempt((n) => n + 1); }} className="mt-4 rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">
            {t('admin.retry')}
          </button>
        )}
      </main>
    );
  }

  if (!capabilities) {
    return <main className="flex min-h-[60vh] items-center justify-center" role="status">{t('admin.session_check')}</main>;
  }

  const blocked = PAGE_CAPABILITIES.some(([prefix, capability]) => pathname.startsWith(prefix) && !capabilities[capability]);
  if (blocked) {
    return (
      <main className="flex min-h-[70vh] items-center justify-center bg-slate-50 px-4">
        <section className="max-w-md rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
          <ShieldAlert aria-hidden="true" className="mx-auto text-amber-600" size={40} />
          <h1 className="mt-4 text-2xl font-bold text-slate-950">{t('admin.restricted_title')}</h1>
          <p className="mt-2 text-slate-600">{t('admin.restricted_text')}</p>
          <Link href="/admin/dashboard" className="mt-6 inline-block rounded-lg bg-blue-700 px-5 py-2.5 font-semibold text-white hover:bg-blue-800">
            {t('admin.back_dashboard')}
          </Link>
        </section>
      </main>
    );
  }

  // Réserve la place de la barre de navigation mobile fixée en bas d'écran.
  return <div className="pb-20 md:pb-0">{children}</div>;
}
