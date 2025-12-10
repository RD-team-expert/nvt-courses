<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Team Member Enrolled in Professional Development Course</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9fafb;
        }
        .email-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 16px;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .enrollment-badge {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .enrollment-badge h3 {
            color: #059669;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .employee-info {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            border: 1px solid #e2e8f0;
        }
        .employee-info h4 {
            color: #1e40af;
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        .employee-details {
            background-color: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 15px;
        }
        .course-info {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #0ea5e9;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .course-info h4 {
            color: #0c4a6e;
            margin: 0 0 15px 0;
            font-size: 16px;
        }

        /* ✅ NEW: Schedule Information Styles */
        .schedule-info {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 2px solid #f59e0b;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
        }
        .schedule-info h4 {
            color: #92400e;
            margin: 0 0 20px 0;
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .schedule-details {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.1);
        }
        .schedule-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
        }
        .schedule-item {
            background-color: #fffbeb;
            padding: 12px;
            border-radius: 6px;
            border-left: 3px solid #f59e0b;
        }
        .schedule-label {
            font-size: 12px;
            font-weight: 600;
            color: #92400e;
            text-transform: uppercase;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }
        .schedule-value {
            font-size: 16px;
            font-weight: 600;
            color: #451a03;
        }
        .days-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 5px;
        }
        .day-badge {
            background: #f59e0b;
            color: white;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
        }
        .date-info {
            background-color: #f8fafc;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 15px;
            margin-top: 15px;
        }

        .optional-note {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #22c55e;
            border-radius: 6px;
            padding: 20px;
            margin: 25px 0;
        }
        .optional-note h4 {
            color: #15803d;
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: 600;
        }
        .signature {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }
        .signature .name {
            font-weight: 600;
            color: #1f2937;
            font-size: 16px;
        }
        .signature .title {
            color: #6b7280;
            margin-top: 5px;
            font-size: 14px;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }

        @media (max-width: 600px) {
            .schedule-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>📚 Team Member Professional Development</h1>
    </div>

    <div class="content">
        <div class="greeting">
            Dear {{ $manager->name }},
        </div>

        <div class="enrollment-badge">
            <h3>✅ Enrollment Confirmation</h3>
            <p>I'm pleased to inform you that a member of your team has voluntarily enrolled in a professional development course designed to enhance their skills and support their career growth.</p>
        </div>

        <div class="employee-info">
            <h4>👤 Team Member Information</h4>
            <div class="employee-details">
                <strong>{{ $enrolledUser->name }}</strong><br>
                <small>{{ $enrolledUser->email }}
                    @if($enrolledUser->department)
                        • {{ $enrolledUser->department->name }} Department
                    @endif
                </small>
            </div>
        </div>

        <div class="course-info">
            <h4>📚 Course Details</h4>
            <p><strong>Course:</strong> {{ $course->name }}</p>
            <p><strong>Enrollment Date:</strong> {{ $enrollmentDate }}</p>
            @if($course->description)
                <p style="margin-top: 15px;"><strong>Course Description:</strong></p>
                <div style="margin-left: 15px; color: #4b5563; font-size: 14px;">{!! $course->description !!}</div>
            @endif
            @if($course->level)
                <p><strong>Skill Level:</strong> {{ ucfirst($course->level) }}</p>
            @endif
            @if($course->duration)
                <p><strong>Total Duration:</strong> {{ $course->duration }} hours</p>
            @endif
        </div>

        {{-- ✅ NEW: Selected Schedule Information --}}
        @if($selectedSchedule)
            <div class="schedule-info">
                <h4>📅 Selected Schedule</h4>
                <p style="color: #92400e; margin-bottom: 20px;">Your team member has selected the following session schedule:</p>

                <div class="schedule-details">
                    {{-- Schedule Grid --}}
                    <div class="schedule-grid">
                        {{-- Days of Week --}}
                        @if($selectedSchedule['formatted_days'] && $selectedSchedule['formatted_days'] !== 'TBD')
                            <div class="schedule-item">
                                <div class="schedule-label">📆 Days</div>
                                <div class="days-container">
                                    @foreach(explode(', ', $selectedSchedule['formatted_days']) as $day)
                                        <span class="day-badge">{{ $day }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Duration --}}
                        @if($selectedSchedule['duration_weeks'])
                            <div class="schedule-item">
                                <div class="schedule-label">⏱️ Duration</div>
                                <div class="schedule-value">{{ $selectedSchedule['duration_weeks'] }} weeks</div>
                            </div>
                        @endif

                        {{-- Session Time --}}
                        @if($selectedSchedule['formatted_session_time'])
                            <div class="schedule-item">
                                <div class="schedule-label">🕐 Start Time</div>
                                <div class="schedule-value">{{ $selectedSchedule['formatted_session_time'] }}</div>
                            </div>
                        @endif

                        {{-- Session Duration --}}
                        @if($selectedSchedule['formatted_session_duration'])
                            <div class="schedule-item">
                                <div class="schedule-label">⏰ Session Length</div>
                                <div class="schedule-value">{{ $selectedSchedule['formatted_session_duration'] }}</div>
                            </div>
                        @endif
                    </div>

                    {{-- Date Range Information --}}
                    <div class="date-info">
                        <div style="color: #374151; font-weight: 600; margin-bottom: 8px;">
                            📅 Course Period: {{ $selectedSchedule['formatted_date_range'] }}
                        </div>
                        @if($selectedSchedule['start_date'] && $selectedSchedule['end_date'])
                            <div style="color: #6b7280; font-size: 14px;">
                                <strong>Start:</strong> {{ \Carbon\Carbon::parse($selectedSchedule['start_date'])->format('l, F j, Y \a\t g:i A') }}<br>
                                <strong>End:</strong> {{ \Carbon\Carbon::parse($selectedSchedule['end_date'])->format('l, F j, Y \a\t g:i A') }}
                            </div>
                        @endif
                    </div>

                    {{-- Notes --}}
                    @if($selectedSchedule['notes'])
                        <div style="margin-top: 15px; padding: 15px; background-color: #f8fafc; border-radius: 6px; border-left: 3px solid #64748b;">
                            <div style="color: #64748b; font-size: 12px; font-weight: 600; margin-bottom: 5px;">📝 SCHEDULE NOTES</div>
                            <div style="color: #475569; font-size: 14px;">{{ $selectedSchedule['notes'] }}</div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <p>This course has been carefully chosen to contribute to their personal development and to our team's continued success.</p>

        <div class="optional-note">
            <h4>📋 Enrollment Information</h4>
            <p><strong>Please note that this course was offered as optional</strong>, and {{ $enrolledUser->name }} has voluntarily chosen to enroll. Their progress will be recognized and reflected in the personal development section of their monthly evaluation.</p>
        </div>

        <p>Your team member will have access to comprehensive course materials and resources, allowing them to develop valuable skills that will benefit both their career advancement and your team's overall capabilities.</p>

        <p>The selected schedule has been designed to minimize disruption to their regular work responsibilities while providing consistent learning opportunities.</p>

        <p>Thank you for supporting your team member as they take part in this valuable development initiative. Your encouragement and leadership in fostering a culture of continuous learning are greatly appreciated.</p>

        <div class="signature">
            <p class="name">Best regards,<br>Harry Prescott</p>
            <p class="title">Director Of Training And Development<br>The Training And Development Department
</p>
            <p style="color: #3b82f6; margin-top: 8px; font-size: 14px;">📧 thedevelopmentzone@onepne.com</p>
        </div>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} The Training And Development Department
</p>
        <p>This notification was sent regarding voluntary professional development enrollment by {{ $enrolledUser->name }}.</p>
    </div>
</div>
</body>
</html>
