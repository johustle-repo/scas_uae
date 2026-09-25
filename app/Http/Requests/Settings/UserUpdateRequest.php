<?php

namespace App\Http\Requests\Settings;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UserUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::enum(UserRole::class)],
        ];
    }

    /**
     * Stop administrators from locking everyone out of user management.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var User $user */
                $user = $this->route('user');

                if ($user->role !== UserRole::Admin || $this->input('role') === UserRole::Admin->value) {
                    return;
                }

                if ($user->is($this->user())) {
                    $validator->errors()->add('role', 'You cannot remove your own administrator access.');
                } elseif (! User::otherActiveAdminsExist($user)) {
                    $validator->errors()->add('role', 'At least one active administrator is required.');
                }
            },
        ];
    }
}
