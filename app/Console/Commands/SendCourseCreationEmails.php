<?php

namespace App\Console\Commands;

use App\Events\CourseCreated;
use App\Models\Course;
use Illuminate\Console\Command;

class SendCourseCreationEmails extends Command
{


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'courses:send-creation-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send course creation emails to all users' ;
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $courses = Course::whereDate('created_at', today()->toDateString())->get();

        foreach ($courses as $course) {
            SendCourseCreationEmails::dispatch($course);
        }

        $this->info('Course creation emails dispatched successfully!');
    }
}
