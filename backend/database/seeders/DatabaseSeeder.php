<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categorie;
use App\Models\Demarche;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Référentiel unique (catégories du cahier des charges, classement et traductions des fiches).
        $catalogue = require database_path('data/catalogue.php');

        foreach ($catalogue['categories'] as $slug => [$fr, $en, $ar, $icon, $color, $order]) {
            Categorie::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $fr,
                    'name_en' => $en,
                    'name_ar' => $ar,
                    'icon' => $icon,
                    'color' => $color,
                    'display_order' => $order,
                    'is_active' => true,
                ]
            );
        }

        $demarches = [
            [
                'title' => 'Permis de Construction',
                'summary' => 'Obtenir une autorisation de construire auprès des autorités locales',
                'description' => 'Le permis de construction est un document officiel délivré par les autorités municipales autorisant la construction ou la modification d\'une structure. Ce processus garantit que votre projet respecte les codes de bâtiment et les plans d\'urbanisme locaux.',
                'cost' => '25,000 FCFA',
                'processing_time' => '5-7 jours ouvrables',
                'institution' => 'Direction Générale de l\'Urbanisme',
                'location' => 'Mairie de N\'Djaména, 3ème arrondissement',
                'contact_phone' => '+235 22 51 45 62',
                'contact_email' => 'urbanisme@ndjamena.td',
                'required_documents' => ['Plan d\'architecture signé', 'Titre de propriété', 'Pièce d\'identité valide', 'Formulaire de demande rempli'],
                'procedure_steps' => ['Préparer les documents', 'Aller à la mairie', 'Déposer le dossier', 'Payer les frais', 'Attendre approbation', 'Récupérer le permis']
            ],
            [
                'title' => 'Certificat de Mariage',
                'summary' => 'Obtenir un extrait de certificat de mariage',
                'description' => 'Le certificat de mariage est un document officiel prouvant l\'enregistrement de votre mariage. Cet extrait est nécessaire pour les procédures administratives, les demandes de visa, ou les questions d\'héritage.',
                'cost' => 'Gratuit',
                'processing_time' => '1-2 jours',
                'institution' => 'Tribunal de Grande Instance',
                'location' => 'Palais de Justice, Avenue Félix Éboué',
                'contact_phone' => '+235 22 51 40 55',
                'contact_email' => 'justice@tchad.td',
                'required_documents' => ['Livret de famille', 'Pièce d\'identité', 'Demande écrite'],
                'procedure_steps' => ['Aller au tribunal', 'Demander l\'extrait', 'Fournir les documents', 'Signer les formulaires', 'Récupérer le certificat']
            ],
            [
                'title' => 'Carte d\'Identité Nationale',
                'summary' => 'Demander ou renouveler votre carte d\'identité nationale',
                'description' => 'La carte d\'identité nationale est le document d\'identification principal au Tchad. Elle est obligatoire pour voter, voyager, ouvrir un compte bancaire et accéder à de nombreux services publics.',
                'cost' => '5,000 FCFA',
                'processing_time' => '10-15 jours',
                'institution' => 'Direction de l\'Immigration et des Documents',
                'location' => 'Ministère de l\'Intérieur, N\'Djaména',
                'contact_phone' => '+235 22 51 18 88',
                'contact_email' => 'immigration@interieur.td',
                'required_documents' => ['Certificat de naissance', 'Extrait de jugement supplétif', 'Deux photos 4x6', 'Pièce d\'identité actuelle'],
                'procedure_steps' => ['Retirer le formulaire', 'Remplir et signer', 'Fournir les documents', 'Passer les empreintes', 'Payer les frais', 'Attendre livraison']
            ],
            [
                'title' => 'Permis de Conduire',
                'summary' => 'Obtenir ou renouveler votre permis de conduire',
                'description' => 'Le permis de conduire est un document légal autorisant le conducteur à opérer un véhicule motorisé sur les voies publiques. Il existe plusieurs catégories selon le type de véhicule.',
                'cost' => '25,000 FCFA',
                'processing_time' => '5-10 jours',
                'institution' => 'Direction du Contrôle et de la Sécurité Routière',
                'location' => 'DSCR, Rue de l\'Hôtel de Ville',
                'contact_phone' => '+235 22 51 63 45',
                'contact_email' => 'dscr@interieur.td',
                'required_documents' => ['Certificat médical', 'Deux photos 4x6', 'Pièce d\'identité', 'Certificat d\'immatriculation', 'Attestation d\'assurance'],
                'procedure_steps' => ['Passer examen médical', 'Retirer le formulaire', 'Soumettre les documents', 'Passer test théorique', 'Passer test pratique', 'Récupérer le permis']
            ],
            [
                'title' => 'Inscription Université',
                'summary' => 'S\'inscrire dans une université tchadienne',
                'description' => 'L\'inscription universitaire est le processus permettant aux étudiants admis d\'enregistrer leur admission et de payer les frais d\'inscription. Chaque université a ses propres frais et conditions.',
                'cost' => '50,000 FCFA',
                'processing_time' => '3-5 jours',
                'institution' => 'Université de N\'Djaména',
                'location' => 'Campus Principal, Quartier du Fleuve',
                'contact_phone' => '+235 22 51 35 98',
                'contact_email' => 'inscriptions@univ-ndjamena.td',
                'required_documents' => ['Lettre d\'admission', 'Relevé de notes BAC', 'Copie identité', 'Certificat de naissance', 'Photo 4x6'],
                'procedure_steps' => ['Recevoir lettre d\'admission', 'Retirer formulaire', 'Payer les frais', 'Déposer dossier', 'Attendre confirmation', 'Recevoir numéro inscription']
            ],
            [
                'title' => 'Consultation Médicale',
                'summary' => 'Accéder aux services de consultation médicale',
                'description' => 'Les consultations médicales sont disponibles dans les hôpitaux, cliniques et centres de santé publics et privés. Les tarifs varient selon la structure et la complexité du cas.',
                'cost' => '10,000 FCFA',
                'processing_time' => '1 jour',
                'institution' => 'Ministère de la Santé Publique',
                'location' => 'Centre Hospitalier Universitaire, N\'Djaména',
                'contact_phone' => '+235 22 51 50 55',
                'contact_email' => 'sante@msante.td',
                'required_documents' => ['Pièce d\'identité', 'Carte d\'assuré social (si applicable)', 'Lettre de référence (si recommandé)'],
                'procedure_steps' => ['Venir à la clinique', 'S\'inscrire à l\'accueil', 'Attendre votre tour', 'Consulter le médecin', 'Payer les frais', 'Obtenir ordonnance']
            ],
            [
                'title' => 'Passeport Biométrique',
                'summary' => 'Obtenir ou renouveler votre passeport biométrique tchadien',
                'description' => 'Le passeport biométrique est le document de voyage officiel de la République du Tchad. Il intègre une puce électronique contenant les données biométriques du titulaire (empreintes, photo). Obligatoire pour tout voyage international.',
                'cost' => '75,000 FCFA',
                'cost_amount' => 75000,
                'processing_time' => '15-30 jours',
                'institution' => 'Direction Générale de la Police Nationale – Bureau des Passeports',
                'location' => 'Ministère de l\'Intérieur, Avenue Charles de Gaulle, N\'Djaména',
                'contact_phone' => '+235 22 51 18 00',
                'contact_email' => 'passeports@interieur.td',
                'required_documents' => [
                    'Acte de naissance (original ou copie légalisée)',
                    'Carte d\'identité nationale valide',
                    'Deux photos biométriques récentes (fond blanc, 4x6)',
                    'Extrait de casier judiciaire vierge (moins de 3 mois)',
                    'Reçu de paiement des frais',
                    'Ancien passeport (si renouvellement)',
                ],
                'procedure_steps' => [
                    'Retirer le formulaire de demande au bureau des passeports',
                    'Remplir et signer le formulaire',
                    'Constituer le dossier complet',
                    'Déposer le dossier au guichet',
                    'Payer les frais de 75 000 FCFA à la caisse',
                    'Se présenter pour la prise des empreintes biométriques',
                    'Attendre la notification (SMS ou affichage)',
                    'Récupérer le passeport muni de votre reçu',
                ],
            ],
            [
                'title' => 'Acte de Naissance',
                'summary' => 'Obtenir un extrait ou une copie intégrale de votre acte de naissance',
                'description' => 'L\'acte de naissance est le document fondamental de l\'état civil tchadien. Il atteste officiellement de la naissance d\'une personne et est exigé pour presque toutes les démarches administratives (CNI, passeport, mariage, inscription scolaire, etc.).',
                'cost' => '1,000 FCFA',
                'cost_amount' => 1000,
                'processing_time' => '1-5 jours',
                'institution' => 'Centre d\'État Civil — Mairie de votre commune',
                'location' => 'Mairie de N\'Djaména ou commune de naissance',
                'contact_phone' => '+235 22 51 40 00',
                'contact_email' => 'etatcivil@mairie-ndjamena.td',
                'required_documents' => [
                    'Livret de famille ou déclaration de naissance originale',
                    'Pièce d\'identité du demandeur (parent ou intéressé majeur)',
                    'Formulaire de demande d\'acte',
                ],
                'procedure_steps' => [
                    'Se rendre à la mairie ou au centre d\'état civil du lieu de naissance',
                    'Remplir le formulaire de demande',
                    'Présenter les pièces justificatives',
                    'Payer les frais de timbre (1 000 FCFA)',
                    'Récupérer l\'acte sur place ou dans les délais indiqués',
                ],
            ],
            [
                'title' => 'Acte de Décès',
                'summary' => 'Déclarer un décès et obtenir l\'acte de décès officiel',
                'description' => 'L\'acte de décès est un document officiel d\'état civil constatant le décès d\'une personne. Il est indispensable pour les successions, les pensions, la clôture de comptes bancaires et toutes les démarches administratives liées au décès.',
                'cost' => '1,000 FCFA',
                'cost_amount' => 1000,
                'processing_time' => '1-3 jours',
                'institution' => 'Centre d\'État Civil — Mairie de la commune du décès',
                'location' => 'Mairie de N\'Djaména ou commune concernée',
                'contact_phone' => '+235 22 51 40 00',
                'contact_email' => 'etatcivil@mairie-ndjamena.td',
                'required_documents' => [
                    'Certificat de décès délivré par le médecin ou l\'hôpital',
                    'Livret de famille du défunt',
                    'Pièce d\'identité du déclarant (parent proche)',
                ],
                'procedure_steps' => [
                    'Se rendre à la mairie dans les 8 jours suivant le décès',
                    'Présenter le certificat médical de décès',
                    'Remplir la déclaration de décès',
                    'Payer les frais de timbre (1 000 FCFA)',
                    'Récupérer l\'acte de décès officiel',
                ],
            ],
            [
                'title' => 'Casier Judiciaire',
                'summary' => 'Obtenir un extrait de casier judiciaire (bulletin n°3)',
                'description' => 'Le casier judiciaire (bulletin n°3) est un document officiel attestant que vous n\'avez pas d\'antécédents judiciaires en République du Tchad. Il est exigé pour l\'emploi public, l\'obtention d\'un passeport, ou toute procédure nécessitant une garantie de moralité.',
                'cost' => '2,000 FCFA',
                'cost_amount' => 2000,
                'processing_time' => '3-7 jours',
                'institution' => 'Tribunal de Grande Instance de N\'Djaména',
                'location' => 'Palais de Justice, Avenue Félix Éboué, N\'Djaména',
                'contact_phone' => '+235 22 51 40 55',
                'contact_email' => 'casier@justice.td',
                'required_documents' => [
                    'Carte d\'identité nationale ou passeport valide',
                    'Extrait d\'acte de naissance',
                    'Formulaire de demande rempli',
                    'Reçu de paiement des frais',
                ],
                'procedure_steps' => [
                    'Se rendre au greffe du Tribunal de Grande Instance',
                    'Retirer et remplir le formulaire de demande',
                    'Fournir les pièces d\'identité requises',
                    'Payer les frais (2 000 FCFA)',
                    'Retirer le document dans les délais indiqués',
                ],
            ],
            [
                'title' => 'Inscription sur la Liste Électorale (CENI)',
                'summary' => 'S\'inscrire sur les listes électorales auprès de la CENI',
                'description' => 'L\'inscription sur la liste électorale est obligatoire pour pouvoir voter lors des élections présidentielles, législatives ou municipales au Tchad. Elle est gérée par la Commission Électorale Nationale Indépendante (CENI).',
                'cost' => 'Gratuit',
                'cost_amount' => 0,
                'processing_time' => '1 jour (délais de révision annuels)',
                'institution' => 'Commission Électorale Nationale Indépendante (CENI)',
                'location' => 'Sièges CENI par arrondissement, N\'Djaména',
                'contact_phone' => '+235 22 52 30 00',
                'contact_email' => 'inscription@ceni.td',
                'required_documents' => [
                    'Carte d\'identité nationale valide',
                    'Acte de naissance',
                    'Justificatif de résidence (facture ou attestation)',
                ],
                'procedure_steps' => [
                    'Se rendre au bureau CENI de votre arrondissement',
                    'Présenter les documents requis',
                    'Remplir le formulaire d\'inscription',
                    'Recevoir la confirmation et la carte d\'électeur',
                ],
            ],
        ];

        foreach ($demarches as $dem) {
            $slug = Str::slug($dem['title']);
            $cat = Categorie::where('slug', $catalogue['placement'][$slug] ?? '')->first();
            if ($cat) {
                Demarche::firstOrCreate(
                    ['slug' => $slug],
                    [
                        'title' => $dem['title'],
                        'summary' => $dem['summary'],
                        'description' => $dem['description'],
                        'categorie_id' => $cat->id,
                        'institution' => $dem['institution'],
                        'cost' => $dem['cost'],
                        'cost_amount' => $dem['cost_amount'] ?? null,
                        'processing_time' => $dem['processing_time'],
                        'location' => $dem['location'],
                        'contact_phone' => $dem['contact_phone'],
                        'contact_email' => $dem['contact_email'],
                        'required_documents' => $dem['required_documents'],
                        'procedure_steps' => $dem['procedure_steps'],
                        'status' => 'published',
                    ]
                );
            }
        }

        // Complète les traductions EN/AR (champs vides uniquement) et vérifie la cohérence du classement.
        \Illuminate\Support\Facades\Artisan::call('catalogue:normalize', ['--apply' => true]);

        // Villes et parcours de vie (créés seulement s'ils manquent).
        $this->call(ReferenceDataSeeder::class);

        // Corrections sourcées (ANATS, tarifs) et retrait des coordonnées de démonstration invérifiables.
        \Illuminate\Support\Facades\Artisan::call('catalogue:corrections', ['--apply' => true]);

        $configuredPassword = env('SEED_ADMIN_PASSWORD');
        $adminPassword = $configuredPassword ?: Str::random(48);
        $admin = Admin::firstOrCreate(
            ['email' => 'superadmin@demarches.td'],
            [
                'name' => 'Admin Système',
                'password' => Hash::make($adminPassword),
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );
        if ($admin->wasRecentlyCreated && !$configuredPassword && $this->command) {
            $this->command->info('Mot de passe super-administrateur généré pour ce développement local : ' . $adminPassword);
            $this->command->warn('Conservez-le dans un gestionnaire de mots de passe ; il ne sera pas affiché à nouveau.');
        }

        $this->command->info('✅ Données créées avec succès!');
    }
}
