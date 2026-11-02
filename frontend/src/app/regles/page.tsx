import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Règles communes à toutes les démarches',
  description: 'Règles transversales, règles pour les mineurs et règles pour les étrangers applicables aux démarches administratives au Tchad.',
};

const commonRules = [
  {
    title: 'Validité des pièces d\'identité',
    text: 'Toute CNI ou passeport présenté doit être en cours de validité. Une pièce expirée est systématiquement refusée, même si le demandeur peut prouver son identité autrement.',
  },
  {
    title: 'Photos d\'identité',
    text: 'Les photos doivent être récentes (moins de 6 mois), fond blanc, tête nue, face visible. Les photos floues, trop sombres ou avec lunettes de soleil sont refusées.',
  },
  {
    title: 'Originaux exigés',
    text: 'Toutes les administrations exigent les originaux + copies. Ne jamais se présenter avec des photocopies seules. Conserver les originaux après présentation.',
  },
  {
    title: 'Dossier incomplet = refus',
    text: 'Un dossier incomplet est systématiquement refusé sans exception. Toutes les pièces listées doivent être présentes lors du dépôt.',
  },
  {
    title: 'Timbres fiscaux',
    text: 'Le timbre fiscal doit être acheté AVANT le dépôt du dossier (Trésorerie nationale ou points de vente agréés). Un dossier sans timbre fiscal est irrecevable.',
  },
  {
    title: 'Récépissé de dépôt',
    text: 'Toujours exiger et conserver le récépissé lors du dépôt. Sans récépissé, il est impossible de suivre le dossier ou de réclamer en cas de perte.',
  },
  {
    title: 'Délais légaux',
    text: 'Certains délais sont impératifs et leur dépassement entraîne des procédures plus lourdes (jugement supplétif pour naissance tardive, majoration pour patente, etc.). Vérifier systématiquement.',
  },
  {
    title: 'Présence physique',
    text: 'La plupart des démarches exigent la présence physique du demandeur. Un mandataire n\'est accepté que si une procuration notariée est présentée.',
  },
];

const minorRules = [
  'Toute démarche pour un mineur nécessite la présence et le consentement des parents ou tuteur légal.',
  'L\'autorisation parentale doit être légalisée (mairie ou notaire) pour les démarches importantes.',
  'En cas de divorce : fournir le jugement de divorce et la décision sur la garde.',
  'En cas de décès d\'un parent : fournir l\'acte de décès et désignation du tuteur légal.',
];

const foreignerRules = [
  'Titre de séjour valide exigé pour toutes les démarches administratives.',
  'Permis de travail obligatoire pour toute activité professionnelle rémunérée.',
  'Les ressortissants CEMAC bénéficient d\'une libre circulation simplifiée (accord régional).',
  'Les diplômes étrangers doivent être préalablement équivalés par le Ministère de l\'Enseignement supérieur.',
];

export default function RulesPage() {
  return (
    <main className="mx-auto max-w-4xl px-4 py-10 sm:px-6">
      <header>
        <h1 className="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Règles communes à toutes les démarches</h1>
        <p className="mt-3 text-lg text-slate-600">
          Ces règles s&apos;appliquent à l&apos;ensemble du catalogue. Elles viennent du cahier des charges DemarchesTchad v2.0.
        </p>
      </header>

      <section className="mt-10" aria-labelledby="regles-communes">
        <h2 id="regles-communes" className="text-xl font-semibold text-slate-950">Règles transversales</h2>
        <ol className="mt-4 grid gap-4 sm:grid-cols-2">
          {commonRules.map((rule, index) => (
            <li key={rule.title} className="rounded-2xl border border-slate-200 bg-white p-5">
              <p className="text-sm font-semibold text-slate-500">Règle {index + 1}</p>
              <h3 className="mt-1 font-semibold text-slate-950">{rule.title}</h3>
              <p className="mt-2 text-slate-600">{rule.text}</p>
            </li>
          ))}
        </ol>
      </section>

      <section className="mt-10" aria-labelledby="regles-mineurs">
        <h2 id="regles-mineurs" className="text-xl font-semibold text-slate-950">Règles spécifiques aux mineurs</h2>
        <ul className="mt-4 list-disc space-y-2 ps-6 text-slate-600">
          {minorRules.map((rule) => <li key={rule}>{rule}</li>)}
        </ul>
      </section>

      <section className="mt-10" aria-labelledby="regles-etrangers">
        <h2 id="regles-etrangers" className="text-xl font-semibold text-slate-950">Règles spécifiques aux étrangers au Tchad</h2>
        <ul className="mt-4 list-disc space-y-2 ps-6 text-slate-600">
          {foreignerRules.map((rule) => <li key={rule}>{rule}</li>)}
        </ul>
      </section>
    </main>
  );
}
