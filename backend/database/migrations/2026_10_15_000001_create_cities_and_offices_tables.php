<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Villes du Tchad et bureaux (guichets) où l'on effectue les démarches.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('name_ar', 100)->nullable();
            $table->string('slug', 100)->unique();
            $table->string('province', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('name_en', 200)->nullable();
            $table->string('name_ar', 200)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('address_ar', 255)->nullable();
            $table->string('opening_hours', 150)->nullable();
            $table->string('opening_hours_en', 150)->nullable();
            $table->string('opening_hours_ar', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('demarche_office', function (Blueprint $table) {
            $table->foreignId('demarche_id')->constrained('demarches')->cascadeOnDelete();
            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->primary(['demarche_id', 'office_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demarche_office');
        Schema::dropIfExists('offices');
        Schema::dropIfExists('cities');
    }
};
