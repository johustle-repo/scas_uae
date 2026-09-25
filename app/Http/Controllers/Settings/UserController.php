<?php

namespace App\Http\Controllers\Settings;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UserPasswordResetRequest;
use App\Http\Requests\Settings\UserStoreRequest;
use App\Http\Requests\Settings\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display the user list.
     */
    public function index(): Response
    {
        return Inertia::render('settings/users/index', [
            'users' => User::query()->orderByDesc('is_active')->orderBy('name')->get(),
            'roleOptions' => collect(UserRole::cases())->map(fn (UserRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
                'description' => $role->description(),
            ]),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(UserStoreRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        return redirect()->route('settings.users.index')
            ->with('success', "{$user->name} was added.");
    }

    /**
     * Update a user's details and role.
     */
    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $user->update($request->validated());

        return redirect()->route('settings.users.index')
            ->with('success', "{$user->name} was updated.");
    }

    /**
     * Activate or deactivate a user account.
     */
    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate(['is_active' => ['required', 'boolean']]);
        $activate = (bool) $validated['is_active'];

        if (! $activate && $user->is($request->user())) {
            throw ValidationException::withMessages(['is_active' => 'You cannot deactivate your own account.']);
        }

        if (! $activate && $user->role === UserRole::Admin && ! User::otherActiveAdminsExist($user)) {
            throw ValidationException::withMessages(['is_active' => 'At least one active administrator is required.']);
        }

        $user->update(['is_active' => $activate]);

        return redirect()->route('settings.users.index')
            ->with('success', $activate ? "{$user->name} was reactivated." : "{$user->name} was deactivated.");
    }

    /**
     * Set a new password for a user.
     */
    public function resetPassword(UserPasswordResetRequest $request, User $user): RedirectResponse
    {
        $user->update(['password' => $request->validated('password')]);

        return redirect()->route('settings.users.index')
            ->with('success', "{$user->name}'s password was reset.");
    }
}
