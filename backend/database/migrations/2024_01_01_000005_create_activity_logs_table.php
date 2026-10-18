<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('action', 100); // 'create_demarche', 'update_demarche', 'delete_demarche', 'login', etc.
            $table->string('subject_type', 150)->nullable(); // Nom du modèle (ex: Demarche::class)
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description', 255);
            $table->json('old_values')->nullable(); // Ancien état
            $table->json('new_values')->nullable(); // Nouvel état
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
