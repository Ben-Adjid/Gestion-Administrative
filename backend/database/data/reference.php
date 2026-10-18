<?php

/*
|--------------------------------------------------------------------------
| Données de référence : villes et parcours de vie
|--------------------------------------------------------------------------
| Utilisées par ReferenceDataSeeder, qui crée seulement ce qui manque :
| une modification faite ensuite dans l'administration n'est jamais écrasée.
|
| Villes : les 23 chefs-lieux de province du Tchad, plus Kélo. Aucun bureau n'est pré-rempli :
| les adresses de guichets sont saisies par l'équipe après vérification.
|
| Parcours : chaque enchaînement découle des pièces demandées dans les fiches
| (ex. le passeport demande l'acte de naissance, la CNI et le casier judiciaire).
*/

return [
    'cities' => [
        // slug => [nom, nom arabe, province, ordre]
        'ndjamena' => ["N'Djaména", 'إنجامينا', "Ville de N'Djaména", 1],
        'moundou' => ['Moundou', 'موندو', 'Logone Occidental', 2],
        'sarh' => ['Sarh', 'سار', 'Moyen-Chari', 3],
        'abeche' => ['Abéché', 'أبشي', 'Ouaddaï', 4],
        'ati' => ['Ati', 'آتي', 'Batha', 10],
        'bardai' => ['Bardaï', 'برداي', 'Tibesti', 10],
        'biltine' => ['Biltine', 'بلتين', 'Wadi Fira', 10],
        'bol' => ['Bol', 'بول', 'Lac', 10],
        'bongor' => ['Bongor', 'بونغور', 'Mayo-Kebbi Est', 10],
        'doba' => ['Doba', 'دوبا', 'Logone Oriental', 10],
        'faya-largeau' => ['Faya-Largeau', 'فايا لارجو', 'Borkou', 10],
        'fada' => ['Fada', 'فادا', 'Ennedi Ouest', 10],
        'amdjarass' => ['Amdjarass', 'أم جرس', 'Ennedi Est', 10],
        'goz-beida' => ['Goz Beïda', 'قوز بيضا', 'Sila', 10],
        'koumra' => ['Koumra', 'كومرا', 'Mandoul', 10],
        'lai' => ['Laï', 'لاي', 'Tandjilé', 10],
        'mao' => ['Mao', 'ماو', 'Kanem', 10],
        'massakory' => ['Massakory', 'ماساكوري', 'Hadjer-Lamis', 10],
        'mongo' => ['Mongo', 'مونغو', 'Guéra', 10],
        'moussoro' => ['Moussoro', 'موسورو', 'Barh El Gazel', 10],
        'pala' => ['Pala', 'بالا', 'Mayo-Kebbi Ouest', 10],
        'am-timan' => ['Am Timan', 'أم تيمان', 'Salamat', 10],
        'massenya' => ['Massenya', 'ماسينيا', 'Chari-Baguirmi', 10],
        // Grande ville qui n'est pas chef-lieu de province
        'kelo' => ['Kélo', 'كيلو', 'Tandjilé', 20],
    ],

    // Synonymes de recherche (terme courant => terme des fiches), utilisés dans les deux sens.
    'synonyms' => [
        ["pièce d'identité", "carte d'identité"],
        ['cni', "carte d'identité"],
        ['carte nationale', "carte d'identité"],
        ['extrait de naissance', 'acte de naissance'],
        ['certificat de naissance', 'acte de naissance'],
        ['bulletin n°3', 'casier judiciaire'],
        ['passport', 'passeport'],
        ['permis de construire', 'permis de construction'],
        ['élections', 'liste électorale'],
        ['voter', 'liste électorale'],
        ['fac', 'université'],
    ],

    'life_events' => [
        'voyager-a-l-etranger' => [
            'icon' => 'plane', 'order' => 1,
            'title' => ["Je veux voyager à l'étranger", 'I want to travel abroad', 'أريد السفر إلى الخارج'],
            'description' => [
                'Le passeport demande un acte de naissance, une carte d’identité et un casier judiciaire : faites-les dans cet ordre.',
                'The passport requires a birth certificate, an identity card and a criminal record extract: obtain them in this order.',
                'يتطلب جواز السفر شهادة ميلاد وبطاقة تعريف وسجلًا عدليًا: استخرجها بهذا الترتيب.',
            ],
            'steps' => ['acte-de-naissance', 'carte-didentite-nationale', 'casier-judiciaire', 'passeport-biometrique'],
        ],
        'obtenir-mes-papiers' => [
            'icon' => 'id-card', 'order' => 2,
            'title' => ["J'ai besoin de mes papiers d'identité", 'I need my identity documents', 'أحتاج إلى وثائق هويتي'],
            'description' => [
                'La carte nationale d’identité demande d’abord un certificat de naissance.',
                'The national identity card first requires a birth certificate.',
                'تتطلب بطاقة التعريف الوطنية أولًا شهادة ميلاد.',
            ],
            'steps' => ['acte-de-naissance', 'carte-didentite-nationale'],
        ],
        'j-ai-un-bebe' => [
            'icon' => 'baby', 'order' => 3,
            'title' => ["J'ai un bébé", 'I have a baby', 'رُزقت بمولود'],
            'description' => [
                'Déclarez la naissance et obtenez l’acte de naissance : c’est le premier document de votre enfant.',
                'Declare the birth and obtain the birth certificate: it is your child’s first document.',
                'صرّح بالولادة واستخرج شهادة الميلاد: إنها أول وثيقة لطفلك.',
            ],
            'steps' => ['acte-de-naissance'],
        ],
        'je-me-marie' => [
            'icon' => 'heart', 'order' => 4,
            'title' => ['Je me marie', 'I am getting married', 'سأتزوج'],
            'description' => [
                'Le certificat de mariage demande une pièce d’identité : préparez-la à l’avance.',
                'The marriage certificate requires an identity document: prepare it in advance.',
                'تتطلب شهادة الزواج وثيقة هوية: جهّزها مسبقًا.',
            ],
            'steps' => ['carte-didentite-nationale', 'certificat-de-mariage'],
        ],
        'je-perds-un-proche' => [
            'icon' => 'flower', 'order' => 5,
            'title' => ['Je perds un proche', 'I have lost a loved one', 'فقدت أحد أقاربي'],
            'description' => [
                'L’acte de décès est indispensable pour la succession, les pensions et la clôture des comptes.',
                'The death certificate is essential for inheritance, pensions and closing accounts.',
                'شهادة الوفاة ضرورية للميراث والمعاشات وإغلاق الحسابات.',
            ],
            'steps' => ['acte-de-deces'],
        ],
        'je-veux-voter' => [
            'icon' => 'vote', 'order' => 6,
            'title' => ['Je veux voter', 'I want to vote', 'أريد التصويت'],
            'description' => [
                'L’inscription sur la liste électorale demande un acte de naissance et une carte d’identité valide.',
                'Registering on the electoral roll requires a birth certificate and a valid identity card.',
                'يتطلب التسجيل في القائمة الانتخابية شهادة ميلاد وبطاقة تعريف سارية.',
            ],
            'steps' => ['acte-de-naissance', 'carte-didentite-nationale', 'inscription-sur-la-liste-electorale-ceni'],
        ],
        'j-entre-a-l-universite' => [
            'icon' => 'graduation-cap', 'order' => 7,
            'title' => ["J'entre à l'université", 'I am starting university', 'سألتحق بالجامعة'],
            'description' => [
                'L’inscription demande un certificat de naissance et une copie de votre pièce d’identité.',
                'Enrolment requires a birth certificate and a copy of your identity document.',
                'يتطلب التسجيل شهادة ميلاد ونسخة من وثيقة هويتك.',
            ],
            'steps' => ['acte-de-naissance', 'carte-didentite-nationale', 'inscription-universite'],
        ],
        'je-veux-conduire' => [
            'icon' => 'car', 'order' => 8,
            'title' => ['Je veux conduire', 'I want to drive', 'أريد قيادة السيارة'],
            'description' => [
                'Le permis de conduire demande une pièce d’identité.',
                'The driving licence requires an identity document.',
                'تتطلب رخصة السياقة وثيقة هوية.',
            ],
            'steps' => ['carte-didentite-nationale', 'permis-de-conduire'],
        ],
        'je-construis' => [
            'icon' => 'home', 'order' => 9,
            'title' => ['Je construis ma maison', 'I am building my house', 'أبني منزلي'],
            'description' => [
                'Le permis de construire demande une pièce d’identité, un titre de propriété et un plan signé.',
                'The building permit requires an identity document, a title deed and a signed plan.',
                'تتطلب رخصة البناء وثيقة هوية وسند ملكية ومخططًا موقّعًا.',
            ],
            'steps' => ['carte-didentite-nationale', 'permis-de-construction'],
        ],
    ],
];
