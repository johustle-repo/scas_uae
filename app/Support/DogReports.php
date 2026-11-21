<?php

namespace App\Support;

use App\Enums\DogStatus;
use App\Models\Dog;
use App\Models\DogAdoptionRecord;
use App\Models\DogFosterRecord;
use App\Services\DogAlerts;
use Barryvdh\DomPDF\Facade\Pdf;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * The standard reports (spec §13), each a titled table of active dogs or
 * their records that can be viewed or downloaded as CSV, Excel or PDF.
 */
class DogReports
{
    /**
     * @var array<string, array{title: string, description: string}>
     */
    public const REPORTS = [
        'summary' => ['title' => 'Summary', 'description' => 'Headline counts: total dogs, at SCAS, in foster, adopted, international placements and records needing update.'],
        'all_dogs' => ['title' => 'All dogs', 'description' => 'Every active dog with its key profile details.'],
        'at_scas' => ['title' => 'Dogs currently at SCAS', 'description' => 'Dogs in the sanctuary right now.'],
        'in_foster' => ['title' => 'Dogs in foster care', 'description' => 'Dogs in a local or international foster home, with their current placement.'],
        'adopted' => ['title' => 'Adopted dogs', 'description' => 'Dogs adopted in the UAE or internationally, with their adopter.'],
        'international' => ['title' => 'International placements', 'description' => 'Dogs fostered or adopted abroad.'],
        'needs_update' => ['title' => 'Records requiring updates', 'description' => 'Dogs flagged during import or missing breed, gender, size, date of birth or microchip.'],
        'medical' => ['title' => 'Medical status', 'description' => 'Neutering, vet checks, vaccinations and treatment for each dog.'],
        'foster' => ['title' => 'Foster placements', 'description' => 'Every foster placement, current and past.'],
        'adoption' => ['title' => 'Adoptions', 'description' => 'Every adoption record, including returns.'],
        'intake' => ['title' => 'Rescue and intake', 'description' => 'How and when each dog came to SCAS.'],
    ];

    public function __construct(private readonly DogAlerts $alerts) {}

    /**
     * @return array{key: string, title: string, description: string, headings: list<string>, rows: list<list<string>>}
     */
    public function build(string $key): array
    {
        [$headings, $rows] = match ($key) {
            'summary' => $this->summary(),
            'all_dogs' => $this->dogList(Dog::query()),
            'at_scas' => $this->dogList(Dog::query()->status(DogStatus::AtScas)),
            'in_foster' => $this->inFoster(),
            'adopted' => $this->adopted(),
            'international' => $this->dogList(Dog::query()->status([DogStatus::InternationalFoster, DogStatus::AdoptedInternationally])),
            'needs_update' => $this->needsUpdate(),
            'medical' => $this->medical(),
            'foster' => $this->fosterHistory(),
            'adoption' => $this->adoptionHistory(),
            'intake' => $this->intake(),
            default => throw new \InvalidArgumentException("Unknown report \"{$key}\"."),
        };

        return ['key' => $key, ...self::REPORTS[$key], 'headings' => $headings, 'rows' => $rows];
    }

    /**
     * Stream the report as CSV to the given path (e.g. "php://output").
     *
     * @param  array{headings: list<string>, rows: list<list<string>>}  $report
     */
    public function writeCsv(array $report, string $path): void
    {
        $handle = fopen($path, 'w') ?: throw new \RuntimeException('Could not open the CSV output.');
        fwrite($handle, "\xEF\xBB\xBF");

        foreach ([$report['headings'], ...$report['rows']] as $row) {
            fputcsv($handle, array_map($this->neutraliseFormula(...), $row), escape: '');
        }

        fclose($handle);
    }

    /**
     * @param  array{title: string, headings: list<string>, rows: list<list<string>>}  $report
     */
    public function writeExcel(array $report, string $path): void
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->setValueBinder((new StringValueBinder)->setSetIgnoredErrors(true));
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($report['title'], 0, 31));
        $sheet->fromArray([$report['headings'], ...$report['rows']], null, 'A1', true);
        $sheet->getStyle([1, 1, max(1, count($report['headings'])), 1])->getFont()->setBold(true);
        $sheet->freezePane('A2');

        foreach (range(1, max(1, count($report['headings']))) as $column) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
    }

    /**
     * @param  array{title: string, description: string, headings: list<string>, rows: list<list<string>>}  $report
     */
    public function pdfOutput(array $report): string
    {
        return Pdf::loadView('exports.report', ['report' => $report, 'generatedAt' => now()])
            ->setPaper('a4', count($report['headings']) > 5 ? 'landscape' : 'portrait')
            ->output();
    }

    /**
     * Spreadsheet apps run a cell starting with =, +, -, @, tab or CR as a
     * formula, so such values are prefixed with an apostrophe.
     */
    private function neutraliseFormula(string $value): string
    {
        return $value !== '' && str_contains("=+-@\t\r", $value[0]) ? "'".$value : $value;
    }

    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function summary(): array
    {
        $active = fn (): Builder => Dog::query()->active();
        $needsUpdate = collect($this->alerts->groups())->firstWhere('key', 'needs_update');

        $rows = [
            ['Total dogs', $active()->count()],
            ['Currently at SCAS', $active()->status(DogStatus::AtScas)->count()],
            ['In foster (local)', $active()->status(DogStatus::LocalFoster)->count()],
            ['In foster (international)', $active()->status(DogStatus::InternationalFoster)->count()],
            ['Adopted (UAE)', $active()->status(DogStatus::AdoptedUae)->count()],
            ['Adopted (international)', $active()->status(DogStatus::AdoptedInternationally)->count()],
            ['International placements', $active()->status([DogStatus::InternationalFoster, DogStatus::AdoptedInternationally])->count()],
            ['Returned / rehoming', $active()->status(DogStatus::ReturnedRehoming)->count()],
            ['Memorial', $active()->status(DogStatus::Memorial)->count()],
            ['Records requiring updates', count($needsUpdate['items'] ?? [])],
            ['Archived records', Dog::query()->archived()->count()],
        ];

        return [['Measure', 'Count'], array_map(fn (array $row): array => [$row[0], (string) $row[1]], $rows)];
    }

    /**
     * @param  Builder<Dog>  $query
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function dogList(Builder $query): array
    {
        $dogs = $query->active()->with('identification')->orderBy('scas_id')->get();

        return [
            ['SCAS ID', 'Name', 'Status', 'Location', 'Breed', 'Gender', 'Size', 'Date of Birth', 'Microchip'],
            array_values($dogs->map(fn (Dog $dog): array => [
                $dog->scas_id,
                $dog->name,
                $dog->current_status->label(),
                (string) $dog->current_location,
                (string) $dog->breed,
                (string) $dog->gender?->label(),
                (string) $dog->size?->label(),
                $this->date($dog->date_of_birth),
                (string) $dog->identification?->microchip_number,
            ])->all()),
        ];
    }

    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function inFoster(): array
    {
        $dogs = Dog::query()->active()
            ->status([DogStatus::LocalFoster, DogStatus::InternationalFoster])
            ->with(['fosterRecords' => fn ($query) => $query->whereNull('end_date')->latest('start_date')])
            ->orderBy('scas_id')
            ->get();

        return [
            ['SCAS ID', 'Name', 'Status', 'Foster Family', 'Location', 'Since'],
            array_values($dogs->map(function (Dog $dog): array {
                /** @var DogFosterRecord|null $foster */
                $foster = $dog->fosterRecords->first();

                return [$dog->scas_id, $dog->name, $dog->current_status->label(), (string) $foster?->foster_family_name, (string) ($foster->location ?? $dog->current_location), $this->date($foster?->start_date)];
            })->all()),
        ];
    }

    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function adopted(): array
    {
        $dogs = Dog::query()->active()
            ->status([DogStatus::AdoptedUae, DogStatus::AdoptedInternationally])
            ->with(['adoptionRecords' => fn ($query) => $query->latest('adoption_date')])
            ->orderBy('scas_id')
            ->get();

        return [
            ['SCAS ID', 'Name', 'Status', 'Adopter', 'Adopter Location', 'Adoption Date'],
            array_values($dogs->map(function (Dog $dog): array {
                /** @var DogAdoptionRecord|null $adoption */
                $adoption = $dog->adoptionRecords->first();

                return [$dog->scas_id, $dog->name, $dog->current_status->label(), (string) $adoption?->adopter_name, (string) $adoption?->adopter_location, $this->date($adoption?->adoption_date)];
            })->all()),
        ];
    }

    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function needsUpdate(): array
    {
        $group = collect($this->alerts->groups())->firstWhere('key', 'needs_update');

        return [
            ['SCAS ID', 'Name', 'What needs updating'],
            array_map(fn (array $item): array => [$item['scas_id'], $item['name'], $item['detail']], $group['items'] ?? []),
        ];
    }

    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function medical(): array
    {
        $dogs = Dog::query()->active()->with(['medicalProfile', 'vaccinations'])->orderBy('scas_id')->get();

        return [
            ['SCAS ID', 'Name', 'Status', 'Spayed / Neutered', 'Last Vet Check', 'Vaccinations Up to Date', 'Next Vaccination', 'Medical Concerns', 'Treatment Required', 'Medications'],
            array_values($dogs->map(fn (Dog $dog): array => [
                $dog->scas_id,
                $dog->name,
                $dog->current_status->label(),
                $this->yesNo($dog->medicalProfile?->spayed_neutered),
                $this->date($dog->medicalProfile?->last_vet_check_date),
                $dog->vaccinations->isEmpty() ? 'No records' : ($dog->vaccinations_up_to_date ? 'Yes' : 'No'),
                $this->date($dog->next_vaccination_date),
                (string) $dog->medicalProfile?->medical_concerns,
                (string) $dog->medicalProfile?->treatment_required,
                (string) $dog->medicalProfile?->medications,
            ])->all()),
        ];
    }

    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function fosterHistory(): array
    {
        $records = DogFosterRecord::query()
            ->whereHas('dog', fn ($query) => $query->active())
            ->with('dog:id,scas_id,name')
            ->latest('start_date')
            ->get();

        return [
            ['SCAS ID', 'Name', 'Type', 'Foster Family', 'Location', 'Start Date', 'End Date', 'Current'],
            array_values($records->map(fn (DogFosterRecord $record): array => [
                (string) $record->dog?->scas_id,
                (string) $record->dog?->name,
                $record->foster_type->label(),
                $record->foster_family_name,
                (string) $record->location,
                $this->date($record->start_date),
                $this->date($record->end_date),
                $record->end_date === null ? 'Yes' : 'No',
            ])->all()),
        ];
    }

    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function adoptionHistory(): array
    {
        $records = DogAdoptionRecord::query()
            ->whereHas('dog', fn ($query) => $query->active())
            ->with('dog:id,scas_id,name')
            ->latest('adoption_date')
            ->get();

        return [
            ['SCAS ID', 'Name', 'Type', 'Adopter', 'Adopter Location', 'Adoption Date', 'Return Date', 'Return Reason'],
            array_values($records->map(fn (DogAdoptionRecord $record): array => [
                (string) $record->dog?->scas_id,
                (string) $record->dog?->name,
                $record->adoption_type->label(),
                $record->adopter_name,
                (string) $record->adopter_location,
                $this->date($record->adoption_date),
                $this->date($record->return_date),
                (string) $record->return_reason,
            ])->all()),
        ];
    }

    /**
     * @return array{0: list<string>, 1: list<list<string>>}
     */
    private function intake(): array
    {
        /** @var Collection<int, Dog> $dogs */
        $dogs = Dog::query()->active()->with('rescueIntake')->orderBy('scas_id')->get();

        return [
            ['SCAS ID', 'Name', 'Date Received', 'Rescue Location', 'How Found', 'Source of Intake', 'Initial Condition'],
            array_values($dogs->map(fn (Dog $dog): array => [
                $dog->scas_id,
                $dog->name,
                $this->date($dog->rescueIntake?->date_received),
                (string) $dog->rescueIntake?->rescue_location,
                (string) $dog->rescueIntake?->how_found,
                (string) $dog->rescueIntake?->source_of_intake,
                (string) $dog->rescueIntake?->initial_condition,
            ])->all()),
        ];
    }

    private function date(?DateTimeInterface $date): string
    {
        return $date?->format('Y-m-d') ?? '';
    }

    private function yesNo(?bool $value): string
    {
        return match ($value) {
            true => 'Yes',
            false => 'No',
            null => '',
        };
    }
}
