<?php

namespace App\Http\Controllers\Dogs;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\DogDataExport;
use App\Support\DogRegister\DogRegisterExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private const XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * Download active dogs as a Dog Register workbook (the same layout the
     * import reads), or every record and its related data as a multi-sheet
     * workbook or PDF report.
     */
    public function __invoke(DogDataExport $export, DogRegisterExport $register, string $format): StreamedResponse
    {
        $date = now()->format('Y-m-d');

        AuditLogger::record('export.dogs', 'Exported dog data ('.match ($format) {
            'excel' => 'Dog Register Excel',
            'records' => 'full records Excel',
            default => 'PDF',
        }.')');

        return match ($format) {
            'excel' => response()->streamDownload(
                fn () => $register->save('php://output'),
                "scas-dog-register-{$date}.xlsx",
                ['Content-Type' => self::XLSX],
            ),
            'records' => response()->streamDownload(
                fn () => $export->saveExcel('php://output'),
                "scas-dogs-{$date}.xlsx",
                ['Content-Type' => self::XLSX],
            ),
            default => $this->pdf($export, "scas-dogs-{$date}.pdf"),
        };
    }

    private function pdf(DogDataExport $export, string $filename): StreamedResponse
    {
        $pdf = $export->pdfOutput();

        return response()->streamDownload(
            function () use ($pdf): void {
                echo $pdf;
            },
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }
}
