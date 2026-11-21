<?php

use App\Enums\DogStatus;
use App\Models\Dog;
use App\Models\DogFosterRecord;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\IOFactory;

test('every user can open a report as a table', function () {
    Dog::factory()->create(['scas_id' => 'SCAS-001', 'name' => 'Abby', 'current_status' => DogStatus::AtScas]);
    Dog::factory()->create(['scas_id' => 'SCAS-002', 'name' => 'Bruno', 'current_status' => DogStatus::AdoptedUae]);
    Dog::factory()->create(['scas_id' => 'SCAS-003', 'name' => 'Goldie', 'current_status' => DogStatus::AtScas, 'archived_at' => now()]);

    $response = $this->actingAs(User::factory()->readOnly()->create())->get(route('reports.show', 'at_scas'));

    $response->assertInertia(fn ($page) => $page
        ->component('reports/show')
        ->where('report.title', 'Dogs currently at SCAS')
        ->has('report.rows', 1)
        ->where('report.rows.0.0', 'SCAS-001'));
});

test('the summary report counts dogs by placement', function () {
    Dog::factory()->count(2)->create(['current_status' => DogStatus::AtScas]);
    Dog::factory()->create(['current_status' => DogStatus::InternationalFoster]);

    $response = $this->actingAs(User::factory()->create())->get(route('reports.show', 'summary'));

    $response->assertInertia(fn ($page) => $page
        ->where('report.rows.0', ['Total dogs', '3'])
        ->where('report.rows.1', ['Currently at SCAS', '2'])
        ->where('report.rows.3', ['In foster (international)', '1'])
        ->where('report.rows.6', ['International placements', '1']));
});

test('a report downloads as csv with spreadsheet formulas neutralised and is logged', function () {
    $user = User::factory()->create();
    $dog = Dog::factory()->create(['scas_id' => 'SCAS-010', 'name' => 'Rex', 'current_status' => DogStatus::LocalFoster]);
    DogFosterRecord::factory()->for($dog)->create(['foster_family_name' => '=HYPERLINK("http://evil.test")', 'location' => 'Dubai', 'start_date' => '2026-01-05', 'end_date' => null]);

    $response = $this->actingAs($user)->get(route('reports.download', ['report' => 'in_foster', 'format' => 'csv']));

    $response->assertDownload('scas-in-foster-'.now()->format('Y-m-d').'.csv');
    $lines = array_map(str_getcsv(...), explode("\n", trim(substr($response->streamedContent(), 3))));
    expect($lines)->toBe([
        ['SCAS ID', 'Name', 'Status', 'Foster Family', 'Location', 'Since'],
        ['SCAS-010', 'Rex', 'Local Foster', '\'=HYPERLINK("http://evil.test")', 'Dubai', '2026-01-05'],
    ]);
    $this->assertDatabaseHas('audit_logs', ['action' => 'export.report', 'user_id' => $user->id]);
});

test('a report downloads as an excel workbook that keeps formulas as text', function () {
    Dog::factory()->create(['scas_id' => 'SCAS-011', 'name' => '=1+1']);

    $response = $this->actingAs(User::factory()->create())->get(route('reports.download', ['report' => 'all_dogs', 'format' => 'excel']));

    $path = tempnam(sys_get_temp_dir(), 'report');
    file_put_contents($path, $response->streamedContent());
    $sheet = IOFactory::load($path)->getActiveSheet();
    unlink($path);
    expect($sheet->getCell('B2')->getValue())->toBe('=1+1')
        ->and($sheet->getCell('B2')->getDataType())->toBe('s');
});

test('a report downloads as a pdf', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('reports.download', ['report' => 'medical', 'format' => 'pdf']))
        ->assertDownload('scas-medical-'.now()->format('Y-m-d').'.pdf');
});

test('unknown reports and formats are not found', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/reports/salaries')->assertNotFound();
    $this->actingAs($user)->get('/reports/summary/docx')->assertNotFound();
});

test('guests cannot see reports', function () {
    $this->get(route('reports.index'))->assertRedirect(route('login'));
});
