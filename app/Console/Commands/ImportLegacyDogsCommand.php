<?php

namespace App\Console\Commands;

use App\Services\AuditLogger;
use App\Support\LegacyImport\LegacyDogImportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Signature('dogs:import-legacy {path=database/data/legacy-dogs.xlsx} {--dry-run : Parse and report without writing to the database}')]
#[Description('Import dog records from the legacy SCAS spreadsheet, cleaning up known data-quality problems.')]
class ImportLegacyDogsCommand extends Command
{
    public function handle(LegacyDogImportService $importer): int
    {
        $path = (string) $this->argument('path');
        $dryRun = (bool) $this->option('dry-run');

        if (! is_file($path)) {
            $this->error("File not found: {$path}");

            return self::FAILURE;
        }

        DB::beginTransaction();

        try {
            $summary = AuditLogger::withoutAuditing(fn () => $importer->import($path));
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Import failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        if ($dryRun) {
            DB::rollBack();
            $this->comment('Dry run — no changes were written.');
        } else {
            DB::commit();

            AuditLogger::record(
                action: 'import.completed',
                description: "Imported legacy dog records from {$path}",
                newValues: $summary->toArray(),
            );
        }

        $this->table(
            ['Created', 'Updated', 'Skipped (footer/blank rows)', 'Flagged for review'],
            [[$summary->created, $summary->updated, $summary->skipped, $summary->flagged]],
        );

        Log::info('Legacy dog import completed.', [
            'path' => $path,
            'dry_run' => $dryRun,
            ...$summary->toArray(),
        ]);

        return self::SUCCESS;
    }
}
