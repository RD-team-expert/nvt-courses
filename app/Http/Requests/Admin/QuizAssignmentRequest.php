<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class QuizAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true ;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quiz_id' => ['required', 'exists:quizzes,id'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['exists:users,id'],
            'send_notification' => ['required', 'in:none,email_now'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quiz_id.required' => 'Please select a quiz to assign.',
            'quiz_id.exists' => 'The selected quiz does not exist.',
            'user_ids.required' => 'Please select at least one user.',
            'user_ids.min' => 'Please select at least one user.',
            'user_ids.*.exists' => 'One or more selected users do not exist.',
            'send_notification.required' => 'Please select a notification option.',
            'send_notification.in' => 'Invalid notification option selected.',
        ];
    }
}
