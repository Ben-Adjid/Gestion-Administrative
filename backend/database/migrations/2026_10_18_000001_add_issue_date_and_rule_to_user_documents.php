<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Date de délivrance (saisie par le citoyen) et règle de validité officielle appliquée.
 * expiration_date reste obligatoire : pour un document sans règle sourcée, elle est saisie telle quelle.
 * Migration additive : aucune donnée existante n'est modifiée ni supprimée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_documents', function (Blueprint $table) {
            $table->date('issue_date')->nullable()->after('expiration_date');
            $table->foreignId('validity_rule_id')->nullable()->after('issue_date')
                ->constrained('document_validity_rules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validity_rule_id');
            $table->dropColumn('issue_date');
        });
    }
};
