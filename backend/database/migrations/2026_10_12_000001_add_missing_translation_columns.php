<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('name_en', 100)->nullable()->after('name');
            $table->string('name_ar', 100)->nullable()->after('name_en');
            $table->text('description_en')->nullable()->after('description');
            $table->text('description_ar')->nullable()->after('description_en');
        });

        Schema::table('demarches', function (Blueprint $table) {
            $table->string('processing_time_en', 100)->nullable()->after('processing_time');
            $table->string('processing_time_ar', 100)->nullable()->after('processing_time_en');
            $table->string('location_en', 255)->nullable()->after('location');
            $table->string('location_ar', 255)->nullable()->after('location_en');
        });
    }

    public function down(): void
    {
        Schema::table('demarches', function (Blueprint $table) {
            $table->dropColumn(['processing_time_en', 'processing_time_ar', 'location_en', 'location_ar']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['name_en', 'name_ar', 'description_en', 'description_ar']);
        });
    }
};
