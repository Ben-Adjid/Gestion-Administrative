<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Règles du calendrier fournies par l'équipe (exigences métier, non issues d'une source officielle) :
 *  - acte de mariage : valable tant que le mariage subsiste ; après un divorce légalement prononcé, il n'est plus valable ;
 *  - laissez-passer frontalier : validité d'un an à partir de la délivrance (cahier des charges v2.0, page 8) ;
 *  - casier judiciaire : trois mois calendaires (déjà en place, vérifié ici).
 * Migration idempotente et additive : aucune règle n'est supprimée.
 */
return new class extends Migration
{
    private const MARIAGE_NOTES = [
        'Le mariage n’a pas de date d’expiration : l’acte est valable tant que le mariage subsiste. Après un divorce légalement prononcé, il n’est plus valable : une administration peut demander le jugement de divorce.',
        'A marriage certificate has no expiry date: it stays valid as long as the marriage lasts. After a legally granted divorce, it is no longer valid: an administration may ask for the divorce judgment.',
        'عقد الزواج ليس له تاريخ انتهاء: يبقى صالحًا ما دام الزواج قائمًا. بعد صدور طلاق قانوني لم يعد العقد صالحًا: قد تطلب الإدارة حكم الطلاق.',
    ];

    public function up(): void
    {
        DB::table('document_validity_rules')->where('key', 'acte-de-mariage')->update([
            'notes' => self::MARIAGE_NOTES[0],
            'notes_en' => self::MARIAGE_NOTES[1],
            'notes_ar' => self::MARIAGE_NOTES[2],
            'updated_at' => now(),
        ]);

        // Le casier doit être une exigence de l'équipe (3 mois calendaires), pas une règle à vérifier.
        DB::table('document_validity_rules')->where('key', 'casier-judiciaire')->update([
            'status' => 'business_requirement',
            'validity_months' => 3,
            'validity_start' => 'issue_date',
            'updated_at' => now(),
        ]);

        if (! DB::table('document_validity_rules')->where('key', 'laissez-passer-frontalier')->exists()) {
            DB::table('document_validity_rules')->insert([
                'key' => 'laissez-passer-frontalier',
                'name' => 'Laissez-passer frontalier',
                'name_en' => 'Cross-border pass',
                'name_ar' => 'تصريح عبور الحدود',
                'country_code' => 'TD',
                'category' => 'travel',
                'demarche_id' => DB::table('demarches')->where('slug', 'laissez-passer-frontalier')->value('id'),
                'validity_months' => 12,
                'validity_start' => 'issue_date',
                'renewal' => 'Nouvelle demande auprès de la Direction de l’immigration ou de la préfecture frontalière.',
                'renewal_en' => 'New application to the Immigration Directorate or the border prefecture.',
                'renewal_ar' => 'تقديم طلب جديد لدى مديرية الهجرة أو محافظة الحدود.',
                'notes' => 'Validité d’un an à partir de la délivrance, selon l’exigence de l’équipe (cahier des charges v2.0). Non confirmée par une source officielle.',
                'notes_en' => 'Valid one year from issue, per the team’s requirement (specifications v2.0). Not confirmed by an official source.',
                'notes_ar' => 'صالح لمدة سنة من تاريخ الإصدار، حسب متطلب الفريق (دفتر الشروط v2.0). غير مؤكد بمصدر رسمي.',
                'status' => 'business_requirement',
                'source_label' => 'Cahier des charges v2.0, page 8 (exigence de l’équipe)',
                'source_url' => null,
                'source_date' => null,
                'verified_on' => null,
                'position' => 12,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Retour arrière : le texte du mariage revient à la version précédente, le casier à « à vérifier ».
        DB::table('document_validity_rules')->where('key', 'casier-judiciaire')->update(['status' => 'to_verify', 'validity_months' => null, 'validity_start' => 'unknown', 'updated_at' => now()]);
    }
};
