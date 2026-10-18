<?php

/*
|--------------------------------------------------------------------------
| Corrections sourcées du catalogue (audit du 2026-10-16)
|--------------------------------------------------------------------------
| Appliquées par `php artisan catalogue:corrections --apply`. Chaque correction cite sa source ;
| les anciennes valeurs sont copiées dans le journal d'audit (restauration possible).
| Les sources sont des articles de presse tchadiens : les fiches restent « non vérifiées »
| tant que l'équipe n'a pas confirmé auprès du service (bouton « Marquer comme vérifiée »).
*/

$anats = [
    'institution' => 'Agence Nationale des Titres Sécurisés (ANATS)',
    'institution_en' => 'National Agency for Secure Documents (ANATS)',
    'institution_ar' => 'الوكالة الوطنية للوثائق المؤمّنة (ANATS)',
    'location' => "Centres d'accueil de l'ANATS (N'Djaména et chefs-lieux de province)",
    'location_en' => "ANATS reception centres (N'Djaména and provincial capitals)",
    'location_ar' => 'مراكز استقبال الوكالة الوطنية للوثائق المؤمّنة (إنجامينا وعواصم الولايات)',
];

$sourceNdjamena = 'https://tchadinfos.com/2023/01/31/tchad-lanats-ouvre-un-centre-dans-le-8eme-arrondissement/';
$sourceBongor = 'https://www.alwihdainfo.com/tchad-ouverture-d-un-centre-de-delivrance-de-titres-securises-a-bongor-a120987/';
$sourceMongo = 'https://www.alwihdainfo.com/tchad-afflux-des-voyageurs-a-l-anats-de-mongo-pour-la-delivrance-du-passeport-a104358/';

return [
    // slug => champs corrigés
    'demarches' => [
        'passeport-biometrique' => $anats + [
            // Tarif annoncé en janvier 2020 (Alwihda Info) et confirmé en février 2023 (centre de Bongor).
            'cost' => '60 000 FCFA', 'cost_en' => '60,000 FCFA', 'cost_ar' => '60,000 فرنك أفريقي', 'cost_amount' => 60000,
            'official_source_url' => $sourceBongor,
        ],
        'carte-didentite-nationale' => $anats + [
            // Pièces annoncées par le directeur général de l'ANATS (Tchadinfos, 31/01/2023).
            'required_documents' => ["Copie de l'acte de naissance", 'Justificatif de profession (si nécessaire)', "Deux photos d'identité en couleur", "Trois copies de cartes d'identité de témoins"],
            'required_documents_en' => ['Copy of the birth certificate', 'Proof of occupation (if required)', 'Two colour identity photos', "Three copies of witnesses' identity cards"],
            'required_documents_ar' => ['نسخة من شهادة الميلاد', 'إثبات المهنة (عند الاقتضاء)', 'صورتان شمسيتان ملوّنتان', 'ثلاث نسخ من بطاقات تعريف الشهود'],
            'conditions' => ["Être enrôlé à l'ANATS et disposer du numéro national d'identification (NNI)", 'Pour un mineur : présenter le NNI'],
            'conditions_en' => ['Be enrolled with ANATS and hold a national identification number (NNI)', 'For a minor: present the NNI'],
            'conditions_ar' => ['التسجيل لدى الوكالة الوطنية للوثائق المؤمّنة والحصول على الرقم الوطني للتعريف (NNI)', 'بالنسبة للقاصر: تقديم الرقم الوطني للتعريف'],
            'official_source_url' => $sourceNdjamena,
        ],
    ],

    // Coordonnées de démonstration jamais vérifiées (numéros 22 51 xx xx consécutifs, domaines inventés) :
    // retirées de l'affichage. Seules ces valeurs exactes sont concernées ; une saisie de l'équipe n'est pas touchée.
    'unverified_contacts' => [
        'phones' => ['+235 22 51 45 62', '+235 22 51 40 55', '+235 22 51 18 88', '+235 22 51 63 45', '+235 22 51 35 98', '+235 22 51 50 55', '+235 22 51 18 00', '+235 22 51 40 00', '+235 22 52 30 00'],
        'emails' => ['urbanisme@ndjamena.td', 'justice@tchad.td', 'immigration@interieur.td', 'dscr@interieur.td', 'inscriptions@univ-ndjamena.td', 'sante@msante.td', 'passeports@interieur.td', 'etatcivil@mairie-ndjamena.td', 'casier@justice.td', 'inscription@ceni.td'],
    ],

    // Centres ANATS cités par la presse. Pas d'adresse précise publiée : l'itinéraire recherche le nom du centre.
    'offices' => array_merge(
        array_map(fn (array $arr) => [
            'city' => 'ndjamena',
            'name' => "Centre d'accueil ANATS du {$arr[0]} arrondissement",
            'name_en' => "ANATS reception centre, district {$arr[1]}",
            'name_ar' => "مركز استقبال الوكالة الوطنية للوثائق المؤمّنة – الدائرة {$arr[1]}",
            'demarches' => ['passeport-biometrique', 'carte-didentite-nationale'],
            'source_url' => $sourceNdjamena,
        ], [['1er', 1], ['2e', 2], ['5e', 5], ['6e', 6], ['7e', 7], ['8e', 8], ['10e', 10]]),
        [
            [
                'city' => 'bongor',
                'name' => 'Centre de délivrance de titres sécurisés de Bongor (ANATS)',
                'name_en' => 'Bongor secure documents centre (ANATS)',
                'name_ar' => 'مركز إصدار الوثائق المؤمّنة في بونغور (ANATS)',
                'demarches' => ['passeport-biometrique', 'carte-didentite-nationale'],
                'source_url' => $sourceBongor,
            ],
            [
                'city' => 'mongo',
                'name' => 'Antenne ANATS de Mongo',
                'name_en' => 'ANATS office in Mongo',
                'name_ar' => 'فرع الوكالة الوطنية للوثائق المؤمّنة في مونغو',
                'demarches' => ['passeport-biometrique'],
                'source_url' => $sourceMongo,
            ],
        ],
    ),

    // Montants restés incohérents dans les étapes après la correction du tarif (lecture audio, 16/10/2026) :
    // slug => champ (liste) => [ancien texte => nouveau texte]. Remplacement ciblé, sans toucher au reste des étapes.
    'text_fixes' => [
        'passeport-biometrique' => [
            'procedure_steps' => ['75 000 FCFA' => '60 000 FCFA'],
            'procedure_steps_en' => ['75,000 FCFA' => '60,000 FCFA'],
            'procedure_steps_ar' => ['75,000 فرنك أفريقي' => '60,000 فرنك أفريقي'],
        ],
        // Acte de naissance : le lieu est celui de la NAISSANCE, pas la ville où l'on se trouve (texte sans adresse inventée).
        'acte-de-naissance' => [
            'location' => ["Mairie de N'Djaména ou commune de naissance" => "Centre d'état civil du lieu de naissance (mairie ou centre d'accueil de la commune de naissance)"],
        ],
        // Lieu en anglais resté en français : le sigle officiel DSCR et l'adresse sont conservés.
        'permis-de-conduire' => [
            'location_en' => ["DSCR, Rue de l'Hôtel de Ville" => "DSCR (Road Safety and Control Directorate), Rue de l'Hôtel de Ville"],
        ],
    ],

    // Organisme compétent et ville du texte « lieu », déduits de ce que chaque fiche indique déjà (aucune adresse ajoutée).
    // Niveau null quand la fiche ne permet pas de le déterminer. Ville « ndjamena » : le texte « lieu » ne vaut que pour N'Djaména.
    'authorities' => [
        'passeport-biometrique' => ['level' => 'national', 'location_city' => null],          // ANATS, centres dans plusieurs villes
        'carte-didentite-nationale' => ['level' => 'national', 'location_city' => null],      // ANATS
        // Acte de naissance : on s'adresse à l'officier d'état civil du LIEU DE NAISSANCE (et non à la ville où l'on se trouve).
        'acte-de-naissance' => ['level' => 'local', 'location_city' => null],
        'acte-de-deces' => ['level' => 'local', 'location_city' => 'ndjamena'],              // « Centre d'État Civil — Mairie de la commune… »
        'casier-judiciaire' => ['level' => 'regional', 'location_city' => 'ndjamena'],       // Tribunal de grande instance (juridiction)
        'certificat-de-mariage' => ['level' => 'regional', 'location_city' => 'ndjamena'],   // Tribunal de grande instance d'après la fiche
        'permis-de-conduire' => ['level' => 'national', 'location_city' => 'ndjamena'],      // Direction nationale (DSCR) ; lieu indiqué à N'Djaména
        'permis-de-construction' => ['level' => null, 'location_city' => 'ndjamena'],
        'inscription-universite' => ['level' => null, 'location_city' => 'ndjamena'],
        'consultation-medicale' => ['level' => null, 'location_city' => 'ndjamena'],
        'inscription-sur-la-liste-electorale-ceni' => ['level' => null, 'location_city' => 'ndjamena'],
    ],
];
