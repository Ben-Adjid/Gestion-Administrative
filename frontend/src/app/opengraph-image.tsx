import { ImageResponse } from 'next/og';

// Image d'aperçu par défaut (partage d'un lien sur WhatsApp, Facebook…) — cahier des charges, module 5.
export const alt = 'DemarchesTchad — guide des démarches administratives au Tchad';
export const size = { width: 1200, height: 630 };
export const contentType = 'image/png';
// Moteur « edge » : le moteur Node de next/og échoue sous Windows (chargement de police par chemin de fichier).
export const runtime = 'edge';


// Logo lu depuis le dossier public (moteur edge : pas d’accès au disque, donc chargement par URL du projet).
async function logoDataUri(): Promise<string> {
  const bytes = await fetch(new URL("../../public/icon-512.png", import.meta.url)).then((r) => r.arrayBuffer());
  let binary = "";
  new Uint8Array(bytes).forEach((b) => { binary += String.fromCharCode(b); });
  return "data:image/png;base64," + btoa(binary);
}

export default async function Image() {
  const logo = await logoDataUri();
  return new ImageResponse(
    (
      <div style={{ width: '100%', height: '100%', display: 'flex', flexDirection: 'column', background: '#1e3a8a', color: 'white', padding: 80 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 24 }}>
          <img src={logo} width={120} height={120} style={{ borderRadius: 28 }} />
          <div style={{ fontSize: 56, fontWeight: 800 }}>DemarchesTchad</div>
        </div>
        <div style={{ marginTop: 70, fontSize: 64, fontWeight: 800, lineHeight: 1.15, maxWidth: 1000 }}>
          Pièces à fournir, coût, délai et lieu de vos démarches au Tchad
        </div>
        {/* Image générée par le serveur, sans langue choisie par la personne qui la voit : texte en français uniquement. */}
        <div style={{ marginTop: 'auto', fontSize: 30, color: '#bfdbfe' }}>Guide citoyen indépendant · gratuit · sans inscription</div>
      </div>
    ),
    size,
  );
}
