<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_number' => 'EMP-' . $this->faker->unique()->numerify('########'),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'department' => $this->faker->randomElement([
                'Engineering', 'Finance', 'Support', 'Customer Success',
                'Human Resources', 'Marketing', 'Sales', 'Operations'
            ]),
            'salary' => $this->faker->numberBetween(30000, 150000),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP', 'ZAR', 'KES', 'UGX', 'TZS', 'RWF', 'NGN']),
            'country_code' => $this->faker->randomElement(['US', 'GB', 'ZA', 'KE', 'UG', 'TZ', 'RW', 'NG']),
            'start_date' => $this->faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'last_imported_at' => now(),
            'last_import_job_id' => null,
        ];
    }
}
