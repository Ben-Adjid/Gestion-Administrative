<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Categorie;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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

        $this->call(CahierDemarchesSeeder::class);
        $this->call(CahierPhase2DraftsSeeder::class);

        \Illuminate\Support\Facades\Artisan::call('catalogue:normalize', ['--apply' => true]);
        $this->call(ReferenceDataSeeder::class);
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

        $this->command?->info('✅ Données créées avec succès!');
    }
}
