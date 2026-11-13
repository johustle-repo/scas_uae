<?php

namespace App\Http\Controllers\Dogs;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\DogDataExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Download every dog record and its related data as an Excel workbook or PDF report.
     */
    public function __invoke(DogDataExport $export, string $format): StreamedResponse
    {
        AuditLogger::record('export.dogs', 'Exported all dog data ('.($format === 'pdf' ? 'PDF' : 'Excel').')');

        $filename = 'scas-dogs-'.now()->format('Y-m-d');

        if ($format === 'pdf') {
            $pdf = $export->pdfOutput();

            return response()->streamDownload(
                function () use ($pdf): void {
                    echo $pdf;
                },
                "{$filename}.pdf",
                ['Content-Type' => 'application/pdf'],
            );
        }

        return response()->streamDownload(
            fn () => $export->saveExcel('php://output'),
            "{$filename}.xlsx",
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }
}
