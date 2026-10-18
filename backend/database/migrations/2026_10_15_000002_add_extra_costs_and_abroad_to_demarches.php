<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Frais annexes (photos, timbres…) pour estimer le coût total, et informations pour la diaspora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demarches', function (Blueprint $table) {
            // [{"label": "...", "label_en": "...", "label_ar": "...", "amount": 2000}]
            $table->json('extra_costs')->nullable()->after('cost_amount');
            $table->boolean('abroad_available')->default(false)->after('online_url');
            $table->text('abroad_info')->nullable()->after('abroad_available');
            $table->text('abroad_info_en')->nullable()->after('abroad_info');
            $table->text('abroad_info_ar')->nullable()->after('abroad_info_en');
        });
    }

    public function down(): void
    {
        Schema::table('demarches', function (Blueprint $table) {
            $table->dropColumn(['extra_costs', 'abroad_available', 'abroad_info', 'abroad_info_en', 'abroad_info_ar']);
        });
    }
};
