<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemarcheSeeder extends Seeder
{
    public function run(): void
    {
        $demarches = [
            ['title' => 'Demande de Visa Touristique', 'cost' => '50 000 FCFA', 'processing_time' => '7 jours'],
            ['title' => 'Renouvellement Passeport', 'cost' => '65 000 FCFA', 'processing_time' => '10 jours'],
            ['title' => 'Immatriculation Véhicule', 'cost' => '35 000 FCFA', 'processing_time' => '5 jours'],
            ['title' => 'Certificat de Résidence', 'cost' => '5 000 FCFA', 'processing_time' => '2 jours'],
            ['title' => 'Levée de Succession', 'cost' => '100 000 FCFA', 'processing_time' => '30 jours'],
            ['title' => 'Inscription Université', 'cost' => 'Gratuit', 'processing_time' => '15 jours'],
            ['title' => 'Permis de Commerce', 'cost' => '25 000 FCFA', 'processing_time' => '3 jours'],
            ['title' => 'Autorisation de Travail', 'cost' => '15 000 FCFA', 'processing_time' => '7 jours'],
            ['title' => 'Certificat de Mariage', 'cost' => '10 000 FCFA', 'processing_time' => '1 jour'],
            ['title' => 'Extrait de Naissance', 'cost' => '3 000 FCFA', 'processing_time' => '1 jour'],
            ['title' => 'Demande de Bourse Scolaire', 'cost' => 'Gratuit', 'processing_time' => '60 jours'],
            ['title' => 'Attestation de Domicile', 'cost' => '2 000 FCFA', 'processing_time' => '1 jour'],
            ['title' => 'Licence d\'Exploitation Commerciale', 'cost' => '50 000 FCFA', 'processing_time' => '10 jours'],
            ['title' => 'Visite Technique Véhicule', 'cost' => '8 000 FCFA', 'processing_time' => '1 jour'],
            ['title' => 'Assurance Obligatoire Automobile', 'cost' => '40 000 FCFA', 'processing_time' => '3 jours'],
            ['title' => 'Inscription Ordre Professionnel', 'cost' => '20 000 FCFA', 'processing_time' => '7 jours'],
            ['title' => 'Attestation de Moralité', 'cost' => '5 000 FCFA', 'processing_time' => '2 jours'],
            ['title' => 'Demande d\'Expatriation', 'cost' => '30 000 FCFA', 'processing_time' => '14 jours'],
            ['title' => 'Enregistrement de Marque', 'cost' => '75 000 FCFA', 'processing_time' => '30 jours'],
            ['title' => 'Demande de Naturalisation', 'cost' => '100 000 FCFA', 'processing_time' => '90 jours'],
            ['title' => 'Autorisation de Construire', 'cost' => '60 000 FCFA', 'processing_time' => '20 jours'],
            ['title' => 'Permis de Résidence', 'cost' => '25 000 FCFA', 'processing_time' => '10 jours'],
            ['title' => 'Enregistrement Contrat Travail', 'cost' => '5 000 FCFA', 'processing_time' => '2 jours'],
            ['title' => 'Levée de Restriction Bancaire', 'cost' => '10 000 FCFA', 'processing_time' => '5 jours'],
            ['title' => 'Attestation de Solvabilité', 'cost' => '7 000 FCFA', 'processing_time' => '3 jours'],
            ['title' => 'Demande de Congé Maternité', 'cost' => 'Gratuit', 'processing_time' => '1 jour'],
            ['title' => 'Certificat de Décès', 'cost' => '2 000 FCFA', 'processing_time' => '1 jour'],
            ['title' => 'Autorisation d\'Exportation', 'cost' => '40 000 FCFA', 'processing_time' => '7 jours'],
            ['title' => 'Licence d\'Importation', 'cost' => '45 000 FCFA', 'processing_time' => '10 jours'],
            ['title' => 'Enregistrement Copropriété', 'cost' => '80 000 FCFA', 'processing_time' => '15 jours'],
        ];

        foreach ($demarches as $key => $demarche) {
            $slug = \Illuminate\Support\Str::slug($demarche['title']);
            // Vérifier si la démarche n'existe pas déjà
            if (DB::table('demarches')->where('slug', $slug)->exists()) {
                continue; // Sauter si elle existe
            }
            DB::table('demarches')->insert([
                'categorie_id' => ($key % 5) + 1,
                'title' => $demarche['title'],
                'slug' => $slug,
                'summary' => 'Procédure officielle pour ' . strtolower($demarche['title']),
                'description' => 'Description complète de la démarche: ' . $demarche['title'],
                'cost' => $demarche['cost'],
                'cost_amount' => rand(2000, 100000),
                'processing_time' => $demarche['processing_time'],
                'processing_time_days' => rand(1, 90),
                'institution' => 'Ministère / Agence Gouvernementale',
                'location' => 'N\'Djamena',
                'contact_email' => 'contact@gouv.td',
                'contact_phone' => '+235 22 51 00 00',
                'official_source_url' => 'https://gouv.td',
                'last_verified_at' => now(),
                'status' => 'published',
                'is_online' => rand(0, 1),
                'required_documents' => json_encode(['Document 1', 'Document 2', 'Document 3']),
                'procedure_steps' => json_encode(['Étape 1', 'Étape 2', 'Étape 3']),
                'conditions' => json_encode(['Condition 1', 'Condition 2']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        echo "✅ 30 démarches ajoutées avec succès !";
    }
}
