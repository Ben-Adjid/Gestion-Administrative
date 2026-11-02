<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemarcheSeeder extends Seeder
{
    public function run(): void
    {
        if ($this->command) {
            $this->command->warn('DemarcheSeeder est désactivé : ses anciennes données de démonstration contenaient des coûts, délais et pièces inventés. Utilisez DatabaseSeeder pour les données vérifiées.');
        }
    }
}
