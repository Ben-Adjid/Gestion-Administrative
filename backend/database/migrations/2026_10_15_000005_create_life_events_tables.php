<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parcours de vie (« J'ai un bébé », « Je me marie »…) : suite ordonnée de démarches.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('life_events', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('title', 150);
            $table->string('title_en', 150)->nullable();
            $table->string('title_ar', 150)->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('icon', 50)->default('sparkles');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('life_event_demarche', function (Blueprint $table) {
            $table->foreignId('life_event_id')->constrained('life_events')->cascadeOnDelete();
            $table->foreignId('demarche_id')->constrained('demarches')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->primary(['life_event_id', 'demarche_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('life_event_demarche');
        Schema::dropIfExists('life_events');
    }
};
