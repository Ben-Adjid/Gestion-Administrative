'use client';

import { useLanguage } from '@/hooks/useLanguage';

export default function CreditsPage() {
  const { language } = useLanguage();

  const content = {
    fr: {
      title: 'Crédits & Mentions',
      subtitle: 'Un projet personnel au service des citoyens tchadiens',
      creator: 'Créateur & Développeur Principal',
      creatorName: 'Aboubakar Assedick Mahamat Adjid Oumar',
      creatorTitle: 'Ingénieur en Logiciel',
      creatorBio: 'Jeune ingénieur en génie logiciel passionné par la résolution de problèmes administratifs et la création de solutions numériques accessibles.',
      mission: 'Mission du Projet',
      missionText: 'DemarchesTchad est un projet personnel créé dans le but de simplifier l\'accès à l\'information administrative pour les citoyens tchadiens. Le projet vise à lutter contre la corruption administrative et la surfacturation en mettant à disposition les tarifs officiels, les délais réels, et les pièces requises pour chaque démarche.',
      tech: 'Technologies Utilisées',
      frontend: 'Frontend',
      backend: 'Backend',
      database: 'Base de Données',
      features: 'Fonctionnalités Principales',
      feature1: 'Catalogue de 80 démarches administratives avec détails complets',
      feature2: 'Système d\'authentification sécurisé pour les administrateurs',
      feature3: 'Gestion complète des démarches (création, modification, suppression)',
      feature4: 'Système de signalement pour les anomalies',
      feature5: 'Favoris utilisateur avec notes et évaluations',
      feature6: 'Partage social sur 6 plateformes (WhatsApp, Facebook, Twitter, LinkedIn, Telegram, Email)',
      feature7: 'Dashboard analytics avec statistiques',
      feature8: 'Support multilingue (Français, Anglais, Arabe)',
      opensource: 'Code Source',
      opensourceText: 'Ce projet est créé pour servir la communauté tchadienne. Pour toute question, suggestion ou contribution, n\'hésitez pas à contacter le créateur.',
      thankyou: 'Merci de votre confiance',
      thankYouText: 'DemarchesTchad existe pour VOUS - les citoyens tchadiens. Votre feedback est précieux pour l\'amélioration continue du portail.',
    },
    en: {
      title: 'Credits & Mentions',
      subtitle: 'A personal project serving Chadian citizens',
      creator: 'Creator & Lead Developer',
      creatorName: 'Aboubakar Assedick Mahamat Adjid Oumar',
      creatorTitle: 'Software Engineer',
      creatorBio: 'Young software engineer passionate about solving administrative problems and creating accessible digital solutions.',
      mission: 'Project Mission',
      missionText: 'DemarchesTchad is a personal project created to simplify access to administrative information for Chadian citizens. The project aims to combat administrative corruption and overcharging by providing official prices, realistic delays, and required documents for each procedure.',
      tech: 'Technologies Used',
      frontend: 'Frontend',
      backend: 'Backend',
      database: 'Database',
      features: 'Key Features',
      feature1: 'Catalog of 80 administrative procedures with complete details',
      feature2: 'Secure authentication system for administrators',
      feature3: 'Complete procedure management (create, edit, delete)',
      feature4: 'Anomaly reporting system',
      feature5: 'User favorites with notes and ratings',
      feature6: 'Social sharing on 6 platforms (WhatsApp, Facebook, Twitter, LinkedIn, Telegram, Email)',
      feature7: 'Analytics dashboard with statistics',
      feature8: 'Multi-language support (French, English, Arabic)',
      opensource: 'Source Code',
      opensourceText: 'This project is created to serve the Chadian community. For any questions, suggestions or contributions, please contact the creator.',
      thankyou: 'Thank you for your trust',
      thankYouText: 'DemarchesTchad exists for YOU - the Chadian citizens. Your feedback is valuable for the continuous improvement of the portal.',
    },
    ar: {
      title: 'الأرصدة والإشارات',
      subtitle: 'مشروع شخصي في خدمة المواطنين التشاديين',
      creator: 'المنشئ والمطور الرئيسي',
      creatorName: 'أبوبكر أسيدك محمد أجيد عمر',
      creatorTitle: 'مهندس برمجيات',
      creatorBio: 'مهندس برمجيات شاب متحمس لحل المشاكل الإدارية وإنشاء حلول رقمية سهلة الوصول.',
      mission: 'مهمة المشروع',
      missionText: 'DemarchesTchad هو مشروع شخصي تم إنشاؤه لتبسيط الوصول إلى المعلومات الإدارية للمواطنين التشاديين. يهدف المشروع إلى مكافحة الفساد الإداري والإفراط في الفرض من خلال توفير الأسعار الرسمية والتأخيرات الواقعية والمستندات المطلوبة لكل إجراء.',
      tech: 'التقنيات المستخدمة',
      frontend: 'الواجهة الأمامية',
      backend: 'الواجهة الخلفية',
      database: 'قاعدة البيانات',
      features: 'الميزات الرئيسية',
      feature1: 'فهرس يضم 80 إجراءً إدارياً مع تفاصيل كاملة',
      feature2: 'نظام مصادقة آمن للمسؤولين',
      feature3: 'إدارة كاملة للإجراءات (الإنشاء والتعديل والحذف)',
      feature4: 'نظام الإبلاغ عن الشذوذ',
      feature5: 'المفضلات مع الملاحظات والتقييمات',
      feature6: 'المشاركة الاجتماعية على 6 منصات (WhatsApp, Facebook, Twitter, LinkedIn, Telegram, Email)',
      feature7: 'لوحة معلومات التحليلات مع الإحصائيات',
      feature8: 'دعم متعدد اللغات (الفرنسية والإنجليزية والعربية)',
      opensource: 'كود المصدر',
      opensourceText: 'تم إنشاء هذا المشروع لخدمة المجتمع التشادي. لأي أسئلة أو اقتراحات أو مساهمات، يرجى التواصل مع المنشئ.',
      thankyou: 'شكرا لثقتك',
      thankYouText: 'DemarchesTchad موجود من أجلك - المواطنين التشاديين. ملاحظاتك ذات قيمة لتحسين البوابة بشكل مستمر.',
    }
  };

  const c = content[language as keyof typeof content] || content.fr;
  const L = (fr: string, en: string, ar: string) => (language === 'fr' ? fr : language === 'en' ? en : ar);
  // Liens réseaux : affichés seulement si l'adresse est renseignée dans la configuration du site.
  const socials = [
    { label: 'LinkedIn', href: process.env.NEXT_PUBLIC_LINKEDIN_URL },
    { label: 'Facebook', href: process.env.NEXT_PUBLIC_FACEBOOK_URL },
  ].filter((item): item is { label: string; href: string } => Boolean(item.href));

  return (
    <main className="min-h-screen bg-white">
      {/* Header */}
      <div className="bg-gradient-to-r from-[#002664] to-[#003d99] text-white py-20">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h1 className="text-4xl sm:text-5xl font-black mb-4">{c.title}</h1>
          <p className="text-xl text-blue-100">{c.subtitle}</p>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

        {/* Creator Section */}
        <div className="bg-blue-50 rounded-2xl p-12 border-2 border-blue-200 mb-12 text-center">
          <h2 className="text-3xl font-black text-gray-950 mb-4">{c.creator}</h2>
          <div className="text-4xl mb-4">👨‍💻</div>
          <p className="text-2xl font-bold text-[#002664] mb-2">{c.creatorName}</p>
          <p className="text-lg text-gray-700 mb-4 italic">{c.creatorTitle}</p>
          <p className="text-gray-700 text-lg leading-relaxed">{c.creatorBio}</p>
          <ul className="mt-4 flex flex-wrap gap-2" aria-label={L('Compétences', 'Skills', 'المهارات')}>
            {[L('Analyse de données', 'Data analysis', 'تحليل البيانات'), L('Administration réseau', 'Network administration', 'إدارة الشبكات'), L('Développeur', 'Developer', 'مطوّر')].map((skill) => (
              <li key={skill} className="rounded-full bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-900">{skill}</li>
            ))}
          </ul>
          {socials.length > 0 && (
            <ul className="mt-4 flex flex-wrap gap-3">
              {socials.map((item) => (
                <li key={item.label}><a href={item.href} target="_blank" rel="noreferrer noopener" className="font-semibold text-blue-800 underline">{item.label}</a></li>
              ))}
            </ul>
          )}
        </div>

        {/* Mission */}
        <div className="mb-12">
          <h2 className="text-3xl font-black text-gray-950 mb-6">{c.mission}</h2>
          <p className="text-lg text-gray-700 leading-relaxed">{c.missionText}</p>
        </div>

        {/* Features */}
        <div className="mb-12">
          <h2 className="text-3xl font-black text-gray-950 mb-6">{c.features}</h2>
          <div className="grid md:grid-cols-2 gap-4">
            {[c.feature1, c.feature2, c.feature3, c.feature4, c.feature5, c.feature6, c.feature7, c.feature8].map((feature, i) => (
              <div key={i} className="flex gap-3 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <span className="text-2xl">✓</span>
                <p className="text-gray-700">{feature}</p>
              </div>
            ))}
          </div>
        </div>

        {/* Thank You */}
        <div className="bg-gradient-to-r from-[#002664] to-[#003d99] text-white rounded-2xl p-12 text-center mb-12">
          <h2 className="text-3xl font-black mb-4">{c.thankyou}</h2>
          <p className="text-xl text-blue-100">{c.thankYouText}</p>
        </div>
      </div>
    </main>
  );
}
