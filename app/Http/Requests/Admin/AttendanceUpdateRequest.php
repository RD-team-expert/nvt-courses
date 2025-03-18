<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Assuming authorization is handled elsewhere
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'course_id' => 'nullable|exists:courses,id',
            'clock_in' => 'required|date',
            'clock_out' => 'nullable|date|after:clock_in',
            'rating' => 'nullable|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ];
    }
}