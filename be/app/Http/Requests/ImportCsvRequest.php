<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCsvRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'csv' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:' . config('imports.max_file_size', 20 * 1024 * 1024)
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'csv.required' => 'A CSV file is required.',
            'csv.file' => 'The uploaded file must be a valid file.',
            'csv.mimes' => 'The file must be a CSV file.',
            'csv.max' => 'The file size must not exceed ' . $this->formatFileSize(config('imports.max_file_size', 20 * 1024 * 1024)) . '.',
        ];
    }

    /**
     * Format file size for display.
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
