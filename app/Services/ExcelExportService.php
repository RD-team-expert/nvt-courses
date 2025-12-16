<?php

namespace App\Services;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExcelExportService
{
    /**
     * Export course progress data to Excel with 2 sheets
     * Sheet 1: Completed Courses (KPI) - People who finished
     * Sheet 2: Non-Completed Courses (KPI) - People who didn't finish
     * 
     * @param Collection $completedData
     * @param Collection $nonCompletedData
     * @return BinaryFileResponse
     */
    public function exportCourseProgress(
        Collection $completedData,
        Collection $nonCompletedData
    ): BinaryFileResponse {
        // Create new spreadsheet
        $spreadsheet = new Spreadsheet();
        
        // Create first sheet for completed courses
        $completedSheet = $spreadsheet->getActiveSheet();
        $completedSheet->setTitle('Completed Courses (KPI)');
        $this->formatWorksheet($completedSheet, $completedData, 'Completed Courses (KPI)');
        
        // Create second sheet for non-completed courses
        $nonCompletedSheet = $spreadsheet->createSheet();
        $nonCompletedSheet->setTitle('Non-Completed Courses (KPI)');
        $this->formatWorksheet($nonCompletedSheet, $nonCompletedData, 'Non-Completed Courses (KPI)');
        
        // Generate filename with timestamp
        $filename = 'user_course_progress_' . date('Y-m-d_H-i-s') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        
        // Write to file
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);
        
        // Return as download
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Format a worksheet with headers and data
     * 
     * LEARNING SCORE FORMULA:
     * (completion_rate × 0.25) + (progress × 0.25) + (attention × 0.25) + (quiz × 0.25) - suspicious_penalty
     * 
     * Where:
     * - completion_rate: 100% if completed, 0% if not
     * - progress: Current progress percentage (0-100)
     * - attention: Attention score from learning sessions (0-100)
     * - quiz: Average quiz score percentage (0-100)
     * - suspicious_penalty: (suspicious_sessions / total_sessions) × 10
     * 
     * @param Worksheet $sheet
     * @param Collection $data
     * @param string $sheetName
     * @return void
     */
    private function formatWorksheet(
        Worksheet $sheet,
        Collection $data,
        string $sheetName
    ): void {
        // Define column headers (NO Employee Code as per client request)
        // Added "Start Course" column as requested
        $headers = [
            'A' => 'Employee Name',
            'B' => 'Department',
            'C' => 'Course type',
            'D' => 'Course Name',
            'E' => 'Completion Status (Completed/In Progress/Overdue)',
            'F' => 'DaysOverdue',
            'G' => 'progress%',
            'H' => 'Start Course',
            'I' => 'Completion Date',
            'J' => 'Overall Learning Score (0-100)',
            'K' => 'Score Band (Excellent / Good / Needs Attention)',
            'L' => 'Compliance Status (Compliant/At Risk/Non-Compliant)',
        ];
        
        // Set headers in first row with styling
        foreach ($headers as $col => $header) {
            $sheet->setCellValue($col . '1', $header);
        }
        
        // Style header row - Dark blue background with white text (matching client's design)
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A5F'], // Dark blue like client's image
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
        
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        
        // Add data rows
        $row = 2;
        foreach ($data as $assignment) {
            // A: Employee Name
            $sheet->setCellValue('A' . $row, $assignment['user_name'] ?? '');
            
            // B: Department
            $sheet->setCellValue('B' . $row, $assignment['department'] ?? '');
            
            // C: Course type
            $sheet->setCellValue('C' . $row, ucfirst($assignment['course_type'] ?? ''));
            
            // D: Course Name
            $sheet->setCellValue('D' . $row, $assignment['course_name'] ?? '');
            
            // E: Completion Status
            $sheet->setCellValue('E' . $row, $assignment['completion_status'] ?? '');
            
            // F: Days overdue - numeric or empty
            $daysOverdue = $assignment['days_overdue'] ?? null;
            if ($daysOverdue !== null && $daysOverdue > 0) {
                $sheet->setCellValue('F' . $row, $daysOverdue);
            } else {
                $sheet->setCellValue('F' . $row, '');
            }
            
            // G: Progress percentage
            $progress = $assignment['progress_percentage'] ?? 0;
            $sheet->setCellValue('G' . $row, $progress . '%');
            
            // H: Start Course Date (NEW COLUMN)
            $startDate = $assignment['started_date'] ?? '';
            $sheet->setCellValue('H' . $row, $startDate);
            
            // I: Completion date
            $completionDate = $assignment['completion_date'] ?? '';
            $sheet->setCellValue('I' . $row, $completionDate);
            
            // J: Learning score - numeric
            $sheet->setCellValue('J' . $row, $assignment['learning_score'] ?? 0);
            
            // K: Score band
            $sheet->setCellValue('K' . $row, $assignment['score_band'] ?? '');
            
            // L: Compliance status
            $sheet->setCellValue('L' . $row, $assignment['compliance_status'] ?? '');
            
            // Alternate row colors for readability (like client's design)
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':L' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'B8CCE4'], // Light blue alternate row
                    ],
                ]);
            }
            
            $row++;
        }
        
        // Apply borders to all data cells
        if ($row > 2) {
            $sheet->getStyle('A1:L' . ($row - 1))->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);
        }
        
        // Set column widths for better readability
        $sheet->getColumnDimension('A')->setWidth(20);  // Employee Name
        $sheet->getColumnDimension('B')->setWidth(15);  // Department
        $sheet->getColumnDimension('C')->setWidth(12);  // Course type
        $sheet->getColumnDimension('D')->setWidth(25);  // Course Name
        $sheet->getColumnDimension('E')->setWidth(20);  // Completion Status
        $sheet->getColumnDimension('F')->setWidth(12);  // DaysOverdue
        $sheet->getColumnDimension('G')->setWidth(12);  // progress%
        $sheet->getColumnDimension('H')->setWidth(15);  // Start Course
        $sheet->getColumnDimension('I')->setWidth(15);  // Completion Date
        $sheet->getColumnDimension('J')->setWidth(18);  // Learning Score
        $sheet->getColumnDimension('K')->setWidth(20);  // Score Band
        $sheet->getColumnDimension('L')->setWidth(20);  // Compliance Status
        
        // Center align certain columns
        if ($row > 2) {
            $sheet->getStyle('C2:C' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E2:E' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F2:F' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G2:G' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H2:H' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('I2:I' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('J2:J' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('K2:K' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('L2:L' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        
        // Add filter dropdown to headers (like client's design shows)
        $sheet->setAutoFilter('A1:L1');
        
        // If no data, add a message
        if ($data->isEmpty()) {
            $sheet->setCellValue('A2', 'No data available');
            $sheet->mergeCells('A2:L2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
    }
}
