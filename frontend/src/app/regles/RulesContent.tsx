'use client';

import { useLanguage } from '@/hooks/useLanguage';

type Lang = 'fr' | 'en' | 'ar';

interface Rule { title: string; text: string }

/** Règles du cahier des charges v2.0, dans les trois langues. Les traductions anglaise et arabe sont à relire par un locuteur. */
const CONTENT: Record<Lang, {
  title: string; intro: string; common: string; minors: string; foreigners: string; rule: string;
  rules: Rule[]; minorRules: string[]; foreignerRules: string[];
}> = {
  fr: {
    title: 'Règles communes à toutes les démarches',
    intro: 'Ces règles s’appliquent à l’ensemble du catalogue. Elles viennent du cahier des charges DemarchesTchad v2.0.',
    common: 'Règles transversales', minors: 'Règles spécifiques aux mineurs', foreigners: 'Règles spécifiques aux étrangers au Tchad', rule: 'Règle',
    rules: [
      { title: 'Validité des pièces d’identité', text: 'Toute CNI ou passeport présenté doit être en cours de validité. Une pièce expirée est systématiquement refusée, même si le demandeur peut prouver son identité autrement.' },
      { title: 'Photos d’identité', text: 'Les photos doivent être récentes (moins de 6 mois), fond blanc, tête nue, face visible. Les photos floues, trop sombres ou avec lunettes de soleil sont refusées.' },
      { title: 'Originaux exigés', text: 'Toutes les administrations exigent les originaux + copies. Ne jamais se présenter avec des photocopies seules. Conserver les originaux après présentation.' },
      { title: 'Dossier incomplet = refus', text: 'Un dossier incomplet est systématiquement refusé sans exception. Toutes les pièces listées doivent être présentes lors du dépôt.' },
      { title: 'Timbres fiscaux', text: 'Le timbre fiscal doit être acheté AVANT le dépôt du dossier (Trésorerie nationale ou points de vente agréés). Un dossier sans timbre fiscal est irrecevable.' },
      { title: 'Récépissé de dépôt', text: 'Toujours exiger et conserver le récépissé lors du dépôt. Sans récépissé, il est impossible de suivre le dossier ou de réclamer en cas de perte.' },
      { title: 'Délais légaux', text: 'Certains délais sont impératifs et leur dépassement entraîne des procédures plus lourdes (jugement supplétif pour naissance tardive, majoration pour patente, etc.). Vérifier systématiquement.' },
      { title: 'Présence physique', text: 'La plupart des démarches exigent la présence physique du demandeur. Un mandataire n’est accepté que si une procuration notariée est présentée.' },
    ],
    minorRules: [
      'Toute démarche pour un mineur nécessite la présence et le consentement des parents ou tuteur légal.',
      'L’autorisation parentale doit être légalisée (mairie ou notaire) pour les démarches importantes.',
      'En cas de divorce : fournir le jugement de divorce et la décision sur la garde.',
      'En cas de décès d’un parent : fournir l’acte de décès et désignation du tuteur légal.',
    ],
    foreignerRules: [
      'Titre de séjour valide exigé pour toutes les démarches administratives.',
      'Permis de travail obligatoire pour toute activité professionnelle rémunérée.',
      'Les ressortissants CEMAC bénéficient d’une libre circulation simplifiée (accord régional).',
      'Les diplômes étrangers doivent être préalablement équivalés par le Ministère de l’Enseignement supérieur.',
    ],
  },
  en: {
    title: 'Rules common to all procedures',
    intro: 'These rules apply to the whole catalogue. They come from the DemarchesTchad specifications v2.0.',
    common: 'Cross-cutting rules', minors: 'Specific rules for minors', foreigners: 'Specific rules for foreigners in Chad', rule: 'Rule',
    rules: [
      { title: 'Validity of identity documents', text: 'Any national ID card or passport presented must be valid. An expired document is always refused, even if the applicant can prove their identity otherwise.' },
      { title: 'Identity photos', text: 'Photos must be recent (less than 6 months old), on a white background, bare-headed, face visible. Blurred, too dark or sunglasses photos are refused.' },
      { title: 'Originals required', text: 'All administrations require originals plus copies. Never come with photocopies alone. Keep the originals after presentation.' },
      { title: 'Incomplete file = refusal', text: 'An incomplete file is always refused without exception. All listed documents must be present when filing.' },
      { title: 'Revenue stamps', text: 'The revenue stamp must be bought BEFORE filing (National Treasury or approved outlets). A file without a revenue stamp is inadmissible.' },
      { title: 'Filing receipt', text: 'Always ask for and keep the receipt when filing. Without it, it is impossible to track the file or to claim in case of loss.' },
      { title: 'Legal deadlines', text: 'Some deadlines are mandatory, and missing them leads to heavier procedures (supplementary judgment for a late birth declaration, surcharge for the business licence, etc.). Always check.' },
      { title: 'Physical presence', text: 'Most procedures require the applicant to be present in person. A proxy is accepted only if a notarised power of attorney is presented.' },
    ],
    minorRules: [
      'Any procedure for a minor requires the presence and consent of the parents or legal guardian.',
      'The parental authorisation must be legalised (town hall or notary) for important procedures.',
      'In case of divorce: provide the divorce judgment and the custody decision.',
      'In case of a parent’s death: provide the death certificate and the appointment of a legal guardian.',
    ],
    foreignerRules: [
      'A valid residence permit is required for all administrative procedures.',
      'A work permit is required for any paid professional activity.',
      'CEMAC nationals benefit from simplified free movement (regional agreement).',
      'Foreign diplomas must first be recognised by the Ministry of Higher Education.',
    ],
  },
  ar: {
    title: 'القواعد المشتركة لجميع الإجراءات',
    intro: 'تنطبق هذه القواعد على الفهرس كله، وهي مأخوذة من دفتر شروط DemarchesTchad v2.0.',
    common: 'القواعد العامة', minors: 'قواعد خاصة بالقاصرين', foreigners: 'قواعد خاصة بالأجانب في تشاد', rule: 'القاعدة',
    rules: [
      { title: 'صلاحية وثائق الهوية', text: 'يجب أن تكون بطاقة التعريف الوطنية أو جواز السفر المقدَّمة سارية المفعول. تُرفض الوثيقة المنتهية دائمًا، حتى لو أمكن لصاحب الطلب إثبات هويته بوسيلة أخرى.' },
      { title: 'صور الهوية', text: 'يجب أن تكون الصور حديثة (أقل من 6 أشهر)، على خلفية بيضاء، بدون غطاء للرأس، والوجه ظاهر. تُرفض الصور الضبابية أو المعتمة جدًا أو التي يرتدي صاحبها نظارات شمسية.' },
      { title: 'الأصول مطلوبة', text: 'تطلب جميع الإدارات الأصول مع النسخ. لا تحضر أبدًا بالنسخ المصوّرة وحدها. احتفظ بالأصول بعد تقديمها.' },
      { title: 'الملف غير المكتمل = رفض', text: 'يُرفض الملف غير المكتمل دائمًا ودون استثناء. يجب توفر جميع الوثائق المذكورة عند الإيداع.' },
      { title: 'الطوابع الجبائية', text: 'يجب شراء الطابع الجبائي قبل إيداع الملف (خزينة الدولة أو نقاط البيع المعتمدة). الملف الذي لا يحمل طابعًا جبائيًا غير مقبول.' },
      { title: 'وصل الإيداع', text: 'اطلب دائمًا وصل الإيداع واحتفظ به. بدونه يصعب متابعة الملف أو المطالبة به عند الضياع.' },
      { title: 'الآجال القانونية', text: 'بعض الآجال إلزامية، وتجاوزها يؤدي إلى إجراءات أثقل (حكم تكميلي عند التصريح المتأخر بالولادة، أو زيادة على رخصة المهنة، وغيرها). تحقّق منها دائمًا.' },
      { title: 'الحضور الشخصي', text: 'تتطلب أغلب الإجراءات حضور صاحب الطلب شخصيًا. لا يُقبل الوكيل إلا إذا قُدِّمت وكالة موثّقة لدى موثّق.' },
    ],
    minorRules: [
      'يتطلب أي إجراء يخص قاصرًا حضور ورضا الوالدين أو الوصي القانوني.',
      'يجب تصديق الإذن الأبوي (البلدية أو الموثّق) في الإجراءات المهمة.',
      'في حالة الطلاق: تقديم حكم الطلاق وقرار الحضانة.',
      'في حالة وفاة أحد الوالدين: تقديم عقد الوفاة وتعيين الوصي القانوني.',
    ],
    foreignerRules: [
      'يُشترط تصريح إقامة ساري المفعول لجميع الإجراءات الإدارية.',
      'يُشترط تصريح العمل لأي نشاط مهني مأجور.',
      'يستفيد رعايا الجماعة الاقتصادية لوسط أفريقيا (CEMAC) من حرية تنقل مبسّطة بموجب اتفاق إقليمي.',
      'يجب أن تُعادَل الشهادات الأجنبية مسبقًا من وزارة التعليم العالي.',
    ],
  },
};

/** Affiche les règles dans la langue choisie par le visiteur. */
export default function RulesContent() {
  const { language } = useLanguage();
  const c = CONTENT[(language as Lang)] ?? CONTENT.fr;

  return (
    <main className="mx-auto max-w-4xl px-4 py-10 sm:px-6" dir={language === 'ar' ? 'rtl' : 'ltr'}>
      <header>
        <h1 className="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{c.title}</h1>
        <p className="mt-3 text-lg text-slate-600">{c.intro}</p>
      </header>

      <section className="mt-10" aria-labelledby="regles-communes">
        <h2 id="regles-communes" className="text-xl font-semibold text-slate-950">{c.common}</h2>
        <ol className="mt-4 grid gap-4 sm:grid-cols-2">
          {c.rules.map((rule, index) => (
            <li key={rule.title} className="rounded-2xl border border-slate-200 bg-white p-5">
              <p className="text-sm font-semibold text-slate-500">{c.rule} {index + 1}</p>
              <h3 className="mt-1 font-semibold text-slate-950">{rule.title}</h3>
              <p className="mt-2 text-slate-600">{rule.text}</p>
            </li>
          ))}
        </ol>
      </section>

      <section className="mt-10" aria-labelledby="regles-mineurs">
        <h2 id="regles-mineurs" className="text-xl font-semibold text-slate-950">{c.minors}</h2>
        <ul className="mt-4 list-disc space-y-2 ps-6 text-slate-600">
          {c.minorRules.map((rule) => <li key={rule}>{rule}</li>)}
        </ul>
      </section>

      <section className="mt-10" aria-labelledby="regles-etrangers">
        <h2 id="regles-etrangers" className="text-xl font-semibold text-slate-950">{c.foreigners}</h2>
        <ul className="mt-4 list-disc space-y-2 ps-6 text-slate-600">
          {c.foreignerRules.map((rule) => <li key={rule}>{rule}</li>)}
        </ul>
      </section>
    </main>
  );
}
