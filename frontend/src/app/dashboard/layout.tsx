'use client';

import { ReactNode, useEffect, useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { fetchApi } from '@/lib/api';
import { useLanguage } from '@/hooks/useLanguage';

export default function CitizenDashboardLayout({ children }: { children: ReactNode }) {
  const router = useRouter();
  const { t } = useLanguage();
  const [authorized, setAuthorized] = useState(false);
  const [failed, setFailed] = useState(false);

  useEffect(() => {
    if (!localStorage.getItem('token')) {
      router.replace('/auth/login');
      return;
    }

    let active = true;
    fetchApi('/auth/me')
      .then(() => active && setAuthorized(true))
      .catch(() => active && setFailed(true));

    return () => { active = false; };
  }, [router]);

  if (failed) {
    return (
      <main className="mx-auto max-w-3xl px-4 py-12">
        <p role="alert" className="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">{t('citizen.session_invalid')}</p>
        <Link href="/auth/login" className="mt-4 inline-block font-semibold text-blue-800 underline">{t('auth.login_link')}</Link>
      </main>
    );
  }
  if (!authorized) {
    return <main className="flex min-h-[60vh] items-center justify-center" role="status">{t('citizen.session_check')}</main>;
  }

  return children;
}
