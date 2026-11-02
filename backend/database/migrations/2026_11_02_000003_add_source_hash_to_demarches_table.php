<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Empreinte de la fiche source (cahier des charges) : le seeder ne réécrit une fiche que si son contenu source a changé,
 * ce qui évite de ré-enregistrer à chaque relance les corrections sourcées de l'équipe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demarches', function (Blueprint $table) {
            $table->string('source_hash', 64)->nullable()->after('validity_ar');
        });
    }

    public function down(): void
    {
        Schema::table('demarches', function (Blueprint $table) {
            $table->dropColumn('source_hash');
        });
    }
};
