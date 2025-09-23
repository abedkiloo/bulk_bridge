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
        Schema::create('import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_job_id')->constrained('import_jobs')->onDelete('cascade');
            $table->foreignId('import_row_id')->nullable()->constrained('import_rows')->onDelete('cascade');
            $table->integer('row_number')->nullable();
            $table->enum('error_type', ['validation', 'duplicate', 'system', 'business_logic']);
            $table->string('error_code');
            $table->text('error_message');
            $table->json('error_context')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
            
            // Indexes for error analysis and reporting
            $table->index(['import_job_id', 'error_type']);
            $table->index(['error_type', 'error_code']);
            $table->index(['import_job_id', 'row_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_errors');
    }
};
