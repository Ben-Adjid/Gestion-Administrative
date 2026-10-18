<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Localités signalées par les visiteurs et absentes du référentiel. Un signalement n'ajoute jamais une
 * localité par lui-même : un administrateur la vérifie, puis la crée avec sa source.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geo_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('province', 120)->nullable();
            $table->string('comment', 500)->nullable();
            $table->string('contact_email', 190)->nullable();
            $table->enum('status', ['new', 'accepted', 'rejected', 'duplicate'])->default('new');
            $table->string('admin_note', 500)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_suggestions');
    }
};
