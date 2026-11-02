<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demarches', function (Blueprint $table) {
            $table->string('validity', 255)->nullable()->after('processing_time');
            $table->string('validity_en', 255)->nullable()->after('validity');
            $table->string('validity_ar', 255)->nullable()->after('validity_en');
        });
    }

    public function down(): void
    {
        Schema::table('demarches', function (Blueprint $table) {
            $table->dropColumn(['validity', 'validity_en', 'validity_ar']);
        });
    }
};
