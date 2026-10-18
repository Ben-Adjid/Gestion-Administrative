<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retours d'expérience des citoyens sur une démarche (prix réellement payé, délai réel…),
 * publiés après modération.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demarche_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demarche_id')->constrained('demarches')->cascadeOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->date('visit_date');
            $table->unsignedInteger('price_paid')->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('comment');
            $table->string('author_name', 100)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('ip_hash', 64)->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
            $table->index(['demarche_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demarche_reviews');
    }
};
