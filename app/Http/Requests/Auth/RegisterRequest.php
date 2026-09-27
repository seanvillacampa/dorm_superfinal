<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Guest-only route (see routes/web.php); nothing to gate here.
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)],

            // Only these two roles may self-register through this form.
            // Tenants are registered by staff, never here — see
            // RegisterController::store() for the matching guard.
            'role' => ['required', 'in:owner,employee'],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in' => 'Tenant accounts cannot be created here. An employee or the owner must register tenants from the staff dashboard.',
        ];
    }
}
