<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('synonymes', function (Blueprint $table) {
            $table->id();
            $table->string('term', 255)->index();
            $table->string('synonym', 255)->index();
            $table->text('description')->nullable();
            $table->integer('weight')->default(1)->comment('Importance du synonyme (1-10)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['term', 'synonym']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('synonymes');
    }
};
