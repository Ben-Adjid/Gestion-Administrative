<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un document sans date d'expiration (validité permanente ou liée à un statut) reste enregistré sans date :
 * aucune fausse échéance n'est créée. Les documents existants gardent leur date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_documents', function (Blueprint $table) {
            $table->date('expiration_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Retour arrière impossible sans date : on ne rétablit la contrainte que si aucun document n'est sans date.
        if (\Illuminate\Support\Facades\DB::table('user_documents')->whereNull('expiration_date')->exists()) {
            throw new \RuntimeException('Des documents sans date d’expiration existent : retour arrière refusé pour ne rien perdre.');
        }

        Schema::table('user_documents', function (Blueprint $table) {
            $table->date('expiration_date')->nullable(false)->change();
        });
    }
};
