<?php

namespace App\Http\Controllers;

use App\Enums\AdoptionApplicationStatus;
use App\Enums\HomeType;
use App\Http\Requests\AdoptionRequestUpdateRequest;
use App\Models\AdoptionApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdoptionRequestController extends Controller
{
    /**
     * Staff inbox of adoption requests from the public landing page.
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(AdoptionApplicationStatus::class)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $requests = AdoptionApplication::query()
            ->with(['dog:id,name,scas_id,photo_path,updated_at,current_status', 'reviewer:id,name'])
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, fn ($query, string $term) => $query->where(
                fn ($query) => $query->where('full_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhereHas('dog', fn ($query) => $query->where('name', 'like', "%{$term}%")),
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (AdoptionApplication $application): array => [
                ...$application->only([
                    'id', 'full_name', 'email', 'phone', 'city', 'has_garden', 'has_children',
                    'other_pets', 'experience', 'message', 'staff_notes', 'reviewed_at', 'created_at',
                ]),
                'home_type' => $application->home_type->value,
                'status' => $application->status->value,
                'reviewer' => $application->reviewer?->name,
                'dog' => $application->dog === null ? null : [
                    'id' => $application->dog->id,
                    'name' => $application->dog->name,
                    'scas_id' => $application->dog->scas_id,
                    'photo_url' => $application->dog->photo_url,
                    'current_status' => $application->dog->current_status->value,
                ],
            ]);

        return Inertia::render('adoption-requests/index', [
            'requests' => $requests,
            'filters' => $filters,
            'statusCounts' => AdoptionApplication::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'statusOptions' => collect(AdoptionApplicationStatus::cases())
                ->map(fn (AdoptionApplicationStatus $status): array => ['value' => $status->value, 'label' => $status->label()]),
            'homeTypeLabels' => collect(HomeType::cases())
                ->mapWithKeys(fn (HomeType $type): array => [$type->value => $type->label()]),
        ]);
    }

    /**
     * Update a request's status and the team's notes.
     */
    public function update(AdoptionRequestUpdateRequest $request, AdoptionApplication $adoptionRequest): RedirectResponse
    {
        $adoptionRequest->fill($request->validated());

        if ($adoptionRequest->isDirty('status')) {
            $adoptionRequest->reviewed_by = $request->user()->id;
            $adoptionRequest->reviewed_at = now();
        }

        $adoptionRequest->save();

        return back()->with('success', 'Adoption request updated.');
    }
}
