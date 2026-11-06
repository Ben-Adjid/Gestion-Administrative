<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Date et heure de publication programmée d'une fiche (vide : pas de programmation). */
    public function up(): void
    {
        if (!Schema::hasColumn('demarches', 'scheduled_publish_at')) {
            Schema::table('demarches', function (Blueprint $table) {
                $table->timestamp('scheduled_publish_at')->nullable()->after('last_verified_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('demarches', 'scheduled_publish_at')) {
            Schema::table('demarches', function (Blueprint $table) {
                $table->dropColumn('scheduled_publish_at');
            });
        }
    }
};
