<?php

namespace App\Support;

/**
 * Libellés anglais et arabes des provinces du Tchad (la valeur stockée reste le nom français).
 * Un nom inconnu est renvoyé tel quel.
 */
class Provinces
{
    private const LABELS = [
        "Ville de N'Djaména" => ['en' => "N'Djamena City", 'ar' => 'مدينة إنجامينا'],
        'Logone Occidental' => ['en' => 'Western Logone', 'ar' => 'لوغون الغربي'],
        'Moyen-Chari' => ['en' => 'Middle Chari', 'ar' => 'شاري الأوسط'],
        'Ouaddaï' => ['en' => 'Ouaddai', 'ar' => 'وداي'],
        'Batha' => ['en' => 'Batha', 'ar' => 'البطحاء'],
        'Tibesti' => ['en' => 'Tibesti', 'ar' => 'تبستي'],
        'Wadi Fira' => ['en' => 'Wadi Fira', 'ar' => 'وادي فيرا'],
        'Lac' => ['en' => 'Lac', 'ar' => 'بحيرة'],
        'Mayo-Kebbi Est' => ['en' => 'Eastern Mayo-Kebbi', 'ar' => 'ماي كبي الشرقي'],
        'Logone Oriental' => ['en' => 'Eastern Logone', 'ar' => 'لوغون الشرقي'],
        'Borkou' => ['en' => 'Borkou', 'ar' => 'بوركو'],
        'Ennedi Ouest' => ['en' => 'Western Ennedi', 'ar' => 'إنيدي الغربي'],
        'Ennedi Est' => ['en' => 'Eastern Ennedi', 'ar' => 'إنيدي الشرقي'],
        'Sila' => ['en' => 'Sila', 'ar' => 'سيلا'],
        'Mandoul' => ['en' => 'Mandoul', 'ar' => 'ماندول'],
        'Tandjilé' => ['en' => 'Tandjile', 'ar' => 'تاندجيلي'],
        'Kanem' => ['en' => 'Kanem', 'ar' => 'كانم'],
        'Hadjer-Lamis' => ['en' => 'Hadjer-Lamis', 'ar' => 'حجر لامس'],
        'Guéra' => ['en' => 'Guera', 'ar' => 'قيرا'],
        'Barh El Gazel' => ['en' => 'Bahr el Gazel', 'ar' => 'بحر الغزال'],
        'Mayo-Kebbi Ouest' => ['en' => 'Western Mayo-Kebbi', 'ar' => 'ماي كبي الغربي'],
        'Salamat' => ['en' => 'Salamat', 'ar' => 'سلامات'],
        'Chari-Baguirmi' => ['en' => 'Chari-Baguirmi', 'ar' => 'شاري باقرمي'],
    ];

    /** Libellé de la province dans la langue demandée (fr : valeur d'origine). */
    public static function label(?string $province, string $language): ?string
    {
        if ($province === null || $province === '') {
            return $province;
        }
        if ($language === 'fr' || !isset(self::LABELS[$province][$language])) {
            return $province;
        }

        return self::LABELS[$province][$language];
    }
}
