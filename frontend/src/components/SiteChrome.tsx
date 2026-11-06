'use client';

import { usePathname } from 'next/navigation';
import { Footer } from '@/components/Footer';
import { Navbar } from '@/components/Navbar';
import { PageViewTracker } from '@/components/PageViewTracker';
import ChatBox from '@/components/ChatBox';
import { OfflineSupport } from '@/components/OfflineSupport';
import { LanguageProvider } from '@/contexts/LanguageContext';
import { PreferencesProvider, usePreferences } from '@/contexts/PreferencesContext';
import { ConfirmProvider } from '@/components/ConfirmProvider';

function Chrome({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const { liteMode } = usePreferences();
  const isAdminRoute = pathname.startsWith('/admin');

  return (
    <div className="flex min-h-screen flex-col">
      {!isAdminRoute && <PageViewTracker />}
      <OfflineSupport />
      {!isAdminRoute && <Navbar />}
      <div className="flex-grow">{children}</div>
      {!isAdminRoute && <Footer />}
      {/* L'assistant est retiré en mode économie de données. */}
      {!isAdminRoute && !liteMode && <ChatBox />}
    </div>
  );
}

export function SiteChrome({ children }: { children: React.ReactNode }) {
  return (
    <LanguageProvider>
      <PreferencesProvider>
        <ConfirmProvider>
          <Chrome>{children}</Chrome>
        </ConfirmProvider>
      </PreferencesProvider>
    </LanguageProvider>
  );
}
