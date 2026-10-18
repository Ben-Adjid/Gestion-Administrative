<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Référentiel géographique national (province → département → commune → localité) et services des bureaux.
 *  - geo_units : chaque unité porte son statut (official | to_verify), sa source et sa date de vérification.
 *    Rien n'est importé sans source : les lignes existantes restent « à vérifier ».
 *  - office_services : un bureau ne propose une démarche que si ce lien existe, avec son propre statut
 *    (confirmed | press_reported | to_verify). Aucune démarche n'est déduite de la seule présence d'un bureau.
 * Migration additive : aucune donnée supprimée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('geo_units')->nullOnDelete();
            $table->enum('level', ['province', 'department', 'commune', 'locality']);
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('slug', 120)->unique();
            $table->enum('status', ['official', 'to_verify'])->default('to_verify');
            $table->string('source_label')->nullable();
            $table->string('source_url')->nullable();
            $table->date('verified_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['level', 'parent_id']);
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->foreignId('geo_unit_id')->nullable()->after('province')->constrained('geo_units')->nullOnDelete();
        });

        Schema::create('office_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('demarche_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['confirmed', 'press_reported', 'to_verify'])->default('to_verify');
            $table->string('source_label')->nullable();
            $table->string('source_url')->nullable();
            $table->date('verified_on')->nullable();
            $table->timestamps();

            $table->unique(['office_id', 'demarche_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_services');

        Schema::table('cities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('geo_unit_id');
        });

        Schema::dropIfExists('geo_units');
    }
};
