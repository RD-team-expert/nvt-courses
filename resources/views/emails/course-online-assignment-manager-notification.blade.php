<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Member Course Assignment</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #7c3aed; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px 20px; background: #f8fafc; }
        .course-info { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .team-members { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .member { padding: 10px; border-left: 4px solid #2563eb; margin: 10px 0; background: #f1f5f9; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>👥 Team Member Course Assignment</h1>
    </div>

    <div class="content">
        <p>Hello <strong>{{ $manager->name }}</strong>,</p>

        <p>This is to inform you that
            @if($teamMembers->count() === 1)
                your team member has been assigned
            @else
                {{ $teamMembers->count() }} of your team members have been assigned
            @endif
            to a new online course by <strong>{{ $assignedBy->name }}</strong>.
        </p>

        <div class="course-info">
            <h2>📚 {{ $course->name }}</h2>

            @if($course->description)
                <p><strong>Description:</strong> {{ $course->description }}</p>
            @endif

            @if($course->difficulty_level)
                <p><strong>Difficulty Level:</strong> {{ ucfirst($course->difficulty_level) }}</p>
            @endif

            @if($course->estimated_duration)
                <p><strong>Estimated Duration:</strong> {{ $course->estimated_duration }} hours</p>
            @endif

            <p><strong>Assignment Date:</strong> {{ now()->format('M d, Y') }}</p>
        </div>

        <div class="team-members">
            <h3>🎯 Assigned Team Members:</h3>
            @foreach($teamMembers as $member)
                <div class="member">
                    <strong>{{ $member->name }}</strong><br>
                    <small>{{ $member->email }}</small>
                    @if($member->department)
                        <br><small>{{ $member->department->name }}</small>
                    @endif
                </div>
            @endforeach
        </div>

        <p><strong>📋 As their manager, you may want to:</strong></p>
        <ul>
            <li>Follow up on their progress periodically</li>
            <li>Support them if they need help with the course content</li>
            <li>Discuss how the learning applies to their role</li>
            <li>Recognize their completion when they finish</li>
        </ul>

        <p><strong>📊 Course Details:</strong></p>
        <ul>
            <li>Self-paced online learning</li>
            <li>Progress automatically tracked</li>
            <li>Available 24/7 from any device</li>
            <li>Certificate upon completion</li>
        </ul>

        <p>The team members have been notified directly and can start the course immediately. Their progress will be tracked in the system for your review.</p>

        <p>If you have any questions about this assignment, please contact <strong>{{ $assignedBy->name }}</strong>.</p>

        <p>Best regards,<br>Learning Management System</p>
    </div>

    <div class="footer">
        <p>This notification was sent automatically when course assignments were made.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</div>
</body>
</html>
