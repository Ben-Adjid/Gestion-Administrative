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
        Schema::create('signalements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demarche_id')->nullable()->constrained('demarches')->onDelete('cascade');
            $table->string('type', 50); // 'info_erronee', 'demarche_obsolete', 'lien_mort', 'cout_different', 'autre'
            $table->string('author_name', 100)->nullable();
            $table->string('author_email', 150)->nullable();
            $table->string('author_phone', 50)->nullable();
            $table->text('message');
            $table->string('ip_address', 45); // Pour le rate limiting et la sécurité
            $table->string('user_agent', 255)->nullable();
            
            // Workflow de modération
            $table->enum('status', ['pending', 'investigating', 'resolved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index('ip_address');
            $table->index('demarche_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signalements');
    }
};
