<?php
//
//use Illuminate\Support\Facades\Broadcast;
//use App\Models\User;
//use App\Models\ModuleContent;
//use App\Models\CourseOnline;
//use App\Models\LearningSession;
//use App\Models\CourseOnlineAssignment;
//
///*
//|--------------------------------------------------------------------------
//| Broadcast Channels
//|--------------------------------------------------------------------------
//| Here you may register all of the event broadcasting channels that your
//| application supports. The given channel authorization callbacks are
//| used to determine if an authenticated user can listen to the channel.
//*/
//
//// User-specific content progress channels
//Broadcast::channel('user.{userId}.content.{contentId}', function (User $user, int $userId, int $contentId) {
//    // User can only listen to their own content progress
//    if ($user->id !== $userId) {
//        return false;
//    }
//
//    // Verify user has access to this content
//    $content = ModuleContent::find($contentId);
//    if (!$content) {
//        return false;
//    }
//
//    $assignment = CourseOnlineAssignment::where('course_online_id', $content->module->course_online_id)
//        ->where('user_id', $userId)
//        ->first();
//
//    return $assignment !== null;
//});
//
//// User session-specific channels
//Broadcast::channel('user.{userId}.session.{sessionId}', function (User $user, int $userId, int $sessionId) {
//    if ($user->id !== $userId) {
//        return false;
//    }
//
//    // Verify this session belongs to the user
//    $session = LearningSession::where('id', $sessionId)
//        ->where('user_id', $userId)
//        ->first();
//
//    return $session !== null;
//});
//
//// Course-level progress channels
//Broadcast::channel('course.{courseId}.progress', function (User $user, int $courseId) {
//    // Allow if user has assignment in course OR user is admin
//    if ($user->isAdmin()) {
//        return true;
//    }
//
//    $assignment = CourseOnlineAssignment::where('course_online_id', $courseId)
//        ->where('user_id', $user->id)
//        ->first();
//
//    return $assignment !== null;
//});
//
//
//// Content activity channels
//Broadcast::channel('content.{contentId}.activity', function (User $user, int $contentId) {
//    $content = ModuleContent::find($contentId);
//    if (!$content) {
//        return false;
//    }
//
//    // Check if user has access to this content
//    $assignment = CourseOnlineAssignment::where('course_online_id', $content->module->course_online_id)
//        ->where('user_id', $user->id)
//        ->first();
//
//    return $assignment !== null;
//});
//
//// User completion channels
//Broadcast::channel('user.{userId}.completions', function (User $user, int $userId) {
//    return $user->id === $userId;
//});
//
//// Course completion channels
//Broadcast::channel('course.{courseId}.completions', function (User $user, int $courseId) {
//    // Allow if user has assignment in course OR user is admin
//    if ($user->isAdmin()) {
//        return true;
//    }
//
//    $assignment = CourseOnlineAssignment::where('course_online_id', $courseId)
//        ->where('user_id', $user->id)
//        ->first();
//
//    return $assignment !== null;
//});
//
//// Assignment progress channels
//// Assignment progress channels - FIXED to allow admins
//Broadcast::channel('assignment.{assignmentId}.progress', function (User $user, int $assignmentId) {
//    $assignment = CourseOnlineAssignment::find($assignmentId);
//
//    if (!$assignment) {
//        return false;
//    }
//
//    // Allow if user owns the assignment OR user is an admin
//    return $assignment->user_id === $user->id || $user->isAdmin();
//});
//
//
//// User heartbeat channels
//Broadcast::channel('user.{userId}.heartbeat', function (User $user, int $userId) {
//    return $user->id === $userId;
//});
//
//// Session activity channels
//Broadcast::channel('session.{sessionId}.activity', function (User $user, int $sessionId) {
//    $session = LearningSession::where('id', $sessionId)
//        ->where('user_id', $user->id)
//        ->first();
//
//    return $session !== null;
//});
//
//// User score channels
//Broadcast::channel('user.{userId}.scores', function (User $user, int $userId) {
//    return $user->id === $userId;
//});
//
//// Session analytics channels
//Broadcast::channel('session.{sessionId}.analytics', function (User $user, int $sessionId) {
//    $session = LearningSession::where('id', $sessionId)
//        ->where('user_id', $user->id)
//        ->first();
//
//    return $session !== null;
//});
//
//// User courses channels
//Broadcast::channel('user.{userId}.courses', function (User $user, int $userId) {
//    // Allow if same user OR admin
//    return $user->id === $userId || $user->isAdmin();
//});
//
//// Add these lines to your existing channels.php
//Broadcast::channel('user.{userId}.audio.{audioId}', function ($user, $userId, $audioId) {
//    return (int) $user->id === (int) $userId;
//});
//
//Broadcast::channel('user.{userId}.audio.sessions', function ($user, $userId) {
//    return (int) $user->id === (int) $userId;
//});
