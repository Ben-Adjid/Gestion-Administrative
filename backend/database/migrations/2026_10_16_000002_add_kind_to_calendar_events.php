<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Type d'échéance du calendrier :
 *  - period   : période avec début et fin (ex. inscriptions du 1er au 30 novembre) ;
 *  - deadline : date limite à ne pas dépasser (ex. dépôt des dossiers avant le 15 décembre) ;
 *  - event    : événement à une date unique (ex. jour du scrutin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->enum('kind', ['period', 'deadline', 'event'])->default('event')->after('category');
        });

        // Données existantes : une fin renseignée signifie une période.
        DB::table('calendar_events')->whereNotNull('ends_on')->update(['kind' => 'period']);
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }
};
