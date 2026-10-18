<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Questions-réponses publiques : questions des visiteurs, réponses de l'équipe ou de la communauté,
 * toutes publiées après modération.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demarche_id')->nullable()->constrained('demarches')->nullOnDelete();
            $table->text('body');
            $table->string('author_name', 100)->nullable();
            $table->string('author_email', 150)->nullable();
            $table->string('language', 2)->default('fr');
            $table->enum('status', ['pending', 'published', 'rejected'])->default('pending');
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->text('body');
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('author_name', 100)->nullable();
            $table->boolean('is_official')->default(false);
            $table->enum('status', ['pending', 'published', 'rejected'])->default('pending');
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
            $table->index(['question_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
        Schema::dropIfExists('questions');
    }
};
