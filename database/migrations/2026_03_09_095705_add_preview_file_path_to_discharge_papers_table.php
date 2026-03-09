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
        Schema::table('discharge_papers', function (Blueprint $table) {
            $table->string('preview_file_path')->nullable()->after('qr_file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discharge_papers', function (Blueprint $table) {
            $table->dropColumn('preview_file_path');
        });
    }
};
