<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Calendrier administratif : inscriptions électorales, rentrée, dates limites d'examens…
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('title_en', 200)->nullable();
            $table->string('title_ar', 200)->nullable();
            $table->text('description')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->enum('category', ['elections', 'education', 'exams', 'identity', 'tax', 'other'])->default('other');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->foreignId('demarche_id')->nullable()->constrained('demarches')->nullOnDelete();
            $table->string('source_url', 255)->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->index(['is_published', 'starts_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
