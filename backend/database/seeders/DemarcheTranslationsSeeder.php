<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemarcheTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $translations = [
            [
                'slug' => 'demande-de-visa-touristique',
                'title_en' => 'Tourist Visa Application',
                'title_ar' => 'طلب تأشيرة سياحية',
                'summary_en' => 'Official procedure for obtaining a tourist visa',
                'summary_ar' => 'الإجراء الرسمي للحصول على تأشيرة سياحية',
                'description_en' => 'Complete description for tourist visa application. This visa allows you to stay in the country for tourism purposes.',
                'description_ar' => 'الوصف الكامل لطلب تأشيرة سياحية. تسمح هذه التأشيرة بالبقاء في البلد لأغراض سياحية.',
                'cost_en' => '50,000 CFA Francs',
                'cost_ar' => '50,000 فرنك CFA',
                'institution_en' => 'Ministry of Foreign Affairs',
                'institution_ar' => 'وزارة الخارجية',
                'required_documents_en' => ['Passport', 'Application Form', 'Photo', 'Proof of Accommodation', 'Travel Itinerary'],
                'required_documents_ar' => ['جواز السفر', 'نموذج الطلب', 'صورة', 'إثبات الإقامة', 'جدول السفر'],
                'procedure_steps_en' => ['Complete application form', 'Gather required documents', 'Submit application', 'Pay fees', 'Collect visa'],
                'procedure_steps_ar' => ['ملء نموذج الطلب', 'جمع المستندات المطلوبة', 'تقديم الطلب', 'دفع الرسوم', 'استلام التأشيرة'],
                'conditions_en' => ['Valid passport', 'Good health condition', 'Proof of financial means'],
                'conditions_ar' => ['جواز سفر صحيح', 'حالة صحية جيدة', 'إثبات المقدرة المالية'],
            ],
            [
                'slug' => 'renouvellement-passeport',
                'title_en' => 'Passport Renewal',
                'title_ar' => 'تجديد جواز السفر',
                'summary_en' => 'Official procedure for passport renewal',
                'summary_ar' => 'الإجراء الرسمي لتجديد جواز السفر',
                'description_en' => 'Complete description for passport renewal. You can renew your passport when it is about to expire.',
                'description_ar' => 'الوصف الكامل لتجديد جواز السفر. يمكنك تجديد جواز سفرك عندما يكون على وشك انتهاء صلاحيته.',
                'cost_en' => '65,000 CFA Francs',
                'cost_ar' => '65,000 فرنك CFA',
                'institution_en' => 'Ministry of Interior',
                'institution_ar' => 'وزارة الداخلية',
                'required_documents_en' => ['Old Passport', 'Application Form', 'Photos', 'Birth Certificate', 'ID Card'],
                'required_documents_ar' => ['جواز السفر القديم', 'نموذج الطلب', 'صور', 'شهادة الميلاد', 'بطاقة الهوية'],
                'procedure_steps_en' => ['Apply at civil registry', 'Submit documents', 'Pay fee', 'Wait for processing', 'Collect new passport'],
                'procedure_steps_ar' => ['تقديم الطلب في السجل المدني', 'تقديم المستندات', 'دفع الرسوم', 'الانتظار للمعالجة', 'استلام جواز السفر الجديد'],
                'conditions_en' => ['Current passport expired or about to expire', 'No legal restrictions'],
                'conditions_ar' => ['جواز السفر الحالي منتهي الصلاحية أو على وشك الانتهاء', 'لا توجد قيود قانونية'],
            ],
            [
                'slug' => 'immatriculation-vehicule',
                'title_en' => 'Vehicle Registration',
                'title_ar' => 'تسجيل المركبة',
                'summary_en' => 'Official procedure for vehicle registration',
                'summary_ar' => 'الإجراء الرسمي لتسجيل المركبة',
                'description_en' => 'Complete description for vehicle registration. Register your newly purchased vehicle with authorities.',
                'description_ar' => 'الوصف الكامل لتسجيل المركبة. قم بتسجيل مركبتك المشتراة حديثاً مع السلطات.',
                'cost_en' => '35,000 CFA Francs',
                'cost_ar' => '35,000 فرنك CFA',
                'institution_en' => 'Traffic Department',
                'institution_ar' => 'إدارة المرور',
                'required_documents_en' => ['Vehicle Inspection Report', 'Purchase Invoice', 'ID Card', 'Proof of Ownership', 'Insurance Certificate'],
                'required_documents_ar' => ['تقرير فحص المركبة', 'فاتورة الشراء', 'بطاقة الهوية', 'إثبات الملكية', 'شهادة التأمين'],
                'procedure_steps_en' => ['Vehicle inspection', 'Prepare documents', 'Submit application', 'Pay fees', 'Receive registration plate'],
                'procedure_steps_ar' => ['فحص المركبة', 'تحضير المستندات', 'تقديم الطلب', 'دفع الرسوم', 'استلام لوحة التسجيل'],
                'conditions_en' => ['Vehicle is roadworthy', 'Valid proof of ownership'],
                'conditions_ar' => ['المركبة صالحة للقيادة', 'إثبات ملكية صحيح'],
            ],
            [
                'slug' => 'certificat-de-residence',
                'title_en' => 'Residency Certificate',
                'title_ar' => 'شهادة الإقامة',
                'summary_en' => 'Official procedure for residency certificate',
                'summary_ar' => 'الإجراء الرسمي لشهادة الإقامة',
                'description_en' => 'Complete description for residency certificate. Prove your residence at a particular address.',
                'description_ar' => 'الوصف الكامل لشهادة الإقامة. أثبت إقامتك في عنوان معين.',
                'cost_en' => '5,000 CFA Francs',
                'cost_ar' => '5,000 فرنك CFA',
                'institution_en' => 'Municipal Administration',
                'institution_ar' => 'الإدارة البلدية',
                'required_documents_en' => ['ID Card', 'Proof of Residence', 'Utility Bills'],
                'required_documents_ar' => ['بطاقة الهوية', 'إثبات الإقامة', 'فواتير المرافق'],
                'procedure_steps_en' => ['Prepare documents', 'Submit at town hall', 'Pay fee', 'Receive certificate'],
                'procedure_steps_ar' => ['تحضير المستندات', 'التقديم في بلدية المدينة', 'دفع الرسوم', 'استلام الشهادة'],
                'conditions_en' => ['Proof of residence for at least 3 months'],
                'conditions_ar' => ['إثبات الإقامة لمدة 3 أشهر على الأقل'],
            ],
            [
                'slug' => 'levee-de-succession',
                'title_en' => 'Succession Clearance',
                'title_ar' => 'فض الإرث',
                'summary_en' => 'Official procedure for inheritance succession',
                'summary_ar' => 'الإجراء الرسمي لوراثة العقارات',
                'description_en' => 'Complete description for succession clearance. Handle inheritance matters officially.',
                'description_ar' => 'الوصف الكامل لفض الإرث. التعامل مع مسائل الوراثة بشكل رسمي.',
                'cost_en' => '100,000 CFA Francs',
                'cost_ar' => '100,000 فرنك CFA',
                'institution_en' => 'Land Registry',
                'institution_ar' => 'مكتب تسجيل الأراضي',
                'required_documents_en' => ['Death Certificate', 'Will', 'Birth Certificates', 'Marriage Certificate', 'ID Cards of Heirs'],
                'required_documents_ar' => ['شهادة الوفاة', 'الوصية', 'شهادات الميلاد', 'شهادة الزواج', 'بطاقات هوية الورثة'],
                'procedure_steps_en' => ['File death notice', 'Submit documents', 'Property appraisal', 'Pay taxes and fees', 'Receive clearance'],
                'procedure_steps_ar' => ['تقديم إخطار الوفاة', 'تقديم المستندات', 'تقييم الممتلكات', 'دفع الضرائب والرسوم', 'استلام التصريح'],
                'conditions_en' => ['Deceased person has property', 'Valid documentation from heirs'],
                'conditions_ar' => ['الشخص المتوفى لديه ممتلكات', 'وثائق صحيحة من الورثة'],
            ],
            [
                'slug' => 'inscription-universite',
                'title_en' => 'University Registration',
                'title_ar' => 'التسجيل الجامعي',
                'summary_en' => 'Official procedure for university registration',
                'summary_ar' => 'الإجراء الرسمي للتسجيل الجامعي',
                'description_en' => 'Complete description for university registration. Enroll in a university program.',
                'description_ar' => 'الوصف الكامل للتسجيل الجامعي. التحق ببرنامج جامعي.',
                'cost_en' => 'Free',
                'cost_ar' => 'مجاني',
                'institution_en' => 'University of N\'Djamena',
                'institution_ar' => 'جامعة نجامينا',
                'required_documents_en' => ['High School Diploma', 'Birth Certificate', 'ID Card', 'Transcripts'],
                'required_documents_ar' => ['شهادة الثانوية العامة', 'شهادة الميلاد', 'بطاقة الهوية', 'النسخ المرفقة'],
                'procedure_steps_en' => ['Apply online', 'Submit documents', 'Pass entrance exam', 'Attend orientation', 'Begin studies'],
                'procedure_steps_ar' => ['تقديم الطلب عبر الإنترنت', 'تقديم المستندات', 'اجتياز امتحان القبول', 'حضور حفل الاستقبال', 'بدء الدراسة'],
                'conditions_en' => ['High school graduate', 'Meets program requirements'],
                'conditions_ar' => ['خريج الثانوية العامة', 'يفي بمتطلبات البرنامج'],
            ],
        ];

        foreach ($translations as $translation) {
            DB::table('demarches')
                ->where('slug', $translation['slug'])
                ->update([
                    'title_en' => $translation['title_en'],
                    'title_ar' => $translation['title_ar'],
                    'summary_en' => $translation['summary_en'],
                    'summary_ar' => $translation['summary_ar'],
                    'description_en' => $translation['description_en'],
                    'description_ar' => $translation['description_ar'],
                    'cost_en' => $translation['cost_en'],
                    'cost_ar' => $translation['cost_ar'],
                    'institution_en' => $translation['institution_en'],
                    'institution_ar' => $translation['institution_ar'],
                    'required_documents_en' => json_encode($translation['required_documents_en']),
                    'required_documents_ar' => json_encode($translation['required_documents_ar']),
                    'procedure_steps_en' => json_encode($translation['procedure_steps_en']),
                    'procedure_steps_ar' => json_encode($translation['procedure_steps_ar']),
                    'conditions_en' => json_encode($translation['conditions_en']),
                    'conditions_ar' => json_encode($translation['conditions_ar']),
                ]);
        }

        echo "✅ Traductions ajoutées avec succès pour " . count($translations) . " démarches !";
    }
}
