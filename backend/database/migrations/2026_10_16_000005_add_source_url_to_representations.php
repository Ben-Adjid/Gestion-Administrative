<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Source officielle de chaque adresse d'ambassade ou de consulat (transparence, comme pour les bureaux). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('representations', function (Blueprint $table) {
            $table->string('source_url', 255)->nullable()->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('representations', function (Blueprint $table) {
            $table->dropColumn('source_url');
        });
    }
};
