<?php

namespace Database\Seeders;

use App\Models\Synonyme;
use Illuminate\Database\Seeder;

class SynonymeSeeder extends Seeder
{
    public function run(): void
    {
        $synonymes = [
            // Identité et documents
            ['term' => 'CNI', 'synonym' => 'Carte Nationale d\'Identité', 'weight' => 10],
            ['term' => 'Carte d\'identité', 'synonym' => 'CNI', 'weight' => 10],
            ['term' => 'Passeport', 'synonym' => 'Passeport biométrique', 'weight' => 9],
            ['term' => 'Passeport biométrique', 'synonym' => 'Passeport', 'weight' => 9],

            // Entreprise et commerce
            ['term' => 'Entreprise', 'synonym' => 'Création d\'entreprise', 'weight' => 8],
            ['term' => 'ANIE', 'synonym' => 'Guichet unique', 'weight' => 8],
            ['term' => 'Guichet unique', 'synonym' => 'ANIE', 'weight' => 8],
            ['term' => 'RCCM', 'synonym' => 'Registre commerce', 'weight' => 7],

            // Permis et circulation
            ['term' => 'Permis', 'synonym' => 'Permis de conduire', 'weight' => 9],
            ['term' => 'Permis de conduire', 'synonym' => 'Permis', 'weight' => 9],
            ['term' => 'Immatriculation', 'synonym' => 'Plaque minéralogique', 'weight' => 7],

            // Justice et police
            ['term' => 'Casier judiciaire', 'synonym' => 'Bulletin n°3', 'weight' => 8],
            ['term' => 'Bulletin n°3', 'synonym' => 'Casier judiciaire', 'weight' => 8],
            ['term' => 'Antécédents', 'synonym' => 'Casier judiciaire', 'weight' => 6],

            // Scolarité et éducation
            ['term' => 'Bac', 'synonym' => 'Baccalauréat', 'weight' => 8],
            ['term' => 'Baccalauréat', 'synonym' => 'Bac', 'weight' => 8],
            ['term' => 'Diplôme', 'synonym' => 'Certificat', 'weight' => 6],

            // Santé
            ['term' => 'Vaccination', 'synonym' => 'Carnet vaccinal', 'weight' => 7],
            ['term' => 'Carnet vaccinal', 'synonym' => 'Vaccination', 'weight' => 7],
            ['term' => 'Visa médical', 'synonym' => 'Visite médicale', 'weight' => 6],
        ];

        foreach ($synonymes as $item) {
            Synonyme::firstOrCreate(
                [
                    'term' => $item['term'],
                    'synonym' => $item['synonym'],
                ],
                [
                    'weight' => $item['weight'] ?? 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
