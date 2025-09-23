<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ImportJob>
 */
class ImportJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => $this->faker->uuid(),
            'filename' => $this->faker->uuid() . '.csv',
            'original_filename' => $this->faker->word() . '_employees.csv',
            'file_path' => '/storage/imports/' . $this->faker->uuid() . '.csv',
            'total_rows' => $this->faker->numberBetween(10, 1000),
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'duplicate_rows' => 0,
            'status' => 'pending',
            'metadata' => [
                'file_size' => $this->faker->numberBetween(1000, 1000000),
                'headers' => [
                    'employee_number', 'first_name', 'last_name', 'email',
                    'department', 'salary', 'currency', 'country_code', 'start_date'
                ],
                'uploaded_at' => now()->toISOString(),
            ],
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
