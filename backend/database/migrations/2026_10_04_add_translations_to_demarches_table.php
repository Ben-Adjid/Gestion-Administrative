<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demarches', function (Blueprint $table) {
            // Traductions en anglais
            $table->string('title_en')->nullable()->after('title');
            $table->text('summary_en')->nullable()->after('summary');
            $table->longText('description_en')->nullable()->after('description');
            $table->string('cost_en')->nullable()->after('cost');
            $table->json('required_documents_en')->nullable()->after('required_documents');
            $table->json('procedure_steps_en')->nullable()->after('procedure_steps');
            $table->json('conditions_en')->nullable()->after('conditions');

            // Traductions en arabe
            $table->string('title_ar')->nullable()->after('title_en');
            $table->text('summary_ar')->nullable()->after('summary_en');
            $table->longText('description_ar')->nullable()->after('description_en');
            $table->string('cost_ar')->nullable()->after('cost_en');
            $table->json('required_documents_ar')->nullable()->after('required_documents_en');
            $table->json('procedure_steps_ar')->nullable()->after('procedure_steps_en');
            $table->json('conditions_ar')->nullable()->after('conditions_en');

            // Colonne pour l'institution traduite
            $table->string('institution_en')->nullable()->after('institution');
            $table->string('institution_ar')->nullable()->after('institution_en');
        });
    }

    public function down(): void
    {
        Schema::table('demarches', function (Blueprint $table) {
            $table->dropColumn([
                'title_en',
                'summary_en',
                'description_en',
                'cost_en',
                'required_documents_en',
                'procedure_steps_en',
                'conditions_en',
                'title_ar',
                'summary_ar',
                'description_ar',
                'cost_ar',
                'required_documents_ar',
                'procedure_steps_ar',
                'conditions_ar',
                'institution_en',
                'institution_ar',
            ]);
        });
    }
};
