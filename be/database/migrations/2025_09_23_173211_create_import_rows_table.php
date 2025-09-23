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
        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_job_id')->constrained('import_jobs')->onDelete('cascade');
            $table->integer('row_number');
            $table->json('raw_data');
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'duplicate', 'skipped'])->default('pending');
            $table->string('employee_id')->nullable();
            $table->text('error_message')->nullable();
            $table->json('validation_errors')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Indexes for performance and idempotency
            $table->index(['import_job_id', 'row_number']);
            $table->index(['import_job_id', 'status']);
            $table->index(['employee_id', 'import_job_id']);
            $table->unique(['import_job_id', 'row_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_rows');
    }
};
