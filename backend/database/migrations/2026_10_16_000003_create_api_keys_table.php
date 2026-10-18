<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clés API des partenaires (cahier des charges, module 9) : accès sans limite de débit + statistiques d'usage.
 * Seule l'empreinte SHA-256 de la clé est stockée ; la clé en clair n'est montrée qu'une fois à la création.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('key_prefix', 12);
            $table->string('key_hash', 64)->unique();
            $table->unsignedBigInteger('requests_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
