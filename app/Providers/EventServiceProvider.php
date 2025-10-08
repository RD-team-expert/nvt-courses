<?php

namespace App\Providers;

use App\Events\CourseAssigned;
use App\Events\CourseCreated;
use App\Events\CourseEnrolled;
use App\Events\CourseCompleted;
use App\Listeners\SendCourseCreationEmail;
use App\Listeners\SendCourseEnrollmentEmail;
use App\Listeners\SendCourseCompletionEmail;
use App\Listeners\SendCourseNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        CourseCreated::class => [
            SendCourseCreationEmail::class,
        ],
        CourseEnrolled::class => [
            SendCourseEnrollmentEmail::class,
        ],
        CourseCompleted::class => [
            SendCourseCompletionEmail::class,
        ],
//        CourseAssigned::class => [
//            SendCourseNotification::class,
//        ],
    ];

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
