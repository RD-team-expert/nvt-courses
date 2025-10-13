<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exclusive Team Course Assignment - Manager Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #7c3aed, #6366f1); color: white; padding: 25px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 30px 25px; background: white; border-radius: 0 0 8px 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .exclusive-notice { background: linear-gradient(135deg, #fef3c7, #fde68a); border-left: 4px solid #f59e0b; padding: 20px; border-radius: 8px; margin: 25px 0; }
        .mandatory-alert { background: #fee2e2; border: 2px solid #dc2626; padding: 20px; border-radius: 8px; margin: 25px 0; }
        .course-info { background: #f8fafc; border: 2px solid #e2e8f0; padding: 25px; border-radius: 8px; margin: 25px 0; }
        .team-members { background: #f0f9ff; border: 2px solid #0ea5e9; padding: 25px; border-radius: 8px; margin: 25px 0; }
        .member { padding: 15px; border-left: 4px solid #2563eb; margin: 15px 0; background: white; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .manager-actions { background: #f0fdf4; border-left: 4px solid #059669; padding: 20px; border-radius: 8px; margin: 25px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 14px; background: #f8fafc; margin-top: 20px; border-radius: 8px; }
        .highlight { color: #dc2626; font-weight: bold; }
        h1 { margin: 0; font-size: 24px; }
        h2 { color: #1e293b; margin-bottom: 15px; }
        h3 { color: #374151; margin-bottom: 10px; }
        ul { padding-left: 20px; }
        li { margin-bottom: 8px; }
        .emphasis { background: #fef3c7; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>👥 Exclusive Team Course Assignment</h1>
        <p style="margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;">Manager Notification - Professional Development</p>
    </div>

    <div class="content">
        <p>Hello <strong>{{ $manager->name }}</strong>,</p>

        <div class="exclusive-notice">
            <h3>🌟 Exclusive Selection Notification</h3>
            <p>This is to inform you that
                @if($teamMembers->count() === 1)
                    <strong>{{ $teamMembers->first()->name }}</strong> has been selected for an <span class="emphasis">exclusive online course</span>
                @else
                    <strong>{{ $teamMembers->count() }} of your team members</strong> have been selected for an <span class="emphasis">exclusive online course</span>
                @endif
                designed to enhance their skills and support professional growth.
            </p>
            <p>This course
                @if($teamMembers->count() === 1)
                    has been carefully chosen
                @else
                    has been carefully chosen
                @endif
                to help advance their careers while contributing to our team's continued success.
            </p>
        </div>

        <div class="mandatory-alert">
            <h3>⚠️ Important - Mandatory Completion Required</h3>
            <p><span class="highlight">Completing this course is mandatory</span> for
                @if($teamMembers->count() === 1)
                    your team member,
                @else
                    all assigned team members,
                @endif
                and their progress will be <strong>tracked and reflected in their personal development section of the monthly evaluation</strong>.
            </p>
            <p><strong>Please ensure that
                    @if($teamMembers->count() === 1)
                        {{ $teamMembers->first()->name }} has
                    @else
                        all assigned team members have
                    @endif
                    the necessary time and support to complete the course successfully.</strong>
            </p>
        </div>

        <p>Course assignment made by <strong>{{ $assignedBy->name }}</strong>.</p>

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
            <p><strong>Course Type:</strong> Self-paced online learning with mandatory completion</p>
        </div>

        <div class="team-members">
            <h3>🎯 Selected Team Members:</h3>
            @foreach($teamMembers as $member)
                <div class="member">
                    <strong>{{ $member->name }}</strong> - <span class="emphasis">Exclusive Selection</span><br>
                    <small>📧 {{ $member->email }}</small>
                    @if($member->department)
                        <br><small>🏢 {{ $member->department->name }}</small>
                    @endif
                    <br><small style="color: #dc2626; font-weight: bold;">⚠️ Mandatory completion required</small>
                </div>
            @endforeach
        </div>

        <div class="manager-actions">
            <h3>📋 Your Role as Manager - Enhanced Support Required</h3>
            <ul>
                <li><strong>Ensure Time Allocation:</strong> Guarantee adequate time for course completion</li>
                <li><strong>Monitor Progress:</strong> Follow up regularly due to mandatory requirement</li>
                <li><strong>Provide Support:</strong> Assist with any course-related challenges</li>
                <li><strong>Career Integration:</strong> Discuss how learning applies to their role and growth</li>
                <li><strong>Completion Recognition:</strong> Acknowledge successful completion for evaluation purposes</li>
                <li><strong>Evaluation Documentation:</strong> Prepare to include completion in performance reviews</li>
            </ul>
        </div>

        <p><strong>📊 Course Details:</strong></p>
        <ul>
            <li>Self-paced online learning with tracking</li>
            <li>Progress automatically monitored for evaluation</li>
            <li>Available 24/7 from any device</li>
            <li>Completion certificate provided</li>
            <li><strong>Mandatory completion impacts performance evaluation</strong></li>
        </ul>

        <p>The selected team
            @if($teamMembers->count() === 1)
                member has
            @else
                members have
            @endif
            been notified directly about this exclusive opportunity and can start immediately. Their progress will be closely tracked for both learning outcomes and evaluation purposes.</p>

        <p><strong>This exclusive selection reflects our investment in
                @if($teamMembers->count() === 1)
                    their
                @else
                    their
                @endif
                professional development and career advancement.</strong>
        </p>

        <p>If you have any questions about this assignment or need support in facilitating completion, please contact <strong>{{ $assignedBy->name }}</strong>.</p>

        <p>Best regards,<br><strong>Learning & Development Team</strong></p>
    </div>

    <div class="footer">
        <p>🎓 Supporting professional growth through exclusive learning opportunities</p>
        <p>This notification was sent automatically when course assignments were made.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</div>
</body>
</html>
