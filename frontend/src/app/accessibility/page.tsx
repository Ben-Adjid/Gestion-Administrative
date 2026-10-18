'use client';

import { CheckCircle2, AlertCircle } from 'lucide-react';
import { useLanguage } from '@/hooks/useLanguage';

export default function AccessibilityPage() {
  const { language } = useLanguage();

  const content = {
    fr: {
      title: 'Déclaration d\'Accessibilité RGAA',
      lastUpdate: 'Dernière mise à jour: octobre 2026',
      commitment: 'Engagement en matière d\'accessibilité',
      intro: 'DemarchesTchad s\'engage à rendre son portail accessible à toutes les personnes, y compris celles en situation de handicap, conformément à la loi et aux normes internationales (WCAG 2.1 niveau AA).',
      conformity: 'État de Conformité',
      status: 'Le site se conforme partiellement aux normes WCAG 2.1 niveau AA',
      limitations: 'Limitations Connues',
      report: 'Signaler un Problème d\'Accessibilité',
      reportDesc: 'Si vous rencontrez une barrière d\'accessibilité, veuillez nous le faire savoir. Nous prendrons les mesures correctives rapidement.',
      reportBtn: 'Me signaler un problème d\'accessibilité →',
      standards: 'Normes de Référence',
    },
    en: {
      title: 'RGAA Accessibility Statement',
      lastUpdate: 'Last update: October 2026',
      commitment: 'Accessibility Commitment',
      intro: 'DemarchesTchad is committed to making its portal accessible to all people, including those with disabilities, in accordance with law and international standards (WCAG 2.1 level AA).',
      conformity: 'Conformity Status',
      status: 'The site partially complies with WCAG 2.1 level AA standards',
      limitations: 'Known Limitations',
      report: 'Report an Accessibility Issue',
      reportDesc: 'If you encounter an accessibility barrier, please let us know. We will take corrective action quickly.',
      reportBtn: 'Report an accessibility issue →',
      standards: 'Reference Standards',
    },
    ar: {
      title: 'بيان إمكانية الوصول RGAA',
      lastUpdate: 'آخر تحديث: أكتوبر 2026',
      commitment: 'التزام إمكانية الوصول',
      intro: 'DemarchesTchad ملتزمة بجعل بوابتها في متناول جميع الأشخاص، بما في ذلك الأشخاص ذوو الإعاقة، وفقاً للقانون والمعايير الدولية (WCAG 2.1 المستوى AA).',
      conformity: 'حالة المطابقة',
      status: 'يتوافق الموقع جزئياً مع معايير WCAG 2.1 المستوى AA',
      limitations: 'القيود المعروفة',
      report: 'الإبلاغ عن مشكلة في الوصول',
      reportDesc: 'إذا واجهت حاجزاً في الوصول، يرجى إخبارنا. سنتخذ إجراءات تصحيحية بسرعة.',
      reportBtn: 'الإبلاغ عن مشكلة في الوصول →',
      standards: 'المعايير المرجعية',
    }
  };

  const c = content[language as keyof typeof content] || content.fr;

  return (
    <main className="min-h-screen bg-white">
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <h1 className="text-4xl font-black text-gray-950 mb-4">{c.title}</h1>
        <p className="text-gray-600 mb-8">{c.lastUpdate}</p>

        <section className="mb-12">
          <h2 className="text-2xl font-bold text-gray-950 mb-4">{c.commitment}</h2>
          <p className="text-gray-700 leading-relaxed">{c.intro}</p>
        </section>

        <section className="mb-12 bg-green-50 border-2 border-green-200 rounded-2xl p-8">
          <h2 className="text-2xl font-bold text-green-900 mb-4 flex items-center gap-2">
            <CheckCircle2 className="w-8 h-8" />
            {c.conformity}
          </h2>
          <p className="text-green-900 font-semibold mb-4">{c.status}</p>
          <ul className="space-y-2 text-green-900">
            {language === 'fr' && [
              '✓ Navigation au clavier entièrement fonctionnelle',
              '✓ Contraste de couleurs conforme (ratio 4.5:1 minimum)',
              '✓ Textes alternatifs sur toutes les images',
              '✓ Structure HTML sémantique',
              '✓ Formulaires avec labels associés'
            ].map((item, i) => <li key={i} className="flex items-start gap-2"><span>{item}</span></li>)}
            {language === 'en' && [
              '✓ Fully functional keyboard navigation',
              '✓ Compliant color contrast (4.5:1 minimum ratio)',
              '✓ Alternative text on all images',
              '✓ Semantic HTML structure',
              '✓ Forms with associated labels'
            ].map((item, i) => <li key={i} className="flex items-start gap-2"><span>{item}</span></li>)}
            {language === 'ar' && [
              '✓ ملاحة لوحة المفاتيح الكاملة',
              '✓ تباين الألوان المطابق (نسبة 4.5:1 الحد الأدنى)',
              '✓ نص بديل على جميع الصور',
              '✓ هيكل HTML دلالي',
              '✓ نماذج مع تسميات مرتبطة'
            ].map((item, i) => <li key={i} className="flex items-start gap-2"><span>{item}</span></li>)}
          </ul>
        </section>

        <section className="mb-12 bg-amber-50 border-2 border-amber-200 rounded-2xl p-8">
          <h2 className="text-2xl font-bold text-amber-900 mb-4 flex items-center gap-2">
            <AlertCircle className="w-8 h-8" />
            {c.limitations}
          </h2>
          <ul className="space-y-3 text-amber-900">
            {language === 'fr' && [
              '⚠️ Lecteurs d\'écran: Certains éléments dynamiques peuvent nécessiter des améliorations',
              '⚠️ Zoom: Le contenu peut ne pas restituer parfaitement au-delà de 200% de zoom'
            ].map((item, i) => <li key={i} className="flex items-start gap-3"><span className="text-xl mt-1">⚠️</span><span>{item}</span></li>)}
            {language === 'en' && [
              '⚠️ Screen readers: Some dynamic elements may need improvements',
              '⚠️ Zoom: Content may not render perfectly beyond 200% zoom'
            ].map((item, i) => <li key={i} className="flex items-start gap-3"><span className="text-xl mt-1">⚠️</span><span>{item}</span></li>)}
            {language === 'ar' && [
              '⚠️ قارئات الشاشة: قد تحتاج بعض العناصر الديناميكية إلى تحسينات',
              '⚠️ التكبير: قد لا يتم عرض المحتوى بشكل مثالي بما يتجاوز 200% من الحجم'
            ].map((item, i) => <li key={i} className="flex items-start gap-3"><span className="text-xl mt-1">⚠️</span><span>{item}</span></li>)}
          </ul>
        </section>

        <section className="mb-12 bg-blue-50 rounded-2xl border-2 border-blue-200 p-8">
          <h2 className="text-2xl font-bold text-blue-900 mb-4">{c.report}</h2>
          <p className="text-blue-900 mb-6">{c.reportDesc}</p>
          <a
            href="/contact"
            className="inline-block px-6 py-3 bg-[#002664] hover:bg-[#001c4a] text-white font-bold rounded-xl transition"
          >
            {c.reportBtn}
          </a>
        </section>

        <section>
          <h2 className="text-2xl font-bold text-gray-950 mb-4">{c.standards}</h2>
          <div className="bg-gray-50 rounded-lg p-6 text-gray-700 space-y-2">
            <p><strong>WCAG 2.1</strong> - {language === 'fr' ? 'Web Content Accessibility Guidelines, niveau AA' : language === 'en' ? 'Web Content Accessibility Guidelines, level AA' : 'مبادئ الوصول إلى محتوى الويب، المستوى AA'}</p>
            <p><strong>RGAA 4</strong> - {language === 'fr' ? 'Référentiel Général d\'Amélioration de l\'Accessibilité' : language === 'en' ? 'General Accessibility Improvement Framework' : 'إطار العمل العام لتحسين الوصول'}</p>
            <p><strong>ISO/IEC 40500:2012</strong> - {language === 'fr' ? 'Norme internationale d\'accessibilité web' : language === 'en' ? 'International web accessibility standard' : 'معيار الوصول إلى الويب الدولي'}</p>
          </div>
        </section>
      </div>
    </main>
  );
}
