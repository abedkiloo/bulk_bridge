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
            $table->uuid('import_job_id');
            $table->integer('row_number');
            $table->json('raw_data');
            $table->enum('error_type', ['validation', 'duplicate', 'system']);
            $table->string('error_code');
            $table->text('error_message');
            $table->json('error_details')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['import_job_id', 'error_type']);
            $table->index(['import_job_id', 'row_number']);
            
            // Foreign key constraint
            $table->foreign('import_job_id')->references('uuid')->on('import_jobs')->onDelete('cascade');
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