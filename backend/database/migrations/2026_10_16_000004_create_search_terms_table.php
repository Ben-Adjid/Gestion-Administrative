<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Termes recherchés dans le catalogue (cahier des charges, module 10 : « démarches les plus recherchées »).
 * Seul le terme est conservé, jamais l'adresse IP ni l'identité du visiteur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_terms', function (Blueprint $table) {
            $table->id();
            $table->string('term', 100)->unique();
            $table->unsignedInteger('count')->default(1);
            $table->unsignedInteger('last_results')->default(0);
            $table->timestamp('last_searched_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_terms');
    }
};
