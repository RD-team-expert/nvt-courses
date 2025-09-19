<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your Course Access Link</title>
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
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
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
        .alert-message {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-left: 4px solid #dc2626;
            padding: 25px;
            margin: 25px 0;
            border-radius: 8px;
        }
        .alert-message h3 {
            color: #dc2626;
            margin: 0 0 15px 0;
            font-size: 18px;
        }
        .course-info {
            background-color: #f8fafc;
            padding: 25px;
            border-radius: 10px;
            margin: 25px 0;
            border: 1px solid #e2e8f0;
        }
        .course-info h3 {
            color: #1e40af;
            margin: 0 0 15px 0;
            font-size: 18px;
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
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>🔑 New Access Link</h1>
        <p>Your course access has been restored</p>
    </div>

    <div class="content">
        <div class="greeting">
            Hello {{ $userName }}, 👋
        </div>

        <div class="alert-message">
            <h3>⚠️ Access Link Refreshed</h3>
            <p>Your previous course access link has expired or was not working. We've generated a fresh, secure access link for you to continue your learning journey.</p>
        </div>

        <div class="course-info">
            <h3>📚 Course Access Details</h3>
            <p><strong>Course:</strong> {{ $courseName }}</p>
            <p><strong>Your Email:</strong> {{ $userEmail }}</p>
            <p><strong>Status:</strong> Ready to access</p>
        </div>

        <div class="login-section">
            <h4>🔐 Your New Secure Access Link</h4>
            <p>Click the button below to access your course with your new secure link:</p>

            <a href="{{ $loginLink }}" class="login-button">
                🚀 Access Your Course Now
            </a>

            <div class="security-note">
                <strong>🛡️ Security Information:</strong><br>
                • This is a brand new, secure access link<br>
                • The link expires after 24 hours for your protection<br>
                • It can only be used once and will log you in automatically<br>
                • No password required - just click and start learning!
            </div>
        </div>

        <div class="help-section">
            <p><strong>Still having trouble? 🤝</strong></p>
            <p>If you continue to experience any issues accessing your course, please don't hesitate to contact our support team:</p>
            <p><a href="mailto:harry@pneunited.com" style="color: #3b82f6; font-weight: 600;">harry@pneunited.com</a></p>
            <p><small>We typically respond within 2 hours during business hours</small></p>
        </div>

        <p style="color: #374151; font-size: 16px; line-height: 1.6;">
            We apologize for any inconvenience with your previous access link. This new link will get you back on track with your learning goals! 🌟
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
        <p>This new access link was sent to <a href="mailto:{{ $userEmail }}">{{ $userEmail }}</a></p>
        <p style="margin-top: 15px;">
            <a href="{{ config('app.url') }}">Visit our Learning Portal</a> |
            <a href="mailto:harry@pneunited.com">Contact Support</a>
        </p>
    </div>
</div>
</body>
</html>
