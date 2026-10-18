'use client';

import { CheckCircle2, Users, Globe, Zap } from 'lucide-react';
import { useLanguage } from '@/hooks/useLanguage';

export default function AboutPage() {
  const { language, mounted } = useLanguage();

  if (!mounted) return null;

  const content = {
    fr: {
      title: 'A Propos',
      subtitle: 'DemarchesTchad - Cree par Aboubakar Assedick Mahamat adjid Oumar',
      mission: 'Mission',
      missionText: 'DemarchesTchad est un projet personnel pour simplifier l acces aux informations administratives au Tchad.',
      missionText2: 'Nous mettons a disposition des citoyens tchadiens tous les details essentiels sur les demarches publiques.',
      objective: 'Objectif: plus de transparence, moins de confusion, zero surfacturation.',
      values: 'Nos Valeurs',
      citizen: 'Au Service du Citoyen',
      citizenDesc: 'Le portail existe pour vous simplifier la vie administrative.',
      access: 'Acces Pour Tous',
      accessDesc: 'Information gratuite, accessible 24/7, sans barriere.',
      efficiency: 'Efficacite',
      efficiencyDesc: 'Trouve rapidement ce que tu cherches.',
      government: 'Cree par Aboubakar Assedick Mahamat adjid Oumar',
      govText: 'Ce portail est un projet personnel cree par Aboubakar Assedick Mahamat adjid Oumar, ingenieur en logiciel.',
      cta: 'Pret a commencer?',
      ctaText: 'Accedez au catalogue complet de demarches administratives',
      button: 'Acceder au Catalogue',
    },
    en: {
      title: 'About',
      subtitle: 'DemarchesTchad - Created by Aboubakar Assedick Mahamat adjid Oumar',
      mission: 'Mission',
      missionText: 'DemarchesTchad is a personal project to simplify access to administrative information in Chad.',
      missionText2: 'We provide all essential details about public procedures to Chadian citizens.',
      objective: 'Goal: more transparency, less confusion, zero overcharging.',
      values: 'Our Values',
      citizen: 'Serving Citizens',
      citizenDesc: 'The portal exists to simplify your administrative life.',
      access: 'Access For All',
      accessDesc: 'Free information, accessible 24/7, without barriers.',
      efficiency: 'Efficiency',
      efficiencyDesc: 'Find quickly what you are looking for.',
      government: 'Created by Aboubakar Assedick Mahamat adjid Oumar',
      govText: 'This portal is a personal project created by Aboubakar Assedick Mahamat adjid Oumar, Software Engineer.',
      cta: 'Ready to get started?',
      ctaText: 'Access the complete catalog of administrative procedures',
      button: 'Access Catalog',
    },
    ar: {
      title: 'حول',
      subtitle: 'DemarchesTchad - من إنشاء أبوبكر أسيدك محمد',
      mission: 'المهمة',
      missionText: 'DemarchesTchad هو مشروع شخصي لتبسيط الوصول إلى المعلومات الإدارية في تشاد.',
      missionText2: 'نوفر جميع التفاصيل الأساسية حول الإجراءات العامة.',
      objective: 'الهدف: المزيد من الشفافية، أقل ارتباكا، صفر إفراط.',
      values: 'قيمنا',
      citizen: 'خدمة المواطنين',
      citizenDesc: 'البوابة موجودة لتبسيط حياتك الإدارية.',
      access: 'الوصول للجميع',
      accessDesc: 'معلومات مجانية، يمكن الوصول إليها 24/7.',
      efficiency: 'الكفاءة',
      efficiencyDesc: 'ابحث بسرعة عما تبحث عنه.',
      government: 'من إنشاء أبوبكر أسيدك محمد',
      govText: 'هذا المشروع الشخصي من إنشاء أبوبكر أسيدك محمد، مهندس برمجيات.',
      cta: 'هل أنت مستعد؟',
      ctaText: 'الوصول إلى الكتالوج الكامل',
      button: 'الكتالوج',
    }
  };

  const c = content[language as keyof typeof content] || content.fr;

  return (
    <main className="min-h-screen bg-white">
      <div className="bg-gradient-to-r from-blue-700 to-blue-900 text-white py-20">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h1 className="text-4xl sm:text-5xl font-black mb-4">{c.title}</h1>
          <p className="text-xl text-blue-100">{c.subtitle}</p>
        </div>
      </div>

      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div className="grid md:grid-cols-2 gap-12 items-center mb-20">
          <div>
            <h2 className="text-3xl font-black text-gray-950 mb-6">{c.mission}</h2>
            <p className="text-lg text-gray-700 leading-relaxed mb-4">{c.missionText}</p>
            <p className="text-lg text-gray-700 leading-relaxed mb-4">{c.missionText2}</p>
            <p className="text-lg text-gray-700 leading-relaxed"><strong>{c.objective}</strong></p>
          </div>
          <div className="bg-blue-50 rounded-2xl p-8 border-2 border-blue-200">
            <div className="space-y-4">
              <div className="flex items-start gap-4">
                <CheckCircle2 className="w-6 h-6 text-blue-700 shrink-0 mt-1" />
                <div>
                  <h3 className="font-bold text-gray-950">{language === 'fr' ? 'Transparent' : language === 'en' ? 'Transparent' : 'شفاف'}</h3>
                  <p className="text-sm text-gray-600">{language === 'fr' ? 'Tous les couts et delais' : language === 'en' ? 'All costs and delays' : 'جميع التكاليف'}</p>
                </div>
              </div>
              <div className="flex items-start gap-4">
                <CheckCircle2 className="w-6 h-6 text-blue-700 shrink-0 mt-1" />
                <div>
                  <h3 className="font-bold text-gray-950">{language === 'fr' ? 'Gratuit' : language === 'en' ? 'Free' : 'مجاني'}</h3>
                  <p className="text-sm text-gray-600">{language === 'fr' ? 'Sans inscription, sans frais' : language === 'en' ? 'No registration, no fees' : 'بدون تسجيل'}</p>
                </div>
              </div>
              <div className="flex items-start gap-4">
                <CheckCircle2 className="w-6 h-6 text-blue-700 shrink-0 mt-1" />
                <div>
                  <h3 className="font-bold text-gray-950">{language === 'fr' ? 'A jour' : language === 'en' ? 'Up-to-date' : 'محدث'}</h3>
                  <p className="text-sm text-gray-600">{language === 'fr' ? 'Informations verifiees' : language === 'en' ? 'Information verified' : 'معلومات محدثة'}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="mb-20">
          <h2 className="text-3xl font-black text-gray-950 mb-12 text-center">{c.values}</h2>
          <div className="grid md:grid-cols-3 gap-8">
            {[
              { title: c.citizen, desc: c.citizenDesc, icon: Users },
              { title: c.access, desc: c.accessDesc, icon: Globe },
              { title: c.efficiency, desc: c.efficiencyDesc, icon: Zap }
            ].map((val, idx) => {
              const Icon = val.icon;
              return (
                <div key={idx} className="bg-gray-50 rounded-2xl p-8 border-2 border-gray-200 text-center hover:shadow-lg transition">
                  <Icon className="w-12 h-12 text-blue-700 mx-auto mb-4" />
                  <h3 className="text-xl font-bold text-gray-950 mb-2">{val.title}</h3>
                  <p className="text-gray-700">{val.desc}</p>
                </div>
              );
            })}
          </div>
        </div>

        <div className="bg-blue-50 rounded-2xl p-12 border-2 border-blue-200 text-center mb-20">
          <h2 className="text-3xl font-black text-gray-950 mb-4">{c.government}</h2>
          <p className="text-lg text-gray-700">{c.govText}</p>
        </div>

        <div className="bg-gradient-to-r from-blue-700 to-blue-900 text-white rounded-2xl p-12 text-center">
          <h2 className="text-3xl font-black mb-4">{c.cta}</h2>
          <p className="text-xl text-blue-100 mb-8">{c.ctaText}</p>
          <a href="/demarches" className="inline-block px-8 py-4 bg-white hover:bg-blue-50 text-blue-700 font-black rounded-xl transition">
            {c.button}
          </a>
        </div>
      </div>
    </main>
  );
}
