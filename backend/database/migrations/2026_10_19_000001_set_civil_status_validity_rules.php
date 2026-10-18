<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Actes d'état civil : ils n'ont pas de date d'expiration en soi.
 *  - acte de naissance et acte de décès : validité permanente (status « permanent ») ;
 *  - acte de mariage : validité liée au statut matrimonial (status « status_based ») : il reste valable tant que
 *    le mariage subsiste ; un divorce légalement prononcé doit être porté sur l'acte, sans le faire disparaître.
 * Aucune durée n'est inventée. Migration additive : ne supprime aucune règle ni aucune donnée, ne touche que ces trois clés.
 */
return new class extends Migration
{
    private const RULES = [
        'acte-de-naissance' => [
            'status' => 'permanent',
            'notes' => [
                'Un acte de naissance n’a pas de date d’expiration : sa validité est permanente. Certaines administrations demandent cependant une copie ou un extrait récent : l’ancienneté acceptée dépend de l’organisme qui le demande.',
                'A birth certificate has no expiry date: its validity is permanent. Some offices still ask for a recent copy or extract: the age they accept depends on the office asking for it.',
                'شهادة الميلاد ليس لها تاريخ انتهاء: صلاحيتها دائمة. لكن بعض الإدارات تطلب نسخة أو مستخرجًا حديثًا، وتختلف المدة المقبولة حسب الجهة التي تطلبه.',
            ],
        ],
        'acte-de-deces' => [
            'status' => 'permanent',
            'notes' => [
                'Un acte de décès n’a pas de date d’expiration : sa validité est permanente. Certaines administrations demandent une copie ou un extrait récent : l’ancienneté acceptée dépend de l’organisme qui le demande.',
                'A death certificate has no expiry date: its validity is permanent. Some offices ask for a recent copy or extract: the age they accept depends on the office asking for it.',
                'شهادة الوفاة ليس لها تاريخ انتهاء: صلاحيتها دائمة. تطلب بعض الإدارات نسخة أو مستخرجًا حديثًا، وتختلف المدة المقبولة حسب الجهة التي تطلبه.',
            ],
        ],
        'acte-de-mariage' => [
            'status' => 'status_based',
            'notes' => [
                'Le mariage n’a pas de date d’expiration : l’acte reste valable tant que le mariage subsiste. En cas de divorce légalement prononcé, la mention du divorce doit être portée sur l’acte ; l’acte ne disparaît pas et reste la preuve du mariage passé.',
                'A marriage certificate has no expiry date: it stays valid as long as the marriage lasts. If a divorce is legally granted, the divorce must be noted on the record; the certificate does not disappear and remains proof of the past marriage.',
                'عقد الزواج ليس له تاريخ انتهاء: يبقى صالحًا ما دام الزواج قائمًا. عند صدور طلاق قانوني تُدوَّن الإشارة إليه في العقد؛ ولا يزول العقد، بل يبقى دليلًا على الزواج السابق.',
            ],
        ],
    ];

    public function up(): void
    {
        foreach (self::RULES as $key => $change) {
            DB::table('document_validity_rules')->where('key', $key)->update([
                'status' => $change['status'],
                'validity_months' => null,
                'validity_start' => 'not_applicable',
                'notes' => $change['notes'][0],
                'notes_en' => $change['notes'][1],
                'notes_ar' => $change['notes'][2],
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Retour arrière : les trois règles reviennent à « à vérifier », statut d'origine.
        DB::table('document_validity_rules')
            ->whereIn('key', array_keys(self::RULES))
            ->update(['status' => 'to_verify', 'updated_at' => now()]);
    }
};
