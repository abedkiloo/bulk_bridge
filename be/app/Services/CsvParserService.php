<?php

namespace App\Services;

use League\Csv\Reader;
use League\Csv\Statement;
use League\Csv\Exception;
use Illuminate\Support\Facades\Log;

class CsvParserService
{
    /**
     * Parse CSV file and return array of rows
     */
    public function parseFile(string $filePath): array
    {
        try {
            if (!file_exists($filePath)) {
                throw new \Exception("File not found: {$filePath}");
            }

            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0); // First row is header
            
            // Configure CSV settings for better compatibility
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');
            
            $records = Statement::create()->process($csv);
            
            $data = [];
            $headers = $records->getHeader();
            
            // Add header row at the beginning
            $data[] = array_values($headers);
            
            foreach ($records as $record) {
                $data[] = array_values($record);
            }
            
            Log::info("CSV file parsed successfully", [
                'file_path' => $filePath,
                'total_rows' => count($data) - 1, // Exclude header
                'headers' => array_keys($headers)
            ]);
            
            return $data;
            
        } catch (Exception $e) {
            Log::error("CSV parsing failed", [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("CSV parsing failed: " . $e->getMessage());
        }
    }

    /**
     * Validate CSV file structure
     */
    public function validateFile(string $filePath): array
    {
        $errors = [];
        
        try {
            if (!file_exists($filePath)) {
                $errors[] = "File not found: {$filePath}";
                return $errors;
            }
            
            $fileSize = filesize($filePath);
            $maxSize = 20 * 1024 * 1024; // 20MB
            
            if ($fileSize > $maxSize) {
                $errors[] = "File size exceeds maximum allowed size of 20MB";
            }
            
            if ($fileSize === 0) {
                $errors[] = "File is empty";
                return $errors;
            }
            
            // Check file extension
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (!in_array($extension, ['csv', 'txt'])) {
                $errors[] = "File must be a CSV or TXT file";
            }
            
            // Try to parse a few rows to validate structure
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            // Configure CSV settings for better compatibility
            $csv->setDelimiter(',');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');
            
            $records = Statement::create()->limit(5)->process($csv);
            $headers = $records->getHeader();
            $headerCount = count($headers);
            
            if ($headerCount === 0) {
                $errors[] = "CSV file has no headers";
            } else {
                // Check for minimum required columns
                if ($headerCount < 9) {
                    $errors[] = "CSV file must have at least 9 columns (found {$headerCount})";
                }
                
                // Check for expected headers
                $expectedHeaders = [
                    'employee_number', 'first_name', 'last_name', 'email', 
                    'department', 'salary', 'currency', 'country_code', 'start_date'
                ];
                
                $missingHeaders = array_diff($expectedHeaders, $headers);
                if (!empty($missingHeaders)) {
                    $errors[] = "Missing required headers: " . implode(', ', $missingHeaders);
                }
                
                // Check for extra headers
                $extraHeaders = array_diff($headers, $expectedHeaders);
                if (!empty($extraHeaders)) {
                    $errors[] = "Unexpected headers found: " . implode(', ', $extraHeaders);
                }
            }
            
        } catch (Exception $e) {
            $errors[] = "CSV file validation failed: " . $e->getMessage();
        }
        
        return $errors;
    }

    /**
     * Get file statistics
     */
    public function getFileStatistics(string $filePath): array
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            $records = Statement::create()->process($csv);
            $headers = $records->getHeader();
            
            $rowCount = 0;
            foreach ($records as $record) {
                $rowCount++;
            }
            
            return [
                'file_path' => $filePath,
                'file_size' => filesize($filePath),
                'headers' => $headers,
                'header_count' => count($headers),
                'row_count' => $rowCount,
                'estimated_memory_usage' => $this->estimateMemoryUsage($filePath),
            ];
            
        } catch (Exception $e) {
            throw new \Exception("Failed to get file statistics: " . $e->getMessage());
        }
    }

    /**
     * Estimate memory usage for processing the file
     */
    private function estimateMemoryUsage(string $filePath): int
    {
        $fileSize = filesize($filePath);
        
        // Rough estimate: CSV processing typically uses 3-5x the file size in memory
        return $fileSize * 4;
    }

    /**
     * Stream CSV file for large files (memory efficient)
     */
    public function streamFile(string $filePath, callable $callback): void
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            $records = Statement::create()->process($csv);
            
            foreach ($records as $record) {
                $callback($record);
            }
            
        } catch (Exception $e) {
            throw new \Exception("CSV streaming failed: " . $e->getMessage());
        }
    }

    /**
     * Get sample data from CSV file
     */
    public function getSampleData(string $filePath, int $sampleSize = 5): array
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            $records = Statement::create()->limit($sampleSize)->process($csv);
            
            $sample = [];
            foreach ($records as $record) {
                $sample[] = $record;
            }
            
            return [
                'headers' => $records->getHeader(),
                'sample_rows' => $sample,
                'sample_size' => count($sample)
            ];
            
        } catch (Exception $e) {
            throw new \Exception("Failed to get sample data: " . $e->getMessage());
        }
    }
}
