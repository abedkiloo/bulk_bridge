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
            $table->string('employee_number')->unique()->index();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique()->index();
            $table->string('department');
            $table->decimal('salary', 15, 2);
            $table->string('currency', 3);
            $table->string('country_code', 2);
            $table->date('start_date');
            $table->timestamp('last_imported_at')->nullable();
            $table->string('last_import_job_id')->nullable();
            $table->timestamps();
            
            // Composite indexes for performance
            $table->index(['email', 'employee_number']);
            $table->index(['department', 'country_code']);
            $table->index(['start_date', 'salary']);
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
