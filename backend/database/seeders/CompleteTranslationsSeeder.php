<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompleteTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $translations = [
            // Démarches du DatabaseSeeder
            [
                'slug' => 'obtention-du-passeport-biometrique-tchadien',
                'title_en' => 'Obtaining the Biometric Passport',
                'title_ar' => 'الحصول على جواز السفر البيومتري',
                'summary_en' => 'Procedure to apply for or renew the biometric passport of the Republic of Chad.',
                'summary_ar' => 'الإجراء لتقديم أو تجديد جواز السفر البيومتري للجمهورية التشادية.',
                'institution_en' => 'National Agency for Secure Documents (ANATS) / Ministry of Public Safety',
                'institution_ar' => 'الوكالة الوطنية للوثائق الآمنة (ANATS) / وزارة السلامة العامة',
            ],
            [
                'slug' => 'delivrance-carte-nationale-identite-cni-biometrique',
                'title_en' => 'Issuance of National Identity Card (CNI) Biometric',
                'title_ar' => 'إصدار بطاقة الهوية الوطنية البيومترية',
                'summary_en' => 'Procedure for obtaining the biometric national identity card for all Chadian citizens.',
                'summary_ar' => 'الإجراء للحصول على بطاقة الهوية الوطنية البيومترية لجميع المواطنين التشاديين.',
                'institution_en' => 'National Agency for Secure Documents (ANATS)',
                'institution_ar' => 'الوكالة الوطنية للوثائق الآمنة (ANATS)',
            ],
            [
                'slug' => 'creation-entreprise-guichet-unique-anie',
                'title_en' => 'Business Creation at Single Window (ANIE)',
                'title_ar' => 'إنشاء الأعمال في نافذة موحدة (ANIE)',
                'summary_en' => 'Complete formalization of a business (RCCM, NIF, declaration of existence) in 72 hours.',
                'summary_ar' => 'الرسمية الكاملة للعمل (RCCM, NIF، إعلان الوجود) في 72 ساعة.',
                'institution_en' => 'National Agency for Investments and Exports (ANIE)',
                'institution_ar' => 'الوكالة الوطنية للاستثمارات والصادرات (ANIE)',
            ],
            [
                'slug' => 'demande-extrait-casier-judiciaire-bulletin-3',
                'title_en' => 'Request for Criminal Record Extract (Bulletin No. 3)',
                'title_ar' => 'طلب استخراج السجل الجنائي (النموذج رقم 3)',
                'summary_en' => 'Issuance of bulletin no. 3 of the criminal record for administrative file processing.',
                'summary_ar' => 'إصدار النموذج رقم 3 من السجل الجنائي لمعالجة الملفات الإدارية.',
                'institution_en' => 'Ministry of Justice, in charge of Human Rights / Courts of Great Instance',
                'institution_ar' => 'وزارة العدل المسؤولة عن حقوق الإنسان / محاكم الدرجة الأولى',
            ],
            [
                'slug' => 'obtention-permis-de-conduire-categorie-b',
                'title_en' => 'Obtaining a Driving License (Category B)',
                'title_ar' => 'الحصول على رخصة قيادة (الفئة B)',
                'summary_en' => 'Procedure for examination and issuance of driving license for light vehicles.',
                'summary_ar' => 'إجراء الفحص وإصدار رخصة القيادة للمركبات الخفيفة.',
                'institution_en' => 'General Directorate of Road Transport / Ministry of Transport',
                'institution_ar' => 'المديرية العامة للنقل البري / وزارة النقل',
            ],
            // Démarches du DemarcheSeeder
            [
                'slug' => 'demande-de-visa-touristique',
                'title_en' => 'Tourist Visa Application',
                'title_ar' => 'طلب تأشيرة سياحية',
                'institution_en' => 'Ministry of Foreign Affairs',
                'institution_ar' => 'وزارة الخارجية',
            ],
            [
                'slug' => 'renouvellement-passeport',
                'title_en' => 'Passport Renewal',
                'title_ar' => 'تجديد جواز السفر',
                'institution_en' => 'Ministry of Interior',
                'institution_ar' => 'وزارة الداخلية',
            ],
            [
                'slug' => 'immatriculation-vehicule',
                'title_en' => 'Vehicle Registration',
                'title_ar' => 'تسجيل المركبة',
                'institution_en' => 'Traffic Department',
                'institution_ar' => 'إدارة المرور',
            ],
            [
                'slug' => 'certificat-de-residence',
                'title_en' => 'Residency Certificate',
                'title_ar' => 'شهادة الإقامة',
                'institution_en' => 'Municipal Administration',
                'institution_ar' => 'الإدارة البلدية',
            ],
            [
                'slug' => 'levee-de-succession',
                'title_en' => 'Succession Clearance',
                'title_ar' => 'فض الإرث',
                'institution_en' => 'Land Registry',
                'institution_ar' => 'مكتب تسجيل الأراضي',
            ],
        ];

        foreach ($translations as $translation) {
            $demarche = DB::table('demarches')
                ->where('slug', $translation['slug'])
                ->first();

            if ($demarche) {
                $updateData = [
                    'title_en' => $translation['title_en'] ?? null,
                    'title_ar' => $translation['title_ar'] ?? null,
                    'institution_en' => $translation['institution_en'] ?? null,
                    'institution_ar' => $translation['institution_ar'] ?? null,
                ];

                // Ajouter les traductions optionnelles si disponibles
                if (isset($translation['summary_en'])) {
                    $updateData['summary_en'] = $translation['summary_en'];
                }
                if (isset($translation['summary_ar'])) {
                    $updateData['summary_ar'] = $translation['summary_ar'];
                }

                DB::table('demarches')
                    ->where('slug', $translation['slug'])
                    ->update($updateData);
            }
        }

        echo "✅ Traductions complètes ajoutées avec succès !";
    }
}
