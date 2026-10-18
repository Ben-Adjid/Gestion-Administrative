'use client';

import { useEffect } from 'react';
import { usePathname } from 'next/navigation';
import { fetchApi } from '@/lib/api';

export function PageViewTracker() {
  const pathname = usePathname();

  useEffect(() => {
    if (!pathname || pathname.startsWith('/admin')) return;

    let visitorId = sessionStorage.getItem('demarches_visitor_session');
    if (!visitorId) {
      visitorId = crypto.randomUUID();
      sessionStorage.setItem('demarches_visitor_session', visitorId);
    }

    void fetchApi('/api/v1/analytics/page-views', {
      method: 'POST',
      body: JSON.stringify({ visitor_id: visitorId, path: pathname }),
    }).catch(() => undefined);
  }, [pathname]);

  return null;
}