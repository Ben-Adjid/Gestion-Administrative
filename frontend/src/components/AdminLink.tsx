'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { useLanguage } from '@/hooks/useLanguage';

/** Lien discret vers l'administration, affiché seulement à un membre de l'équipe déjà connecté. */
export function AdminLink() {
  const { t } = useLanguage();
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    try {
      setVisible(!!localStorage.getItem('admin_token'));
    } catch {
      setVisible(false);
    }
  }, []);

  if (!visible) return null;

  return (
    <Link href="/admin/dashboard" className="font-semibold text-amber-400 transition hover:text-amber-300">
      {t('admin.dashboard')}
    </Link>
  );
}
