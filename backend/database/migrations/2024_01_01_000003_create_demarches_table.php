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
        Schema::create('demarches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categorie_id')->constrained('categories')->onDelete('restrict');
            $table->string('title', 200);
            $table->string('slug', 220)->unique();
            $table->text('summary')->nullable();
            $table->longText('description');
            
            // Pièces à fournir, étapes, conditions stockées en JSON structuré
            $table->json('required_documents')->nullable(); // Array de documents nécessaires
            $table->json('procedure_steps')->nullable();     // Array d'étapes ordonnées
            $table->json('conditions')->nullable();          // Array de conditions d'éligibilité
            
            // Coût et délais
            $table->string('cost', 100)->default('Gratuit');
            $table->decimal('cost_amount', 12, 2)->nullable(); // Montant numérique en FCFA si applicable
            $table->string('processing_time', 100)->nullable(); // ex: "3 jours ouvrables"
            $table->unsignedInteger('processing_time_days')->nullable(); // pour filtres
            
            // Entité / Ministère responsable
            $table->string('institution', 200); // ex: "Direction Générale de l'Agence Nationale des Titres Sécurisés (ANATS)"
            $table->string('location', 255)->nullable(); // Adresse physique / Ville
            $table->string('contact_email', 150)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('official_source_url', 500)->nullable();
            
            // Statuts et visibilité
            $table->enum('status', ['draft', 'published', 'archived'])->default('published');
            $table->boolean('is_online')->default(false); // Démarche 100% faisable en ligne ou non
            $table->string('online_url', 500)->nullable();
            $table->unsignedBigInteger('views_count')->default(0);

            // Audit auteur
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes pour recherche et filtres performants
            $table->index('status');
            $table->index('categorie_id');
            $table->index(['status', 'categorie_id']);
            $table->index('is_online');
            $table->index('slug');
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->fullText(['title', 'summary', 'institution']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demarches');
    }
};
