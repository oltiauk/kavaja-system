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
            $table->dropColumn('surgical_notes');
        });

        Schema::table('encounters', function (Blueprint $table) {
            $table->string('surgical_notes_file_path', 500)->nullable()->after('treatment');
            $table->string('surgical_notes_original_filename')->nullable()->after('surgical_notes_file_path');
            $table->string('surgical_notes_mime_type', 100)->nullable()->after('surgical_notes_original_filename');
            $table->unsignedBigInteger('surgical_notes_file_size')->nullable()->after('surgical_notes_mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('encounters', function (Blueprint $table) {
            $table->dropColumn([
                'surgical_notes_file_path',
                'surgical_notes_original_filename',
                'surgical_notes_mime_type',
                'surgical_notes_file_size',
            ]);
        });

        Schema::table('encounters', function (Blueprint $table) {
            $table->text('surgical_notes')->nullable()->after('treatment');
        });
    }
};
