<?php

namespace App\Http\Controllers\Dogs;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\DogDataExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Download every dog record and its related data as an Excel workbook.
     */
    public function __invoke(DogDataExport $export): StreamedResponse
    {
        AuditLogger::record('export.dogs', 'Exported all dog data');

        return response()->streamDownload(
            fn () => $export->save('php://output'),
            'scas-dogs-'.now()->format('Y-m-d').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }
}
