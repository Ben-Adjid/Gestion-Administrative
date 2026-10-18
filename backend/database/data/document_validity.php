<?php

/*
 * Règles de validité des documents tchadiens.
 * Règle absolue : aucune durée sans source. Quand aucune source n'a été trouvée, validity_months reste null
 * et status vaut « to_verify » : le site l'affiche « à vérifier » et ne calcule aucune date d'expiration.
 *
 * Source secondaire utilisée (consultée le 16/10/2026) :
 *   Commission de l'immigration et du statut de réfugié du Canada (CISR), Direction des recherches,
 *   réponse à une demande d'information TCD106034.FE du 2 janvier 2018, publiée sur ecoi.net.
 *   Elle cite IZF (2017) et Keesing pour la carte d'identité, le registre PRADO de l'UE (2017) pour le permis,
 *   et le Département d'État des États-Unis pour les passeports diplomatiques et de service.
 * Les sites PRADO et travel.state.gov n'ont pas pu être consultés directement (vérification anti-robot) :
 * ces durées restent « source officielle secondaire » à reconfirmer auprès de l'administration émettrice.
 */

$irb = [
    'source_label' => 'CISR (Canada), réponse à une demande d’information TCD106034.FE',
    'source_url' => 'https://www.ecoi.net/en/document/1443867.html',
    'source_date' => '2018-01-02',
    'verified_on' => '2026-10-16',
];

$etatCivilRenewal = [
    'Un nouvel acte (copie intégrale ou extrait) se demande au centre d’état civil qui détient l’acte d’origine.',
    'A new certificate (full copy or extract) is requested from the civil registry office that holds the original record.',
    'تُطلب نسخة جديدة (كاملة أو مستخرج) من مركز الحالة المدنية الذي يحفظ العقد الأصلي.',
];

return [
    [
        'key' => 'carte-nationale-identite', 'position' => 1, 'category' => 'identity', 'demarche' => 'carte-didentite-nationale',
        'name' => ['Carte nationale d’identité', 'National identity card', 'بطاقة التعريف الوطنية'],
        'validity_months' => 120, 'validity_start' => 'issue_date', 'status' => 'secondary',
        'renewal' => [
            'À renouveler avant la date d’expiration auprès de l’ANATS ; le numéro national d’identification (NNI) est requis.',
            'Renew before the expiry date with ANATS; the national identification number (NNI) is required.',
            'تُجدَّد قبل تاريخ انتهاء الصلاحية لدى الوكالة الوطنية للوثائق المؤمّنة؛ رقم التعريف الوطني مطلوب.',
        ],
        'notes' => [
            'Durée de 10 ans indiquée par une source officielle secondaire datée de 2018 : à reconfirmer auprès de l’ANATS.',
            '10-year validity stated by a secondary official source dated 2018: to be reconfirmed with ANATS.',
            'مدة 10 سنوات مذكورة في مصدر رسمي ثانوي يعود إلى 2018: يجب التأكد منها لدى الوكالة الوطنية للوثائق المؤمّنة.',
        ],
    ] + $irb,
    [
        'key' => 'passeport-ordinaire', 'position' => 2, 'category' => 'travel', 'demarche' => 'passeport-biometrique',
        'name' => ['Passeport ordinaire (biométrique)', 'Ordinary passport (biometric)', 'جواز السفر العادي (البيومتري)'],
        'validity_months' => null, 'validity_start' => 'issue_date', 'status' => 'to_verify',
        'renewal' => [
            'Demande d’un nouveau passeport auprès de l’ANATS (pièces et coût : voir la fiche passeport).',
            'Apply for a new passport with ANATS (documents and cost: see the passport page).',
            'يُطلب جواز جديد لدى الوكالة الوطنية للوثائق المؤمّنة (الوثائق والتكلفة: انظر صفحة جواز السفر).',
        ],
        'notes' => [
            'Aucune source officielle consultée ne précise la durée de validité du passeport ordinaire : elle peut dépendre de l’âge du titulaire. La date d’expiration est imprimée sur la page de données de votre passeport.',
            'No official source consulted states the validity of the ordinary passport: it may depend on the holder’s age. The expiry date is printed on your passport’s data page.',
            'لا يحدّد أي مصدر رسمي اطّلعنا عليه مدة صلاحية جواز السفر العادي، وقد تختلف حسب سن الحامل. تاريخ انتهاء الصلاحية مطبوع في صفحة البيانات من جوازك.',
        ],
        'source_label' => null, 'source_url' => null, 'source_date' => null, 'verified_on' => '2026-10-16',
    ],
    [
        'key' => 'passeport-diplomatique-service', 'position' => 3, 'category' => 'travel', 'demarche' => null,
        'name' => ['Passeport diplomatique ou de service', 'Diplomatic or service passport', 'جواز السفر الدبلوماسي أو جواز الخدمة'],
        'validity_months' => 60, 'validity_start' => 'issue_date', 'status' => 'secondary',
        'renewal' => [
            'Délivré par l’administration aux personnes en mission officielle ; renouvellement par la même voie.',
            'Issued by the administration to people on official duty; renewed through the same channel.',
            'تصدره الإدارة للأشخاص في مهمة رسمية؛ ويُجدَّد بالطريقة نفسها.',
        ],
        'notes' => [
            'Durée de 5 ans pour les passeports diplomatiques et de service délivrés depuis février 2002 (source secondaire de 2018).',
            '5-year validity for diplomatic and service passports issued since February 2002 (secondary source, 2018).',
            'مدة 5 سنوات لجوازات السفر الدبلوماسية وجوازات الخدمة الصادرة منذ فبراير 2002 (مصدر ثانوي، 2018).',
        ],
    ] + $irb,
    [
        'key' => 'permis-de-conduire', 'position' => 4, 'category' => 'transport', 'demarche' => 'permis-de-conduire',
        'name' => ['Permis de conduire', 'Driving licence', 'رخصة السياقة'],
        'validity_months' => 48, 'validity_start' => 'issue_date', 'status' => 'secondary',
        'renewal' => [
            'À renouveler avant la date d’expiration (pièces et coût : voir la fiche permis de conduire).',
            'Renew before the expiry date (documents and cost: see the driving licence page).',
            'تُجدَّد قبل تاريخ انتهاء الصلاحية (الوثائق والتكلفة: انظر صفحة رخصة السياقة).',
        ],
        'notes' => [
            'Durée de 4 ans indiquée par une source officielle secondaire (2018, d’après le registre PRADO de l’Union européenne) : à reconfirmer auprès du service qui délivre le permis.',
            '4-year validity stated by a secondary official source (2018, based on the EU PRADO register): to be reconfirmed with the issuing office.',
            'مدة 4 سنوات مذكورة في مصدر رسمي ثانوي (2018، استنادًا إلى سجل PRADO للاتحاد الأوروبي): يجب التأكد منها لدى الجهة المُصدِرة.',
        ],
    ] + $irb,
    [
        'key' => 'acte-de-naissance', 'position' => 5, 'category' => 'civil_status', 'demarche' => 'acte-de-naissance',
        'name' => ['Acte de naissance', 'Birth certificate', 'عقد الميلاد'],
        'validity_months' => null, 'validity_start' => 'not_applicable', 'status' => 'permanent',
        'renewal' => $etatCivilRenewal,
        'notes' => [
            'Un acte de naissance n’a pas de date d’expiration : sa validité est permanente. Certaines administrations demandent cependant une copie ou un extrait récent : l’ancienneté acceptée dépend de l’organisme qui le demande.',
            'A birth certificate has no expiry date: its validity is permanent. Some offices still ask for a recent copy or extract: the age they accept depends on the office asking for it.',
            'شهادة الميلاد ليس لها تاريخ انتهاء: صلاحيتها دائمة. لكن بعض الإدارات تطلب نسخة أو مستخرجًا حديثًا، وتختلف المدة المقبولة حسب الجهة التي تطلبه.',
        ],
        'source_label' => null, 'source_url' => null, 'source_date' => null, 'verified_on' => '2026-10-16',
    ],
    [
        'key' => 'acte-de-mariage', 'position' => 6, 'category' => 'civil_status', 'demarche' => 'certificat-de-mariage',
        'name' => ['Acte ou certificat de mariage', 'Marriage certificate', 'عقد الزواج'],
        'validity_months' => null, 'validity_start' => 'not_applicable', 'status' => 'status_based',
        'renewal' => $etatCivilRenewal,
        'notes' => [
            'Le mariage n’a pas de date d’expiration : l’acte reste valable tant que le mariage subsiste. En cas de divorce légalement prononcé, la mention du divorce doit être portée sur l’acte ; l’acte ne disparaît pas et reste la preuve du mariage passé.',
            'A marriage certificate has no expiry date: it stays valid as long as the marriage lasts. If a divorce is legally granted, the divorce must be noted on the record; the certificate does not disappear and remains proof of the past marriage.',
            'عقد الزواج ليس له تاريخ انتهاء: يبقى صالحًا ما دام الزواج قائمًا. عند صدور طلاق قانوني تُدوَّن الإشارة إليه في العقد؛ ولا يزول العقد، بل يبقى دليلًا على الزواج السابق.',
        ],
        'source_label' => null, 'source_url' => null, 'source_date' => null, 'verified_on' => '2026-10-16',
    ],
    [
        'key' => 'acte-de-deces', 'position' => 7, 'category' => 'civil_status', 'demarche' => 'acte-de-deces',
        'name' => ['Acte de décès', 'Death certificate', 'عقد الوفاة'],
        'validity_months' => null, 'validity_start' => 'not_applicable', 'status' => 'permanent',
        'renewal' => $etatCivilRenewal,
        'notes' => [
            'Un acte de décès n’a pas de date d’expiration : sa validité est permanente. Certaines administrations demandent une copie ou un extrait récent : l’ancienneté acceptée dépend de l’organisme qui le demande.',
            'A death certificate has no expiry date: its validity is permanent. Some offices ask for a recent copy or extract: the age they accept depends on the office asking for it.',
            'شهادة الوفاة ليس لها تاريخ انتهاء: صلاحيتها دائمة. تطلب بعض الإدارات نسخة أو مستخرجًا حديثًا، وتختلف المدة المقبولة حسب الجهة التي تطلبه.',
        ],
        'source_label' => null, 'source_url' => null, 'source_date' => null, 'verified_on' => '2026-10-16',
    ],
    [
        'key' => 'casier-judiciaire', 'position' => 8, 'category' => 'justice', 'demarche' => 'casier-judiciaire',
        'name' => ['Extrait de casier judiciaire (bulletin n°3)', 'Criminal record extract (bulletin no. 3)', 'مستخرج السجل العدلي (البطاقة رقم 3)'],
        'validity_months' => null, 'validity_start' => 'unknown', 'status' => 'to_verify',
        'renewal' => [
            'Un nouvel extrait se demande au tribunal compétent (voir la fiche casier judiciaire).',
            'A new extract is requested from the competent court (see the criminal record page).',
            'يُطلب مستخرج جديد من المحكمة المختصة (انظر صفحة السجل العدلي).',
        ],
        'notes' => [
            'Aucune durée réglementaire n’a été trouvée. L’extrait porte sa date de délivrance ; l’organisme qui le demande fixe l’ancienneté qu’il accepte — renseignez-vous auprès de lui.',
            'No regulatory period was found. The extract shows its issue date; the office asking for it sets how old it may be — check with that office.',
            'لم يُعثر على مدة تنظيمية. يحمل المستخرج تاريخ صدوره، وتحدّد الجهة التي تطلبه المدة التي تقبلها — فاستفسر لديها.',
        ],
        'source_label' => null, 'source_url' => null, 'source_date' => null, 'verified_on' => '2026-10-16',
    ],
];
