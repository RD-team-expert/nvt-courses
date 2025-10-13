<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exclusive Course Assignment - Professional Development</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 25px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px 25px; background: white; border-radius: 0 0 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .exclusive-notice { background: linear-gradient(135deg, #fef3c7, #fde68a); border-left: 4px solid #f59e0b; padding: 20px; border-radius: 8px; margin: 25px 0; }
        .mandatory-alert { background: #fee2e2; border: 2px solid #dc2626; padding: 20px; border-radius: 8px; margin: 25px 0; text-align: center; }
        .course-info { background: #f8fafc; border: 2px solid #e2e8f0; padding: 25px; border-radius: 8px; margin: 25px 0; }
        .cta-button { display: inline-block; background: linear-gradient(135deg, #059669, #047857); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2); transition: transform 0.2s; }
        .cta-button:hover { transform: translateY(-2px); }
        .benefits-box { background: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 20px; border-radius: 8px; margin: 25px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; background: #f8fafc; margin-top: 20px; border-radius: 8px; }
        .highlight { color: #dc2626; font-weight: bold; }
        h1 { margin: 0; font-size: 24px; }
        h2 { color: #1e293b; margin-bottom: 15px; }
        h3 { color: #374151; margin-bottom: 10px; }
        ul { padding-left: 20px; }
        li { margin-bottom: 8px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🎯 Exclusive Course Assignment</h1>
        <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">Professional Development Opportunity</p>
    </div>

    <div class="content">
        <p>Hi <strong>{{ $user->name }}</strong>,</p>

        <div class="exclusive-notice">
            <h3>🌟 Congratulations on Your Selection!</h3>
            <p><strong>We've selected you for an exclusive online course</strong> designed to enhance your skills and support your professional growth. This course has been carefully chosen to help you advance your career while contributing to our team's continued success.</p>
        </div>

        <div class="mandatory-alert">
            <h3>⚠️ Important Notice</h3>
            <p class="highlight">Please note that completing this course is mandatory</p>
            <p>Your progress will be tracked and reflected in the <strong>personal development section of your monthly evaluation</strong>.</p>
        </div>

        <p>You have been assigned this course by <strong>{{ $assignedBy->name }}</strong>.</p>

        <div class="course-info">
            <h2>📚 {{ $course->name }}</h2>

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

        <div class="benefits-box">
            <h3>💡 Why This Course Matters</h3>
            <ul>
                <li><strong>Career Advancement:</strong> Develops skills directly applicable to your role</li>
                <li><strong>Professional Growth:</strong> Enhances your expertise and marketability</li>
                <li><strong>Team Contribution:</strong> Strengthens our collective capabilities</li>
                <li><strong>Evaluation Impact:</strong> Positive reflection in your development assessment</li>
            </ul>
        </div>

        <p>This is a self-paced online course. You can start immediately and learn at your own schedule, but remember that <strong>completion is required</strong>.</p>

        <div style="text-align: center; margin: 30px 0;">
            @if($loginLink)
                <a href="{{ $loginLink }}" class="cta-button">🚀 Start Learning Now</a>
            @else
                <a href="{{ route('courses-online.index') }}" class="cta-button">🚀 View My Courses</a>
            @endif
        </div>

        <p><strong>📋 Getting Started:</strong></p>
        <ul>
            <li>Click the button above to access the course</li>
            <li>Complete modules in order to track progress</li>
            <li>Your progress is automatically saved and monitored</li>
            <li>You can continue where you left off anytime</li>
            <li><strong>Ensure completion within the designated timeframe</strong></li>
        </ul>

        <p><strong>📊 Course Features:</strong></p>
        <ul>
            <li>Self-paced online learning</li>
            <li>Progress automatically tracked for evaluation</li>
            <li>Available 24/7 from any device</li>
            <li>Interactive content and assessments</li>
            <li>Certificate upon completion</li>
        </ul>

        <p>We're excited to support your professional development journey. Your commitment to completing this course demonstrates your dedication to growth and excellence.</p>

        <p>If you have any questions about this assignment, please contact <strong>{{ $assignedBy->name }}</strong> or your direct manager.</p>

        <p>Best of luck with your learning!</p>
    </div>

    <div class="footer">
        <p>🎓 Invest in your future - Your career development matters!</p>
        <p>This email was sent automatically by the Learning Management System.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</div>
</body>
</html>
