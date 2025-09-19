<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Course;
use App\Models\User;

class CourseAssigned
{
    use Dispatchable, SerializesModels;

    public $course;
    public $user;
    public $loginLink; // ✅ Changed from password to loginLink

    public function __construct(Course $course, User $user, $loginLink = null)
    {
        $this->course = $course;
        $this->user = $user;
        $this->loginLink = $loginLink; // ✅ Store login link instead of password
    }
}
