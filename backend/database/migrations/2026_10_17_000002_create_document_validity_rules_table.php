<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Règles de validité des documents administratifs (calendrier « validité des documents »).
 * Aucune durée sans source : validity_months reste null tant que la durée n'est pas sourcée.
 *  - status confirmed  : source officielle primaire (texte réglementaire, administration émettrice) ;
 *  - status secondary  : source officielle secondaire (autre administration, registre), datée, à reconfirmer ;
 *  - status to_verify  : aucune source trouvée — affiché comme « à vérifier », sans date d'expiration calculée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_validity_rules', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('name', 150);
            $table->string('name_en', 150)->nullable();
            $table->string('name_ar', 150)->nullable();
            $table->string('country_code', 2)->default('TD');
            $table->enum('category', ['identity', 'travel', 'transport', 'civil_status', 'justice', 'other'])->default('other');
            $table->foreignId('demarche_id')->nullable()->constrained('demarches')->nullOnDelete();
            $table->unsignedSmallInteger('validity_months')->nullable();
            $table->enum('validity_start', ['issue_date', 'not_applicable', 'unknown'])->default('unknown');
            $table->text('renewal')->nullable();
            $table->text('renewal_en')->nullable();
            $table->text('renewal_ar')->nullable();
            $table->text('notes')->nullable();
            $table->text('notes_en')->nullable();
            $table->text('notes_ar')->nullable();
            $table->enum('status', ['confirmed', 'secondary', 'to_verify'])->default('to_verify');
            $table->string('source_label', 255)->nullable();
            $table->string('source_url', 255)->nullable();
            $table->date('source_date')->nullable();
            $table->date('verified_on')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_validity_rules');
    }
};
