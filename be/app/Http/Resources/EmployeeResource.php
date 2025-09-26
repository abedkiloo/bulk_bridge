<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_number' => $this->employee_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'department' => $this->department,
            'salary' => $this->salary,
            'currency' => $this->currency,
            'country_code' => $this->country_code,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Additional computed fields
            'full_name' => trim($this->first_name . ' ' . $this->last_name),
            'formatted_salary' => $this->salary ? number_format($this->salary, 0, '.', ',') : null,
            'years_of_service' => $this->start_date ? $this->start_date->diffInYears(now()) : null,
        ];
    }
}
