
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

        /* ✅ NEW: Deadline-specific styles */
        .deadline-critical { background: #fee2e2; border: 3px solid #dc2626; padding: 20px; border-radius: 8px; margin: 25px 0; animation: urgentPulse 2s infinite; }
        .deadline-urgent { background: #fef3c7; border: 2px solid #f59e0b; padding: 20px; border-radius: 8px; margin: 25px 0; }
        .deadline-warning { background: #dbeafe; border: 2px solid #3b82f6; padding: 20px; border-radius: 8px; margin: 25px 0; }
        .deadline-normal { background: #f0fdf4; border-left: 4px solid #059669; padding: 20px; border-radius: 8px; margin: 25px 0; }

        @keyframes urgentPulse {
            0% { border-color: #dc2626; box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7); }
            50% { border-color: #ef4444; box-shadow: 0 0 0 10px rgba(220, 38, 38, 0); }
            100% { border-color: #dc2626; box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
        }

        .course-info { background: #f8fafc; border: 2px solid #e2e8f0; padding: 25px; border-radius: 8px; margin: 25px 0; }
        .cta-button { display: inline-block; background: linear-gradient(135deg, #059669, #047857); color: white !important; padding: 15px 30px; text-decoration: none !important; border-radius: 8px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2); transition: transform 0.2s; }
        .cta-button:hover { transform: translateY(-2px); color: white !important; }

        /* ✅ NEW: Urgent CTA button style */
        .cta-button-urgent { background: linear-gradient(135deg, #dc2626, #b91c1c); animation: buttonPulse 1.5s infinite; color: white !important; }
        .cta-button-warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white !important; }

        @keyframes buttonPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .benefits-box { background: #f0f9ff; border-left: 4px solid #0ea5e9; padding: 20px; border-radius: 8px; margin: 25px 0; }

        /* ✅ NEW: Study plan box */
        .study-plan { background: #ecfdf5; border-left: 4px solid #059669; padding: 20px; border-radius: 8px; margin: 25px 0; }

        .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; background: #f8fafc; margin-top: 20px; border-radius: 8px; }
        .highlight { color: #dc2626; font-weight: bold; }

        /* ✅ NEW: Time remaining display */
        .time-display { font-size: 24px; font-weight: bold; text-align: center; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .time-critical { background: #fee2e2; color: #dc2626; }
        .time-urgent { background: #fef3c7; color: #f59e0b; }
        .time-warning { background: #dbeafe; color: #3b82f6; }
        .time-normal { background: #f0fdf4; color: #059669; }

        /* ✅ NEW: Progress tracker */
        .progress-tracker { background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; margin: 20px 0; }

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

        @if($hasDeadline)
            <!-- ✅ NEW: Header deadline display -->
            <div style="background: rgba(255,255,255,0.2); padding: 10px; border-radius: 6px; margin-top: 15px;">
                @if($urgencyLevel === 'critical')
                    <span style="font-size: 18px;">🚨 DUE TODAY - Complete Immediately!</span>
                @elseif($urgencyLevel === 'high')
                    <span style="font-size: 18px;">⚠️ Due Soon: {{ $deadlineFormatted['short_date'] }}</span>
                @else
                    <span style="font-size: 16px;">📅 Due: {{ $deadlineFormatted['short_date'] }}</span>
                @endif
            </div>
        @endif
    </div>

    <div class="content">
        <p>Hi <strong>{{ $user->name }}</strong>,</p>

        <!-- ✅ NEW: Critical Deadline Alert -->
        @if($hasDeadline)
            @php
                $deadlineClass = match($urgencyLevel) {
                    'critical' => 'deadline-critical',
                    'high' => 'deadline-urgent',
                    'medium' => 'deadline-warning',
                    default => 'deadline-normal'
                };

                $timeClass = match($urgencyLevel) {
                    'critical' => 'time-critical',
                    'high' => 'time-urgent',
                    'medium' => 'time-warning',
                    default => 'time-normal'
                };
            @endphp

            <div class="{{ $deadlineClass }}">
                <h3 style="text-align: center; margin: 0 0 15px 0;">
                    @if($urgencyLevel === 'critical')
                        🚨 URGENT - Course Due Today!
                    @elseif($urgencyLevel === 'high')
                        ⏰ Course Due Very Soon!
                    @elseif($urgencyLevel === 'medium')
                        📅 Course Deadline Approaching
                    @else
                        📋 Course Deadline Information
                    @endif
                </h3>

                @if($timeRemaining)
                    <div class="time-display {{ $timeClass }}">
                        {{ $timeRemaining['message'] }}
                    </div>
                @endif

                @if($urgencyMessage)
                    <p style="text-align: center; font-weight: bold; margin: 15px 0;">{{ $urgencyMessage }}</p>
                @endif

                @if($actionMessage)
                    <p style="text-align: center; font-size: 16px; margin: 10px 0;"><strong>{{ $actionMessage }}</strong></p>
                @endif
            </div>
        @endif

        <div class="exclusive-notice">
            <h3>🌟 Congratulations on Your Selection!</h3>
            <p><strong>We've selected you for an exclusive online course</strong> designed to enhance your skills and support your professional growth. This course has been carefully chosen to help you advance your career while contributing to our team's continued success.</p>
        </div>

        <div class="mandatory-alert">
            <h3>⚠️ Important Notice</h3>
            <p class="highlight">Please note that completing this course is mandatory</p>
            <p>Your progress will be tracked and reflected in the <strong>personal development section of your monthly evaluation</strong>.</p>
            @if($hasDeadline)
                <p style="font-size: 18px; margin-top: 15px;"><strong>Deadline: {{ $deadlineFormatted['full_date'] }}</strong></p>
            @endif
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
                <p><strong>Estimated Duration:</strong> {{ $course->estimated_duration }} minutes</p>
            @endif

            <p><strong>Modules:</strong> {{ $course->modules_count ?? $course->modules->count() ?? 'Multiple' }} learning modules</p>

            <p><strong>Assignment Date:</strong> {{ now()->format('M d, Y') }}</p>

            @if($hasDeadline)
                <p><strong>Course Deadline:</strong>
                    <span style="color: {{ $urgencyLevel === 'critical' ? '#dc2626' : ($urgencyLevel === 'high' ? '#f59e0b' : '#059669') }}; font-weight: bold;">
                        {{ $deadlineFormatted['full_date'] }}
                    </span>
                </p>

                @if($deadlineFormatted['is_today'])
                    <p style="color: #dc2626; font-weight: bold; font-size: 16px;">⚠️ This course is due TODAY!</p>
                @elseif($deadlineFormatted['is_tomorrow'])
                    <p style="color: #f59e0b; font-weight: bold;">⚠️ This course is due TOMORROW!</p>
                @endif

                <p><strong>Deadline Type:</strong> {{ ucfirst($deadlineType) }}
                    @if($deadlineType === 'strict')
                        <span style="color: #dc2626;">(Access blocked after deadline)</span>
                    @else
                        <span style="color: #059669;">(Late completion tracked)</span>
                    @endif
                </p>
            @endif
        </div>

        <!-- ✅ NEW: Study Planning Section -->
        @if($hasDeadline && $completionGuidance && isset($completionGuidance['daily_commitment']))
            <div class="study-plan">
                <h3>📚 Your Personal Study Plan</h3>
                <p>Based on your deadline, here's how to successfully complete this course:</p>
                <ul>
                    <li><strong>Daily Study Time:</strong> {{ $completionGuidance['daily_commitment']['minutes_per_day'] }} minutes per day</li>
                    <li><strong>Recommended Schedule:</strong> {{ $completionGuidance['daily_commitment']['sessions_suggested'] }}</li>
                    <li><strong>Success Strategy:</strong> {{ $completionGuidance['daily_commitment']['recommendation'] }}</li>
                </ul>

                @if($urgencyLevel === 'critical')
                    <p style="color: #dc2626; font-weight: bold;">⚠️ Due to the urgent deadline, consider dedicating focused time today to complete the course.</p>
                @elseif($urgencyLevel === 'high')
                    <p style="color: #f59e0b; font-weight: bold;">⚠️ Start immediately to ensure comfortable completion by the deadline.</p>
                @endif
            </div>
        @endif

        <div class="benefits-box">
            <h3>💡 Why This Course Matters</h3>
            <ul>
                <li><strong>Career Advancement:</strong> Develops skills directly applicable to your role</li>
                <li><strong>Professional Growth:</strong> Enhances your expertise and marketability</li>
                <li><strong>Team Contribution:</strong> Strengthens our collective capabilities</li>
                <li><strong>Evaluation Impact:</strong> Positive reflection in your development assessment</li>
                @if($hasDeadline)
                    <li><strong>Deadline Compliance:</strong> Demonstrates time management and commitment</li>
                @endif
            </ul>
        </div>

        <p>This is a self-paced online course. You can start immediately and learn at your own schedule, but remember that <strong>completion is required</strong>
            @if($hasDeadline)
                <span style="color: #dc2626; font-weight: bold;">by {{ $deadlineFormatted['short_date'] }}</span>
            @endif
            .</p>

        <!-- ✅ ENHANCED: Smart CTA Button -->
        <div style="text-align: center; margin: 30px 0;">
            @php
                $buttonClass = 'cta-button';
                if ($urgencyLevel === 'critical') {
                    $buttonClass .= ' cta-button-urgent';
                } elseif ($urgencyLevel === 'high') {
                    $buttonClass .= ' cta-button-warning';
                }

                $buttonText = match($urgencyLevel) {
                    'critical' => '🚨 START NOW - Due Today!',
                    'high' => '⚠️ Start Immediately',
                    'medium' => '📅 Begin Learning',
                    default => '🚀 Start Learning Now'
                };
            @endphp

            @if($loginLink)
                <a href="{{ $loginLink }}" class="{{ $buttonClass }}" style="color: #ffffff !important; text-decoration: none !important;">{{ $buttonText }}</a>
            @else
                <a href="{{ route('courses-online.index') }}" class="{{ $buttonClass }}" style="color: #ffffff !important; text-decoration: none !important;">{{ $buttonText }}</a>
            @endif

            @if($hasDeadline && $timeRemaining)
                <p style="margin: 15px 0 0 0; font-size: 14px; color: #666;">
                    {{ $timeRemaining['message'] }}
                </p>
            @endif
        </div>

        <!-- ✅ NEW: Progress Tracking Information -->
        @if($hasDeadline)
            <div class="progress-tracker">
                <h3>📊 Your Progress Will Be Monitored</h3>
                <ul style="margin: 10px 0;">
                    <li>Real-time progress tracking for deadline compliance</li>
                    <li>Automatic notifications to your manager about completion status</li>
                    <li>Deadline adherence reflected in your performance evaluation</li>
                    @if($deadlineType === 'strict')
                        <li style="color: #dc2626; font-weight: bold;">Course access will be blocked after the deadline</li>
                    @else
                        <li>Late completion will be noted in your development record</li>
                    @endif
                </ul>
            </div>
        @endif

        <p><strong>📋 Getting Started:</strong></p>
        <ul>
            <li>Click the button above to access the course</li>
            <li>Complete modules in order to track progress</li>
            <li>Your progress is automatically saved and monitored</li>
            <li>You can continue where you left off anytime</li>
            <li><strong>Ensure completion
                    @if($hasDeadline)
                        by {{ $deadlineFormatted['short_date'] }}
                    @else
                        within the designated timeframe
                    @endif
                </strong></li>
        </ul>

        <p><strong>📊 Course Features:</strong></p>
        <ul>
            <li>Self-paced online learning</li>
            <li>Progress automatically tracked for evaluation</li>
            <li>Available 24/7 from any device</li>
            <li>Interactive content and assessments</li>
            @if($hasDeadline)
                <li><strong style="color: #dc2626;">Deadline monitoring and compliance tracking</strong></li>
            @endif
        </ul>

        <!-- ✅ NEW: Time Management Tips -->
        @if($hasDeadline)
            <div style="background: #fffbeb; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 6px; margin: 20px 0;">
                <h4 style="margin: 0 0 10px 0; color: #92400e;">⏰ Time Management Tips:</h4>
                <ul style="margin: 0; color: #92400e;">
                    @if($urgencyLevel === 'critical')
                        <li>Block out dedicated time TODAY to complete the course</li>
                        <li>Minimize distractions and focus on completion</li>
                        <li>Contact your manager if you need support to meet the deadline</li>
                    @elseif($urgencyLevel === 'high')
                        <li>Start within the next few hours</li>
                        <li>Break the course into manageable sessions</li>
                        <li>Set reminders to keep yourself on track</li>
                    @else
                        <li>Create a study schedule that fits your routine</li>
                        <li>Set aside consistent time each day</li>
                        <li>Track your progress regularly</li>
                    @endif
                </ul>
            </div>
        @endif

        <p>We're excited to support your professional development journey. Your commitment to completing this course
            @if($hasDeadline)
                by the deadline
            @endif
            demonstrates your dedication to growth and excellence.</p>

        <p>If you have any questions about this assignment, please contact <strong>{{ $assignedBy->name }}</strong> or your direct manager.</p>

        <p>Best of luck with your learning!</p>
    </div>

    <div class="footer">
        <p>🎓 Invest in your future - Your career development matters!</p>
        @if($hasDeadline)
            <p style="color: #dc2626; font-weight: bold;">
                ⏰ Reminder: Complete by {{ $deadlineFormatted['short_date'] }}
                @if($timeRemaining)
                    - {{ $timeRemaining['message'] }}
                @endif
            </p>
        @endif
        <p>This email was sent automatically by the Learning Management System.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</div>
</body>
</html>
