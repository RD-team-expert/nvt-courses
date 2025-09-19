<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Learning Opportunity: {{ $courseName }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            line-height: 1.7;
            color: #374151;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9fafb;
        }
        .email-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 30px 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 25px;
        }
        .welcome-message {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-left: 4px solid #10b981;
            padding: 25px;
            margin: 25px 0;
            border-radius: 8px;
        }
        .welcome-message h3 {
            color: #059669;
            margin: 0 0 15px 0;
            font-size: 18px;
        }
        .course-details {
            background-color: #f8fafc;
            padding: 25px;
            border-radius: 10px;
            margin: 25px 0;
            border: 1px solid #e2e8f0;
        }
        .course-details h3 {
            color: #1e40af;
            margin: 0 0 20px 0;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .date-section {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            border-radius: 10px;
            padding: 25px;
            margin: 25px 0;
        }
        .date-section h4 {
            color: #b45309;
            margin: 0 0 15px 0;
            font-size: 18px;
        }
        .date-option {
            background-color: white;
            border: 2px solid #fbbf24;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            font-weight: 600;
            color: #92400e;
        }
        .login-section {
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
            border: 1px solid #8b5cf6;
            border-radius: 10px;
            padding: 25px;
            margin: 30px 0;
        }
        .login-section h4 {
            color: #7c3aed;
            margin: 0 0 20px 0;
            font-size: 18px;
        }
        .credential-box {
            background-color: white;
            border: 1px solid #c4b5fd;
            border-radius: 8px;
            padding: 15px;
            margin: 12px 0;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, monospace;
            font-size: 14px;
            color: #4c1d95;
        }
        .cta-section {
            text-align: center;
            margin: 35px 0;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 18px 35px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
            transition: transform 0.2s;
        }
        .button:hover {
            transform: translateY(-2px);
        }
        .help-section {
            background-color: #f1f5f9;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            text-align: center;
        }
        .signature {
            margin-top: 40px;
            padding-top: 25px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
        }
        .signature .name {
            font-weight: 700;
            color: #1e40af;
            font-size: 18px;
        }
        .signature .title {
            color: #64748b;
            margin-top: 5px;
            font-style: italic;
        }
        .footer {
            background-color: #f8fafc;
            padding: 25px;
            text-align: center;
            font-size: 13px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
        .footer a {
            color: #3b82f6;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>🎓 New Learning Opportunity!</h1>
        <p>Your professional development journey continues</p>
    </div>

    <div class="content">
        <div class="greeting">
            Hello {{ $userName }}, 👋
        </div>

        <div class="welcome-message">
            <h3>✨ Exciting News!</h3>
            <p>We've selected you for an exclusive learning opportunity that will enhance your skills and advance your career. This course has been carefully chosen to support your professional growth and our team's continued success. Please note that participation in this course is mandatory, and your progress will be reflected in the personal development section of your monthly evaluation.</p>
        </div>

        <div class="course-details">
            <h3>📚 Course Information</h3>
            <p><strong>Course:</strong> {{ $courseName }}</p>

            @if($description)
                <p><strong>What you'll learn:</strong></p>
                <div style="margin-left: 15px; color: #4b5563;">{!! $description !!}</div>
            @endif

            @if($course->level)
                <p><strong>Skill Level:</strong> {{ ucfirst($course->level) }}</p>
            @endif

            @if($course->duration)
                <p><strong>Time Investment:</strong> {{ $course->duration }} hours</p>
            @endif

            <p><strong>Current Status:</strong> Ready to begin</p>
        </div>

        @if($availabilities && $availabilities->count() > 0)
            <div class="date-section">
                <h4>📅 Choose Your Preferred Schedule</h4>
                <p>We've arranged flexible options to fit your busy schedule. Please select the time that works best for you:</p>

                @foreach($availabilities->take(2) as $index => $availability)
                    <div class="date-option">
                        <strong>Schedule {{ $index + 1 }}:</strong>
                        {{ \Carbon\Carbon::parse($availability->start_date)->format('l, F j, Y') }}
                        <br>
                        <small>{{ \Carbon\Carbon::parse($availability->start_date)->format('g:i A') }}
                            @if($availability->end_date && $availability->start_date != $availability->end_date)
                                - {{ \Carbon\Carbon::parse($availability->end_date)->format('g:i A') }}
                            @endif</small>
                    </div>
                @endforeach

                <p style="margin-top: 20px; color: #b45309;">
                    <strong>⏰ Please select your preferred schedule soon</strong> - spaces fill up quickly!
                </p>
            </div>
        @endif

        <div class="login-section">
            <h4>🔐 Your Personal Access Details</h4>
            <p>Everything you need to get started right away:</p>

            <div class="credential-box">
                <strong>🌐 Course Platform:</strong><br>
                {{ config('app.url') }}/courses/{{ $course->id }}
            </div>
            <div class="credential-box">
                <strong>👤 Username:</strong><br>
                {{ $userEmail }}
            </div>
            <div class="credential-box">
                <strong>🔑 Password:</strong><br>
                {{ $userPassword }}
            </div>
        </div>

        <div class="cta-section">
            <a href="{{ url('/courses/' . $course->id) }}" class="button">
                🚀 Start Learning Now
            </a>
        </div>

        <div class="help-section">
            <p><strong>Need help?</strong> 🤝</p>
            <p>Our support team is here for you! If you have any questions or run into any issues, just reach out to <a href="mailto:harry@pneunited.com" style="color: #3b82f6; font-weight: 600;">harry@pneunited.com</a></p>
            <p><small>We typically respond within 2 hours during business hours</small></p>
        </div>

        <p style="color: #374151; font-size: 16px;">
            We're excited to support your learning journey and can't wait to see the amazing things you'll accomplish! 🌟
        </p>

        <div class="signature">
            <p class="name">Harry Prescott</p>
            <p class="title">Learning & Development Instructor<br>
                The Development Zone Department</p>
            <p style="color: #3b82f6; margin-top: 10px;">📧 harry@pneunited.com</p>
        </div>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} The Development Zone Department | Empowering Growth Through Learning</p>
        <p>This personalized learning invitation was sent to <a href="mailto:{{ $userEmail }}">{{ $userEmail }}</a></p>
        <p style="margin-top: 15px;">
            <a href="{{ config('app.url') }}">Visit our Learning Portal</a> |
            <a href="mailto:harry@pneunited.com">Contact Support</a>
        </p>
    </div>
</div>
</body>
</html>
