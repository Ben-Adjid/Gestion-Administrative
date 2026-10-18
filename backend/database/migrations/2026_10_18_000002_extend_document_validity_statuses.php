<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nouveaux statuts de règle, sans perte de données :
 *  - business_requirement : exigence métier définie par l'équipe, NON confirmée par une source officielle ;
 *  - permanent            : validité permanente (aucune date d'expiration) ;
 *  - status_based         : validité liée à un statut (ex. mariage tant qu'il subsiste).
 * Les règles existantes gardent leur statut (confirmed, secondary, to_verify).
 */
return new class extends Migration
{
    private const STATUSES = ['confirmed', 'secondary', 'to_verify', 'business_requirement', 'permanent', 'status_based'];

    public function up(): void
    {
        Schema::table('document_validity_rules', function (Blueprint $table) {
            $table->enum('status', self::STATUSES)->default('to_verify')->change();
        });
    }

    public function down(): void
    {
        // Retour arrière sûr : les règles ajoutées depuis reviennent à « à vérifier » avant de restreindre l'enum.
        \Illuminate\Support\Facades\DB::table('document_validity_rules')
            ->whereIn('status', ['business_requirement', 'permanent', 'status_based'])
            ->update(['status' => 'to_verify']);

        Schema::table('document_validity_rules', function (Blueprint $table) {
            $table->enum('status', ['confirmed', 'secondary', 'to_verify'])->default('to_verify')->change();
        });
    }
};
