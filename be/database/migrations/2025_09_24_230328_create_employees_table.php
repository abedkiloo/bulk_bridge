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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('department');
            $table->decimal('salary', 15, 2);
            $table->string('currency', 3);
            $table->string('country_code', 2);
            $table->date('start_date');
            $table->uuid('import_job_id')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['employee_number']);
            $table->index(['email']);
            $table->index(['department', 'salary']);
            $table->index(['import_job_id']);
            
            // Foreign key constraint
            $table->foreign('import_job_id')->references('uuid')->on('import_jobs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};