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
        Schema::table('encounters', function (Blueprint $table) {
            $table->string('lab_results_file_path', 255)->nullable()->after('treatment');
            $table->string('lab_results_original_filename', 255)->nullable()->after('lab_results_file_path');
            $table->string('lab_results_mime_type', 100)->nullable()->after('lab_results_original_filename');

            $table->string('operative_work_file_path', 255)->nullable()->after('lab_results_mime_type');
            $table->string('operative_work_original_filename', 255)->nullable()->after('operative_work_file_path');
            $table->string('operative_work_mime_type', 100)->nullable()->after('operative_work_original_filename');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropColumn([
                'lab_results_file_path',
                'lab_results_original_filename',
                'lab_results_mime_type',
                'operative_work_file_path',
                'operative_work_original_filename',
                'operative_work_mime_type',
            ]);
        });
    }
};
