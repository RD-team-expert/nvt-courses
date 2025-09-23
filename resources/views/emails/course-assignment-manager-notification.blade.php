<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employee Selected for Professional Development Course</title>
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
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
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
        .notification-badge {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .notification-badge h3 {
            color: #1e40af;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .employee-details {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            border: 1px solid #e2e8f0;
        }
        .employee-details h4 {
            color: #1e40af;
            margin: 0 0 15px 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .employee-item {
            background-color: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 15px;
            margin: 10px 0;
        }
        .employee-item strong {
            color: #374151;
        }
        .employee-item small {
            color: #6b7280;
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
        .important-note {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 20px;
            margin: 25px 0;
        }
        .important-note h4 {
            color: #92400e;
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
            .content {
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>📋 Employee Professional Development Notification</h1>
    </div>

    <div class="content">
        <div class="greeting">
            Dear {{ $manager->name }},
        </div>

        <div class="notification-badge">
            <h3>Team Member Development Update</h3>
            <p>I'm pleased to inform you that a member of your team has been selected for an exclusive learning opportunity designed to enhance their skills and support their career growth.</p>
        </div>

        <div class="employee-details">
            <h4>👥 Selected Team Member{{ $userCount > 1 ? 's' : '' }}</h4>
            @foreach($assignedUsers as $user)
                <div class="employee-item">
                    <strong>{{ $user->name }}</strong><br>
                    <small>{{ $user->email }}
                        @if($user->department)
                            • {{ $user->department->name }} Department
                        @endif
                    </small>
                </div>
            @endforeach
        </div>

        <div class="course-info">
            <h4>📚 Course Information</h4>
            <p><strong>Course:</strong> {{ $course->name }}</p>
            <p><strong>Assigned by:</strong> {{ $assignedBy->name }}</p>
            <p><strong>Assignment Date:</strong> {{ $assignmentDate }}</p>
            @if($course->description)
                <p><strong>Course Description:</strong> {{ $course->description }}</p>
            @endif
            @if($course->level)
                <p><strong>Level:</strong> {{ ucfirst($course->level) }}</p>
            @endif
            @if($course->duration)
                <p><strong>Duration:</strong> {{ $course->duration }} hours</p>
            @endif
        </div>

        <p>This course has been carefully chosen to contribute to their personal development and to our team's continued success.</p>

        <div class="important-note">
            <h4>📌 Important Information</h4>
            <p><strong>Please note that participation in this course is mandatory</strong> for the employee, and their progress will be reflected in the <strong>personal development section</strong> of their monthly evaluation.</p>
        </div>

        <p>The selected team member{{ $userCount > 1 ? 's' : '' }} will receive detailed course information and access instructions via email. They will be able to:</p>

        <ul style="margin: 15px 0; padding-left: 25px; color: #374151;">
            <li>Track their completion status</li>
            <li>Track their progress with ease</li>
            <li>Get introduced to the new Clock In/Clock Out system</li>
            <li>Explore an overview of the quizzes section we’ve recently added</li>
        </ul>

        <p>Thank you for supporting your team member{{ $userCount > 1 ? 's' : '' }} as they take part in this important development initiative. Your encouragement and support will be valuable in helping them succeed in this learning opportunity.</p>

        <p>If you have any questions about this course assignment or need additional information, please feel free to reach out.</p>

        <div class="signature">
            <p class="name">Best regards,<br>Harry Prescott</p>
            <p class="title">Instructor<br>The Development Zone Department</p>
            <p style="color: #3b82f6; margin-top: 8px; font-size: 14px;">📧 harry@pneunited.com</p>
        </div>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} The Development Zone Department</p>
        <p>This notification was sent regarding professional development for your team member{{ $userCount > 1 ? 's' : '' }}.</p>
    </div>
</div>
</body>
</html>
