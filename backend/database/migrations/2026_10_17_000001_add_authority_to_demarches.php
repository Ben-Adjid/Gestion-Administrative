<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lieu de dépôt selon la ville :
 *  - authority_level : organisme local (commune), régional (province, juridiction) ou national ;
 *  - location_city_id : ville à laquelle se rapporte le texte libre « location » (souvent N'Djaména).
 * Le site n'affiche ce texte que pour cette ville-là, jamais pour une autre ville choisie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demarches', function (Blueprint $table) {
            $table->enum('authority_level', ['local', 'regional', 'national'])->nullable()->after('institution_ar');
            $table->foreignId('location_city_id')->nullable()->after('location_ar')->constrained('cities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('demarches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_city_id');
            $table->dropColumn('authority_level');
        });
    }
};
