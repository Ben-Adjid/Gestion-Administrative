<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            // Préférences notifications
            $table->boolean('notify_signalements')->default(true)->after('role');
            $table->boolean('notify_demarche_updates')->default(true)->after('notify_signalements');
            $table->boolean('notify_admin_reports')->default(true)->after('notify_demarche_updates');
            $table->boolean('notify_newsletter')->default(false)->after('notify_admin_reports');

            // Fréquence des notifications
            $table->enum('notification_frequency', ['immediate', 'daily', 'weekly', 'never'])->default('immediate')->after('notify_newsletter');

            // Dernier email envoyé (pour éviter les doublons)
            $table->timestamp('last_email_sent_at')->nullable()->after('notification_frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn([
                'notify_signalements',
                'notify_demarche_updates',
                'notify_admin_reports',
                'notify_newsletter',
                'notification_frequency',
                'last_email_sent_at',
            ]);
        });
    }
};
