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
        Schema::table('patient_medical_info', function (Blueprint $table) {
            if (! Schema::hasColumn('patient_medical_info', 'has_allergies')) {
                $table->boolean('has_allergies')->default(false)->after('weight_kg');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_medical_info', function (Blueprint $table) {
            $table->dropColumn('has_allergies');
        });
    }
};
