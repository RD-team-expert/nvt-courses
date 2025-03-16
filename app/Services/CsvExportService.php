<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    /**
     * Export data to CSV file
     *
     * @param string $filename
     * @param array $headers
     * @param array $data
     * @return StreamedResponse
     */
    public function export(string $filename, array $headers, array $data): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($headers, $data) {
            $handle = fopen('php://output', 'w');
            
            // Add UTF-8 BOM to fix Excel encoding issues
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add headers
            fputcsv($handle, $headers);
            
            // Add data rows
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
            
            fclose($handle);
        });
        
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        return $response;
    }
}