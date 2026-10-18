'use client';

import Link from '@/components/AppLink';
import { useLanguage } from '@/hooks/useLanguage';

type Section = { title: string; items: string[] };

// Ce texte décrit ce que le code enregistre réellement : à tenir à jour si la collecte change.
const content: Record<'fr' | 'en' | 'ar', { title: string; intro: string; sections: Section[]; updated: string }> = {
  fr: {
    title: 'Confidentialité et conditions d’utilisation',
    intro: 'DemarchesTchad est un guide indépendant et gratuit. La consultation des fiches ne demande aucun compte et aucune donnée personnelle.',
    sections: [
      {
        title: 'Conditions d’utilisation',
        items: [
          'Les informations (pièces, coûts, délais, adresses) sont données à titre indicatif. Vérifiez toujours auprès du service compétent avant de vous déplacer.',
          'DemarchesTchad n’est pas un site du gouvernement et ne traite aucun dossier administratif.',
          'Les signalements doivent être exacts et respectueux. Les messages abusifs sont supprimés.',
        ],
      },
      {
        title: 'Données enregistrées',
        items: [
          'Visite du site : la page consultée et un identifiant de visite chiffré, sans nom ni adresse IP, pour compter les visites. Une recherche sans résultat est gardée (le texte seul) pour savoir quelles fiches écrire.',
          'Signalement : votre message, le type d’erreur et, si vous les donnez, votre nom et votre email. L’adresse IP et le navigateur sont conservés pour lutter contre les abus.',
          'Avis, questions et réponses : le texte publié, le prénom et la ville si vous les donnez, et l’email (jamais publié) pour vous prévenir d’une réponse. L’adresse IP est conservée sous forme chiffrée pour lutter contre les abus.',
          'Assistant WhatsApp : votre message sert uniquement à vous répondre. Votre numéro n’est pas conservé ; seul le texte d’une recherche restée sans résultat est gardé, de façon anonyme, pour écrire les fiches manquantes.',
          'Espace citoyen (facultatif) : nom, email, téléphone si vous le donnez, langue, démarches sauvegardées, notes et dates d’expiration de vos documents.',
          'Votre navigateur garde votre choix de langue, une copie des pages publiques consultées (pour le mode hors connexion) et, si vous êtes connecté, votre session.',
        ],
      },
      {
        title: 'Utilisation',
        items: [
          'Vos données servent uniquement à faire fonctionner le service : répondre à votre signalement, vous envoyer vos rappels d’expiration et les emails liés à votre compte.',
          'Elles ne sont ni vendues ni transmises à des tiers. Aucune publicité n’est affichée.',
          'Seule l’équipe du site y a accès, selon son rôle.',
        ],
      },
      {
        title: 'Vos droits',
        items: [
          'Vous pouvez corriger vos informations depuis « Mon profil ».',
          'Vous pouvez supprimer définitivement votre compte et toutes ses données depuis « Mon profil » → « Supprimer mon compte ».',
          'Pour toute autre demande, utilisez la page Contact.',
        ],
      },
    ],
    updated: 'Dernière mise à jour : octobre 2026',
  },
  en: {
    title: 'Privacy and terms of use',
    intro: 'DemarchesTchad is a free, independent guide. Reading the pages requires no account and no personal data.',
    sections: [
      {
        title: 'Terms of use',
        items: [
          'Information (documents, costs, processing times, addresses) is provided for guidance only. Always check with the competent office before going.',
          'DemarchesTchad is not a government website and does not process any administrative file.',
          'Reports must be accurate and respectful. Abusive messages are deleted.',
        ],
      },
      {
        title: 'Data we record',
        items: [
          'Site visits: the page viewed and an encrypted visit identifier, without name or IP address, to count visits. A search with no result is kept (text only) to know which pages to write.',
          'Reports: your message, the type of error and, if you provide them, your name and email. The IP address and browser are kept to prevent abuse.',
          'Reviews, questions and answers: the published text, your first name and city if provided, and your email (never published) to notify you of an answer. The IP address is kept in encrypted form to prevent abuse.',
          'WhatsApp assistant: your message is used only to reply to you. Your number is not stored; only the text of a search with no result is kept, anonymously, to write the missing pages.',
          'Citizen space (optional): name, email, phone if provided, language, saved procedures, notes and the expiry dates of your documents.',
          'Your browser keeps your language choice, a copy of the public pages you viewed (for offline mode) and, if you are logged in, your session.',
        ],
      },
      {
        title: 'How it is used',
        items: [
          'Your data is used only to run the service: replying to your report, sending your expiry reminders and the emails related to your account.',
          'It is never sold or shared with third parties. No advertising is shown.',
          'Only the site team can access it, according to their role.',
        ],
      },
      {
        title: 'Your rights',
        items: [
          'You can correct your information from “My profile”.',
          'You can permanently delete your account and all its data from “My profile” → “Delete my account”.',
          'For any other request, use the Contact page.',
        ],
      },
    ],
    updated: 'Last updated: October 2026',
  },
  ar: {
    title: 'الخصوصية وشروط الاستخدام',
    intro: 'DemarchesTchad دليل مستقل ومجاني. تصفّح الصفحات لا يتطلب حسابًا ولا أي بيانات شخصية.',
    sections: [
      {
        title: 'شروط الاستخدام',
        items: [
          'المعلومات (الوثائق، التكاليف، الآجال، العناوين) مقدَّمة على سبيل الإرشاد. تحقّق دائمًا لدى المصلحة المختصة قبل التنقّل.',
          'DemarchesTchad ليس موقعًا حكوميًا ولا يعالج أي ملف إداري.',
          'يجب أن تكون البلاغات صحيحة ومحترمة. تُحذف الرسائل المسيئة.',
        ],
      },
      {
        title: 'البيانات المسجَّلة',
        items: [
          'زيارة الموقع: الصفحة التي تمت زيارتها ومعرّف زيارة مشفّر، دون اسم أو عنوان IP، لعدّ الزيارات. يُحفظ البحث الذي لم يُسفر عن نتيجة (النص فقط) لمعرفة الصفحات التي يجب كتابتها.',
          'البلاغ: رسالتك ونوع الخطأ، واسمك وبريدك الإلكتروني إن قدّمتهما. يُحتفظ بعنوان IP والمتصفح لمكافحة الإساءة.',
          'الآراء والأسئلة والأجوبة: النص المنشور، واسمك الأول ومدينتك إن قدّمتهما، وبريدك الإلكتروني (لا يُنشر أبدًا) لإعلامك بالجواب. يُحفظ عنوان IP بصيغة مشفّرة لمكافحة الإساءة.',
          'مساعد واتساب: تُستعمل رسالتك فقط للرد عليك. لا يُحفظ رقمك؛ ويُحتفظ فقط، بشكل مجهول، بنص البحث الذي لم يُسفر عن نتيجة لكتابة الصفحات الناقصة.',
          'فضاء المواطن (اختياري): الاسم، البريد الإلكتروني، الهاتف إن قدّمته، اللغة، الإجراءات المحفوظة، الملاحظات وتواريخ انتهاء وثائقك.',
          'يحتفظ متصفحك باختيار اللغة، وبنسخة من الصفحات العامة التي زرتها (للتصفح دون اتصال)، وبجلستك إن كنت متصلًا.',
        ],
      },
      {
        title: 'الاستعمال',
        items: [
          'تُستعمل بياناتك فقط لتشغيل الخدمة: الرد على بلاغك، وإرسال تذكيرات انتهاء الصلاحية والرسائل المتعلقة بحسابك.',
          'لا تُباع ولا تُنقل إلى أطراف أخرى. لا تُعرض أي إعلانات.',
          'لا يطّلع عليها إلا فريق الموقع، حسب دور كل عضو.',
        ],
      },
      {
        title: 'حقوقك',
        items: [
          'يمكنك تصحيح معلوماتك من «ملفي الشخصي».',
          'يمكنك حذف حسابك وجميع بياناته نهائيًا من «ملفي الشخصي» ← «حذف حسابي».',
          'لأي طلب آخر، استعمل صفحة الاتصال.',
        ],
      },
    ],
    updated: 'آخر تحديث: أكتوبر 2026',
  },
};

export default function PrivacyPage() {
  const { language, t } = useLanguage();
  const c = content[language];

  return (
    <main className="mx-auto max-w-3xl px-4 py-12">
      <h1 className="text-3xl font-bold tracking-tight text-slate-950">{c.title}</h1>
      <p className="mt-3 text-lg text-slate-600">{c.intro}</p>

      <div className="mt-8 space-y-6">
        {c.sections.map((section) => (
          <section key={section.title} className="rounded-2xl border border-slate-200 bg-white p-6">
            <h2 className="text-xl font-bold text-slate-900">{section.title}</h2>
            <ul className="mt-3 list-disc space-y-2 ps-5 text-slate-700">
              {section.items.map((item) => <li key={item}>{item}</li>)}
            </ul>
          </section>
        ))}
      </div>

      <p className="mt-8 text-sm text-slate-500">{c.updated}</p>
      <p className="mt-2 text-sm"><Link href="/contact" className="font-semibold text-blue-800 hover:underline">{t('nav.contact')}</Link></p>
    </main>
  );
}
