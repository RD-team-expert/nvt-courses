<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Online Course Assignment</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px 20px; background: #f8fafc; }
        .course-info { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .cta-button { display: inline-block; background: #059669; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📚 New Online Course Assignment</h1>
    </div>

    <div class="content">
        <p>Hi <strong>{{ $user->name }}</strong>,</p>

        <p>You have been assigned a new online course by <strong>{{ $assignedBy->name }}</strong>.</p>

        <div class="course-info">
            <h2>{{ $course->name }}</h2>

            @if($course->description)
                <p><strong>Description:</strong> {{ $course->description }}</p>
            @endif

            @if($course->difficulty_level)
                <p><strong>Difficulty:</strong> {{ ucfirst($course->difficulty_level) }}</p>
            @endif

            @if($course->estimated_duration)
                <p><strong>Estimated Duration:</strong> {{ $course->estimated_duration }} hours</p>
            @endif

            <p><strong>Modules:</strong> {{ $course->modules_count ?? $course->modules->count() ?? 'Multiple' }} learning modules</p>

            <p><strong>Assignment Date:</strong> {{ now()->format('M d, Y') }}</p>
        </div>

        <p>This is a self-paced online course. You can start immediately and learn at your own schedule.</p>

        <div style="text-align: center; margin: 30px 0;">
            @if($loginLink)
                <a href="{{ $loginLink }}" class="cta-button">🚀 Start Learning Now</a>
            @else
                <a href="{{ route('courses-online.index') }}" class="cta-button">🚀 View My Courses</a>
            @endif
        </div>

        <p><strong>Getting Started:</strong></p>
        <ul>
            <li>Click the button above to access the course</li>
            <li>Complete modules in order to track progress</li>
            <li>Your progress is automatically saved</li>
            <li>You can continue where you left off anytime</li>
        </ul>

        <p>If you have any questions about this assignment, please contact <strong>{{ $assignedBy->name }}</strong> or your direct manager.</p>

        <p>Happy learning!</p>
    </div>

    <div class="footer">
        <p>This email was sent automatically by the Learning Management System.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</div>
</body>
</html>
