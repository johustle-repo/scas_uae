<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Support\DogReports;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * List the available reports.
     */
    public function index(DogReports $reports): Response
    {
        return Inertia::render('reports/index', [
            'reports' => collect(DogReports::REPORTS)
                ->map(fn (array $report, string $key): array => ['key' => $key, ...$report])
                ->values(),
            'rowCounts' => Inertia::defer(fn (): array => collect(DogReports::REPORTS)
                ->keys()
                ->mapWithKeys(fn (string $key): array => [$key => count($reports->build($key)['rows'])])
                ->all()),
            'summary' => Inertia::defer(fn (): array => $reports->build('summary')['rows']),
        ]);
    }

    /**
     * Show one report as a table.
     */
    public function show(DogReports $reports, string $report): Response
    {
        return Inertia::render('reports/show', [
            'report' => $reports->build($report),
        ]);
    }

    /**
     * Download one report as CSV, Excel or PDF.
     */
    public function download(DogReports $reports, string $report, string $format): StreamedResponse
    {
        $built = $reports->build($report);
        $filename = 'scas-'.str_replace('_', '-', $report).'-'.now()->format('Y-m-d');

        AuditLogger::record('export.report', "Exported the {$built['title']} report (".strtoupper($format).')');

        return match ($format) {
            'csv' => response()->streamDownload(
                fn () => $reports->writeCsv($built, 'php://output'),
                "{$filename}.csv",
                ['Content-Type' => 'text/csv; charset=UTF-8'],
            ),
            'excel' => response()->streamDownload(
                fn () => $reports->writeExcel($built, 'php://output'),
                "{$filename}.xlsx",
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            ),
            default => response()->streamDownload(
                function () use ($reports, $built): void {
                    echo $reports->pdfOutput($built);
                },
                "{$filename}.pdf",
                ['Content-Type' => 'application/pdf'],
            ),
        };
    }
}
