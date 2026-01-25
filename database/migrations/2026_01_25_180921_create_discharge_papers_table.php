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
        Schema::create('discharge_papers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->constrained('encounters')->cascadeOnDelete()->unique();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->string('original_file_path', 500);
            $table->string('original_filename');
            $table->string('qr_file_path', 500);
            $table->string('qr_token', 100)->unique();
            $table->string('mime_type', 100);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('patient_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discharge_papers');
    }
};
