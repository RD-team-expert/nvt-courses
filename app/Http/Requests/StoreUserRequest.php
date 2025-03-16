<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only admins can create new users
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'role'     => 'required|in:admin,user',
            'password' => [
                'required',
                'confirmed', // ensure there's a password_confirmation field
                Password::min(8)->letters()->numbers()->mixedCase()->uncompromised(),
            ],
        ];
    }
}
