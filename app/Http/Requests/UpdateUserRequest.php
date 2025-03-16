<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->route('user')->id,
            'role'  => 'required|in:admin,user',

            // Password is optional on update; only if admin is resetting it
            'password' => [
                'nullable',
                'confirmed',
                Password::min(8)->letters()->numbers()->mixedCase()->uncompromised(),
            ],
        ];
    }
}
