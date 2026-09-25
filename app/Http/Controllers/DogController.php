<?php

namespace App\Http\Controllers;

use App\Enums\DocumentCategory;
use App\Enums\DogStatus;
use App\Enums\EnergyLevel;
use App\Enums\Gender;
use App\Enums\PhotoKind;
use App\Enums\PlacementType;
use App\Enums\RegistrationStatus;
use App\Enums\Size;
use App\Enums\Species;
use App\Enums\TrainingLevel;
use App\Http\Requests\Dogs\DogStoreRequest;
use App\Http\Requests\Dogs\DogUpdateRequest;
use App\Models\Dog;
use App\Models\DogPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class DogController extends Controller
{
    /**
     * Display a listing of the dogs, filterable via query params.
     */
    public function index(Request $request): Response
    {
        $dogs = Dog::query()
            ->with('identification')
            ->when(
                $request->string('archived')->toString(),
                fn ($query, string $archived) => $archived === 'only' ? $query->archived() : $query,
                fn ($query) => $query->active(),
            )
            ->status($this->listFilter($request, 'status'))
            ->placement($this->listFilter($request, 'placement'))
            ->search($request->string('q')->toString() ?: null)
            ->when($this->listFilter($request, 'gender'), fn ($query, array $genders) => $query->whereIn('gender', $genders))
            ->when($this->listFilter($request, 'size'), fn ($query, array $sizes) => $query->whereIn('size', $sizes))
            ->when($request->filled('breed'), fn ($query) => $query->where('breed', 'like', '%'.$request->string('breed').'%'))
            ->ageBetween(
                $request->filled('age_from') ? $request->integer('age_from') : null,
                $request->filled('age_to') ? $request->integer('age_to') : null,
            )
            ->when($request->boolean('needs_review'), fn ($query) => $query->where(
                fn ($query) => $query->needsReview()->orWhere(fn ($query) => $query->incomplete()),
            ))
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('dogs/index', [
            'dogs' => $dogs,
            'filters' => $request->only(['status', 'placement', 'gender', 'size', 'breed', 'q', 'age_from', 'age_to', 'needs_review', 'archived']),
            'statusOptions' => collect(DogStatus::cases())->map(fn (DogStatus $status) => ['value' => $status->value, 'label' => $status->label()]),
            'genderOptions' => collect(Gender::cases())->map(fn (Gender $gender) => ['value' => $gender->value, 'label' => $gender->label()]),
            'sizeOptions' => collect(Size::cases())->map(fn (Size $size) => ['value' => $size->value, 'label' => $size->label()]),
            'placementOptions' => collect(PlacementType::cases())->map(fn (PlacementType $placement) => ['value' => $placement->value, 'label' => $placement->label()]),
        ]);
    }

    /**
     * Show the form for creating a new dog.
     */
    public function create(): Response
    {
        return Inertia::render('dogs/create', $this->formOptions());
    }

    /**
     * Store a newly created dog in storage.
     */
    public function store(DogStoreRequest $request): RedirectResponse
    {
        $data = $request->safe();
        $photo = $request->file('photo');

        $dog = DB::transaction(function () use ($data, $photo, $request): Dog {
            $dog = Dog::create($data->except(['rescue_intake', 'medical', 'identification', 'photo']));

            if ($data->offsetExists('rescue_intake') && array_filter($data['rescue_intake'] ?? [])) {
                $dog->rescueIntake()->create($data['rescue_intake']);
            }

            if ($data->offsetExists('medical') && array_filter($data['medical'] ?? [])) {
                $dog->medicalProfile()->create($data['medical']);
            }

            if ($data->offsetExists('identification') && array_filter($data['identification'] ?? [])) {
                $dog->identification()->create($data['identification']);
            }

            if ($photo instanceof UploadedFile) {
                $profilePhoto = $dog->photos()->create([
                    'kind' => PhotoKind::Profile,
                    'file_path' => $photo->store("dog-photos/{$dog->id}", 'local')
                        ?: throw new RuntimeException('Could not store photo.'),
                    'uploaded_by' => $request->user()?->id,
                ]);

                $dog->update(['photo_path' => $profilePhoto->file_path]);
            }

            return $dog;
        });

        $tab = $request->boolean('save_and_add_medical') ? 'medical' : null;

        return redirect()->route('dogs.show', array_filter(['dog' => $dog, 'tab' => $tab]))
            ->with('success', "{$dog->name} was added as {$dog->scas_id}.");
    }

    /**
     * Display the specified dog.
     */
    public function show(Dog $dog): Response
    {
        $dog->load([
            'medicalProfile',
            'identification',
            'rescueIntake',
            'vaccinations' => fn ($query) => $query->orderByDesc('administered_date'),
            'fosterRecords' => fn ($query) => $query->orderByDesc('start_date'),
            'adoptionRecords' => fn ($query) => $query->orderByDesc('adoption_date'),
            'importFlags',
            'documents' => fn ($query) => $query->with('uploader:id,name')->latest(),
            'photos' => fn ($query) => $query->latest(),
        ]);

        $dog->photos->each(fn (DogPhoto $photo) => $photo->setAttribute('is_profile', $photo->file_path === $dog->photo_path));

        return Inertia::render('dogs/show', [
            'dog' => $dog,
            'placementHistory' => $this->buildPlacementHistory($dog),
            'documentCategoryOptions' => collect(DocumentCategory::cases())->map(fn (DocumentCategory $category) => ['value' => $category->value, 'label' => $category->label()]),
            'history' => Inertia::defer(fn () => $dog->auditLogs()
                ->with('user:id,name')
                ->latest('created_at')
                ->latest('id')
                ->limit(100)
                ->get()),
        ]);
    }

    /**
     * Show the form for editing the specified dog.
     */
    public function edit(Dog $dog): Response
    {
        return Inertia::render('dogs/edit', [
            'dog' => $dog,
            ...$this->formOptions(),
        ]);
    }

    /**
     * Update the specified dog in storage.
     */
    public function update(DogUpdateRequest $request, Dog $dog): RedirectResponse
    {
        $dog->update($request->validated());

        return redirect()->route('dogs.show', $dog)
            ->with('success', 'Profile saved.');
    }

    /**
     * Read a query filter that may be sent as a single value or a list of values.
     *
     * @return array<int, string>
     */
    protected function listFilter(Request $request, string $key): array
    {
        return collect(Arr::wrap($request->input($key)))
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();
    }

    /**
     * Enum option lists shared by the create/edit forms.
     *
     * @return array<string, Collection<int, array{value: string, label: string}>>
     */
    protected function formOptions(): array
    {
        return [
            'speciesOptions' => collect(Species::cases())->map(fn (Species $species) => ['value' => $species->value, 'label' => $species->label()]),
            'genderOptions' => collect(Gender::cases())->map(fn (Gender $gender) => ['value' => $gender->value, 'label' => $gender->label()]),
            'sizeOptions' => collect(Size::cases())->map(fn (Size $size) => ['value' => $size->value, 'label' => $size->label()]),
            'energyLevelOptions' => collect(EnergyLevel::cases())->map(fn (EnergyLevel $level) => ['value' => $level->value, 'label' => $level->label()]),
            'trainingLevelOptions' => collect(TrainingLevel::cases())->map(fn (TrainingLevel $level) => ['value' => $level->value, 'label' => $level->label()]),
            'registrationStatusOptions' => collect(RegistrationStatus::cases())->map(fn (RegistrationStatus $status) => ['value' => $status->value, 'label' => $status->label()]),
            'statusOptions' => collect(DogStatus::cases())->map(fn (DogStatus $status) => ['value' => $status->value, 'label' => $status->label()]),
        ];
    }

    /**
     * Merge rescue intake, foster, and adoption events into one sorted timeline.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildPlacementHistory(Dog $dog): Collection
    {
        $events = collect();

        if ($dog->rescueIntake !== null && $dog->rescueIntake->date_received !== null) {
            $events->push([
                'date' => $dog->rescueIntake->date_received,
                'type' => 'rescue',
                'location' => $dog->rescueIntake->rescue_location,
                'notes' => $dog->rescueIntake->initial_condition,
            ]);
        }

        foreach ($dog->fosterRecords as $foster) {
            $events->push([
                'date' => $foster->start_date,
                'type' => 'foster',
                'location' => $foster->location,
                'notes' => $foster->foster_family_name,
            ]);
        }

        foreach ($dog->adoptionRecords as $adoption) {
            $events->push([
                'date' => $adoption->adoption_date,
                'type' => 'adoption',
                'location' => $adoption->adopter_location,
                'notes' => $adoption->adopter_name,
            ]);
        }

        return $events->filter(fn (array $event) => $event['date'] !== null)
            ->sortByDesc('date')
            ->values();
    }
}
