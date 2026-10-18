<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ambassades et consulats du Tchad à l'étranger (rubrique diaspora).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('representations', function (Blueprint $table) {
            $table->id();
            $table->string('country', 100);
            $table->string('country_en', 100)->nullable();
            $table->string('country_ar', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->enum('type', ['embassy', 'consulate', 'other'])->default('embassy');
            $table->string('address', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('website', 255)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('representations');
    }
};
