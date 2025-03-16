<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only allow logged-in users to clock in
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            // If you do not tie clock in to a course, you can omit this
            // 'course_id' => 'nullable|exists:courses,id',
        ];
    }
}
