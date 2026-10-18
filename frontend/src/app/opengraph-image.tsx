import { ImageResponse } from 'next/og';

// Image d'aperçu par défaut (partage d'un lien sur WhatsApp, Facebook…) — cahier des charges, module 5.
export const alt = 'DemarchesTchad — guide des démarches administratives au Tchad';
export const size = { width: 1200, height: 630 };
export const contentType = 'image/png';
// Moteur « edge » : le moteur Node de next/og échoue sous Windows (chargement de police par chemin de fichier).
export const runtime = 'edge';

export default function Image() {
  return new ImageResponse(
    (
      <div style={{ width: '100%', height: '100%', display: 'flex', flexDirection: 'column', background: '#1e3a8a', color: 'white', padding: 80 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 24 }}>
          <div style={{ width: 96, height: 96, borderRadius: 24, background: '#fbbf24', color: '#1e3a8a', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 48, fontWeight: 800 }}>DT</div>
          <div style={{ fontSize: 56, fontWeight: 800 }}>DemarchesTchad</div>
        </div>
        <div style={{ marginTop: 70, fontSize: 64, fontWeight: 800, lineHeight: 1.15, maxWidth: 1000 }}>
          Pièces à fournir, coût, délai et lieu de vos démarches au Tchad
        </div>
        {/* Image générée par le serveur, sans langue choisie : texte bilingue (le site reste en fr/en/ar). */}
        <div style={{ marginTop: 'auto', fontSize: 30, color: '#bfdbfe' }}>Guide citoyen indépendant · Independent guide · gratuit · free · sans inscription</div>
      </div>
    ),
    size,
  );
}
