<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Course Access Link Restored</title>
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
        .course-info {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            border: 1px solid #e2e8f0;
        }
        .course-info h4 {
            color: #1e40af;
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        .access-section {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #0ea5e9;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .access-section h4 {
            color: #0c4a6e;
            margin: 0 0 15px 0;
            font-size: 16px;
        }
        .login-button {
            display: inline-block;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white !important;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 2px 10px rgba(59, 130, 246, 0.2);
            margin: 15px 0;
        }
        .login-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        .security-info {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
            font-size: 14px;
            color: #374151;
            text-align: left;
        }
        .important-note {
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
            border: 1px solid #ef4444;
            border-radius: 6px;
            padding: 20px;
            margin: 25px 0;
        }
        .important-note h4 {
            color: #dc2626;
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: 600;
        }
        .help-section {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            border: 1px solid #e2e8f0;
            text-align: center;
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
        <h1>🔑 Course Access Restored</h1>
    </div>

    <div class="content">
        <div class="greeting">
            Hello {{ $userName }},
        </div>

        <div class="notification-badge">
            <h3>📚 Access Link Refreshed</h3>
            <p>Your previous course access link has expired or was not working. I've generated a fresh, secure access link for you to continue your learning journey without any interruption.</p>
        </div>

        <div class="course-info">
            <h4>📋 Course Access Details</h4>
            <p><strong>Course:</strong> {{ $courseName }}</p>
            <p><strong>Your Email:</strong> {{ $userEmail }}</p>
            <p><strong>Status:</strong> Ready to access</p>
            <p><strong>Link Generated:</strong> {{ date('F j, Y \a\t g:i A') }}</p>
        </div>

        <div class="access-section">
            <h4>🔐 Your New Secure Access Link</h4>
            <p>Click the button below to access your course with your new secure link:</p>

            <a href="{{ $loginLink }}" class="login-button">
                🚀 Access Your Course Now
            </a>

            <div class="security-info">
                <strong>🛡️ Security Information:</strong><br>
                • This is a brand new, secure access link<br>
                • The link expires after 24 hours for your protection<br>
                • It can only be used once and will log you in automatically<br>
                • No password required - just click and start learning
            </div>
        </div>

        <p>This new access link will get you back on track with your learning goals. The system has been updated to ensure you have seamless access to all your course materials and progress tracking.</p>

        <div class="important-note">
            <h4>⚠️ Important Reminder</h4>
            <p>Please use this new link within 24 hours of receiving this email. After this time, you'll need to request another access link for security purposes.</p>
        </div>

        <div class="help-section">
            <p><strong>Need Additional Support?</strong></p>
            <p>If you continue to experience any issues accessing your course, please don't hesitate to contact me directly. I typically respond within 2 hours during business hours.</p>
        </div>

        <p>I apologize for any inconvenience with your previous access link. Your continued participation in this professional development opportunity is important, and I'm here to ensure you have the support you need to succeed.</p>

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
        <p>This access link was sent to {{ $userEmail }}</p>
    </div>
</div>
</body>
</html>
