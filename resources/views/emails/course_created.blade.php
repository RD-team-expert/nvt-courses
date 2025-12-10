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
            text-align: center;
        }
        .login-section h4 {
            color: #7c3aed;
            margin: 0 0 20px 0;
            font-size: 18px;
        }
        .login-button {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white !important;
            padding: 18px 35px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
            transition: transform 0.2s, box-shadow 0.2s;
            margin: 15px 0;
        }
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }
        .security-note {
            background-color: white;
            border: 1px solid #c4b5fd;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            font-size: 12px;
            color: #4c1d95;
            text-align: left;
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
        @media (max-width: 600px) {
            .content {
                padding: 20px 15px;
            }
            .login-button {
                padding: 15px 25px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>🎓 New Public Course Available!</h1>
        <p>Expand your skills with this exciting learning opportunity</p>
    </div>

    <div class="content">
        <div class="greeting">
            Hello {{ $userName }}, 👋
        </div>

        <div class="welcome-message">
            <h3>✨ New Course Available!</h3>
            <p>We're excited to announce a new public course that's now available for enrollment! This course is open to all team members and offers valuable skills development opportunities.</p>
        </div>

        <div class="course-details">
            <h3>📚 Course Information</h3>
            <p><strong>Course:</strong> {{ $courseName }}</p>

            @if($description)
                <p><strong>What you'll learn:</strong></p>
                <div style="margin-left: 15px; color: #4b5563;">{!! $description !!}</div>
            @endif

            @if(isset($course->level) && $course->level)
                <p><strong>Skill Level:</strong> {{ ucfirst($course->level) }}</p>
            @endif

            @if(isset($course->duration) && $course->duration)
                <p><strong>Time Investment:</strong> {{ $course->duration }} hours</p>
            @endif

            <p><strong>Enrollment:</strong> Open to all team members</p>
            <p><strong>Current Status:</strong> Available for enrollment</p>
        </div>

        @if(isset($availabilities) && $availabilities && $availabilities->count() > 0)
            <div class="date-section">
                <h4>📅 Available Session Schedules</h4>
                <p>Multiple session schedules are available. Choose the one that fits your calendar best:</p>

                @foreach($availabilities->take(3) as $index => $availability)
                    <div class="date-option">
                        <strong>Schedule {{ $index + 1 }}:</strong>
                        {{ \Carbon\Carbon::parse($availability->start_date)->format('l, F j, Y') }}
                        <br>
                        <small>{{ \Carbon\Carbon::parse($availability->start_date)->format('g:i A') }}
                            @if($availability->end_date && $availability->start_date != $availability->end_date)
                                - {{ \Carbon\Carbon::parse($availability->end_date)->format('g:i A') }}
                            @endif
                            @if($availability->sessions)
                                • {{ $availability->sessions }} sessions available
                            @endif
                        </small>
                        @if($availability->notes)
                            <br><em style="color: #78716c; font-size: 12px;">{{ $availability->notes }}</em>
                        @endif
                    </div>
                @endforeach

                <p style="margin-top: 20px; color: #b45309;">
                    <strong>⏰ Enroll soon to secure your preferred schedule</strong> - popular courses fill up quickly!
                </p>
            </div>
        @endif

        <div class="login-section">
            <h4>🔐 Quick Access to Enroll</h4>
            <p>Ready to join this course? Use the secure link below to access the course page and enroll instantly:</p>

            <a href="{{ $loginLink ?? url('/courses/' . (isset($course->id) ? $course->id : '')) }}" class="login-button">
                🚀 View Course & Enroll
            </a>

            @if(isset($loginLink))
                <div class="security-note">
                    <strong>🛡️ Security Information:</strong><br>
                    • This is a secure, personalized access link<br>
                    • The link expires after 24 hours for your protection<br>
                    • It can only be used once and will log you in automatically<br>
                    • No password required - just click and explore the course!
                </div>
            @endif

            <p style="color: #6366f1; font-size: 14px; margin-top: 20px;">
                <strong>Your Email:</strong> {{ $userEmail }}<br>
                <strong>Course Platform:</strong> {{ config('app.url') }}
            </p>
        </div>

        <div class="help-section">
            <p><strong>Questions about this course? 🤝</strong></p>
            <p>Our team is here to help! If you have any questions about the course content, schedule, or enrollment process, please reach out:</p>
            <p><a href="mailto:thedevelopmentzone@onepne.com" style="color: #3b82f6; font-weight: 600;">thedevelopmentzone@onepne.com</a></p>
            <p><small>We typically respond within 2 hours during business hours</small></p>
        </div>

        <p style="color: #374151; font-size: 16px; line-height: 1.6;">
            This is a fantastic opportunity to develop new skills and advance your career. We encourage you to explore the course details and consider enrolling if it aligns with your professional development goals! 🌟
        </p>

        <div class="signature">
            <p class="name">Harry Prescott</p>
            <p class="title">Director Of Training And Development<br>
                The Training And Development Department
</p>
            <p style="color: #3b82f6; margin-top: 10px;">📧 thedevelopmentzone@onepne.com</p>
        </div>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} The Training And Development Department
 | Empowering Growth Through Learning</p>
        <p>This course announcement was sent to <a href="mailto:{{ $userEmail }}">{{ $userEmail }}</a></p>
        <p style="margin-top: 15px;">
            <a href="{{ config('app.url') }}">Visit our Learning Portal</a> |
            <a href="mailto:thedevelopmentzone@onepne.com">Contact Support</a>
        </p>
    </div>
</div>
</body>
</html>
