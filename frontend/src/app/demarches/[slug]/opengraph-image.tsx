import { ImageResponse } from 'next/og';
import { API_V1_URL } from '@/lib/api';
import { serverApiHeaders } from '@/lib/server-api';

// Aperçu d'une fiche quand son lien est partagé : titre, coût et délai (cahier des charges, module 5).
export const alt = 'Fiche démarche — DemarchesTchad';
export const size = { width: 1200, height: 630 };
export const contentType = 'image/png';
// Moteur « edge » : le moteur Node de next/og échoue sous Windows (chargement de police par chemin de fichier).
export const runtime = 'edge';
export const revalidate = 3600;

interface Fiche { title: string; cost: string; processing_time: string | null; categorie?: { name: string } }

export default async function Image({ params }: { params: { slug: string } }) {
  let fiche: Fiche | null = null;
  try {
    const res = await fetch(`${API_V1_URL}/demarches/${encodeURIComponent(params.slug)}`, { next: { revalidate }, headers: serverApiHeaders() });
    if (res.ok) fiche = ((await res.json()) as { data: Fiche }).data;
  } catch {
    fiche = null; // API injoignable : aperçu générique.
  }

  return new ImageResponse(
    (
      <div style={{ width: '100%', height: '100%', display: 'flex', flexDirection: 'column', background: 'white', color: '#0f172a' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 20, background: '#1e3a8a', color: 'white', padding: '36px 70px', fontSize: 40, fontWeight: 800 }}>
          DemarchesTchad
          {fiche?.categorie?.name && <span style={{ marginLeft: 'auto', fontSize: 28, color: '#fbbf24' }}>{fiche.categorie.name}</span>}
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', padding: '50px 70px', flexGrow: 1 }}>
          <div style={{ fontSize: 66, fontWeight: 800, lineHeight: 1.1 }}>{fiche?.title ?? 'Démarche administrative au Tchad'}</div>
          {fiche && (
            <div style={{ display: 'flex', gap: 30, marginTop: 'auto' }}>
              <div style={{ display: 'flex', flexDirection: 'column', background: '#ecfdf5', borderRadius: 20, padding: '24px 34px' }}>
                <span style={{ fontSize: 26, color: '#047857' }}>Coût</span>
                <span style={{ fontSize: 44, fontWeight: 800 }}>{fiche.cost}</span>
              </div>
              {fiche.processing_time && (
                <div style={{ display: 'flex', flexDirection: 'column', background: '#fffbeb', borderRadius: 20, padding: '24px 34px' }}>
                  <span style={{ fontSize: 26, color: '#b45309' }}>Délai</span>
                  <span style={{ fontSize: 44, fontWeight: 800 }}>{fiche.processing_time}</span>
                </div>
              )}
            </div>
          )}
        </div>
      </div>
    ),
    size,
  );
}
