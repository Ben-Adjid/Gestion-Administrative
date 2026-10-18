<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixDemarchesDataSeeder extends Seeder
{
    public function run(): void
    {
        // Corriger TOUTES les démarches avec guillemets cassés
        $demarches = DB::table('demarches')->get();

        foreach ($demarches as $demarche) {
            DB::table('demarches')->where('id', $demarche->id)->update([
                'cost' => str_replace("'", "'", $demarche->cost),
                'institution' => str_replace("'", "'", $demarche->institution),
                'location' => str_replace("'", "'", $demarche->location),
                'contact_email' => str_replace("'", "'", $demarche->contact_email),
                'contact_phone' => str_replace("'", "'", $demarche->contact_phone),
                'official_source_url' => str_replace("'", "'", $demarche->official_source_url),
                'processing_time' => str_replace("'", "'", $demarche->processing_time),
            ]);
        }

        // Corriger la démarche ID 5 (Permis de conduire) specifically
        DB::table('demarches')->where('id', 5)->update([
            'cost' => '45 000 FCFA',
            'cost_amount' => 45000,
            'processing_time' => '15 - 30 jours',
            'processing_time_days' => 20,
            'institution' => 'Direction générale des transports routiers',
            'location' => "Direction des Transports, Quartier Chagoua, N'Djamena",
            'contact_email' => 'transports@gouv.td',
            'contact_phone' => '+235 22 52 80 90',
            'official_source_url' => 'https://transports.gouv.td',
            'status' => 'published',
        ]);

        echo "✅ Toutes les données JSON corrigées avec succès !";
    }
}
