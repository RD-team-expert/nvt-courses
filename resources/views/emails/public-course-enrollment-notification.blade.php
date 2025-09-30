<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employee Enrolled in Professional Development Course</title>
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
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>📚 Employee Professional Development</h1>
    </div>

    <div class="content">
        <div class="greeting">
            Dear {{ $manager->name }},
        </div>

        <div class="enrollment-badge">
            <h3>Professional Development Opportunity</h3>
            <p>I'm pleased to inform you that a member of your team has been selected for an exclusive learning opportunity designed to enhance their skills and support their career growth.</p>
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

        <div class="optional-note">
            <h4>📋 Course Information</h4>
            <p><strong>Please note that this course was offered as optional</strong>, and the employee has voluntarily chosen to enroll. Their progress will be recognized and reflected in the personal development section of their monthly evaluation.</p>
        </div>

        <p>Your team member will have access to comprehensive course materials and resources, allowing them to develop valuable skills that will benefit both their career advancement and your team's overall capabilities.</p>

        <p>Thank you for supporting your team member as they take part in this valuable development initiative. Your encouragement and leadership in fostering a culture of continuous learning are greatly appreciated.</p>

        <div class="signature">
            <p class="name">Best regards,<br>Harry Prescott</p>
            <p class="title">Instructor<br>The Development Zone Department</p>
            <p style="color: #3b82f6; margin-top: 8px; font-size: 14px;">📧 harry@pneunited.com</p>
        </div>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} The Development Zone Department</p>
        <p>This notification was sent regarding voluntary professional development enrollment by your team member.</p>
    </div>
</div>
</body>
</html>
