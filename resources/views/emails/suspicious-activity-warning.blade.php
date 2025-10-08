<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Activity Review</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #e74c3c;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #e74c3c;
            margin: 0;
            font-size: 28px;
        }
        .header p {
            color: #666;
            margin: 10px 0 0 0;
            font-size: 16px;
        }
        .warning-box {
            background: #fff5f5;
            border: 2px solid #feb2b2;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .warning-box h3 {
            color: #e53e3e;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .session-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .session-details h4 {
            color: #2d3748;
            margin-top: 0;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .label {
            font-weight: 600;
            color: #4a5568;
        }
        .value {
            color: #2d3748;
        }
        .risk-high {
            color: #e53e3e;
            font-weight: bold;
        }
        .risk-medium {
            color: #dd6b20;
            font-weight: bold;
        }
        .risk-low {
            color: #38a169;
            font-weight: bold;
        }
        .reasons-list {
            background: #fef5e7;
            border-left: 4px solid #f6ad55;
            padding: 15px;
            margin: 15px 0;
        }
        .reasons-list ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }
        .reasons-list li {
            margin: 5px 0;
            color: #c05621;
        }
        .recommendations {
            background: #f0fff4;
            border-left: 4px solid #68d391;
            padding: 15px;
            margin: 15px 0;
        }
        .recommendations h4 {
            color: #2f855a;
            margin-top: 0;
        }
        .recommendations ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }
        .recommendations li {
            margin: 5px 0;
            color: #2f855a;
        }
        .cta-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            color: white;
        }
        .cta-button {
            display: inline-block;
            background: white;
            color: #667eea;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 15px;
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            background: #f7fafc;
            transform: translateY(-2px);
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            color: #718096;
            font-size: 14px;
        }
        .footer p {
            margin: 5px 0;
        }
        .important-note {
            background: #ebf8ff;
            border: 2px solid #90cdf4;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .important-note h4 {
            color: #2b6cb0;
            margin-top: 0;
        }
        .important-note p {
            color: #2c5282;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
<div class="email-container">
    <!-- Header -->
    <div class="header">
        <h1>⚠️ Learning Activity Review</h1>
        <p>{{ $companyName }} Learning Management System</p>
    </div>

    <!-- Greeting -->
    <p>Dear {{ $userName }},</p>

    <p>We hope this message finds you well. We're reaching out regarding some unusual patterns we've detected in your recent learning activity that require your attention.</p>

    <!-- Warning Box -->
    <div class="warning-box">
        <h3>🚨 Activity Alert</h3>
        <p>Our learning analytics system has flagged one of your recent study sessions for review. This is a <strong>routine security measure</strong> to ensure the integrity of our learning environment and help you get the most out of your educational experience.</p>
    </div>

    <!-- Session Details -->
    <div class="session-details">
        <h4>📊 Session Details</h4>

        <div class="detail-row">
            <span class="label">Course:</span>
            <span class="value">{{ $courseName }}</span>
        </div>

        <div class="detail-row">
            <span class="label">Session Date:</span>
            <span class="value">{{ $sessionDate }}</span>
        </div>

        <div class="detail-row">
            <span class="label">Duration:</span>
            <span class="value">{{ $duration }}</span>
        </div>

        <div class="detail-row">
            <span class="label">Risk Level:</span>
            <span class="value
                    @if(strtolower($riskLevel) === 'critical' || strtolower($riskLevel) === 'high') risk-high
                    @elseif(strtolower($riskLevel) === 'medium') risk-medium
                    @else risk-low
                    @endif
                ">{{ $riskLevel }} (Score: {{ $riskScore }})</span>
        </div>
    </div>

    <!-- Reasons for flagging -->
    @if(!empty($reasons))
        <div class="reasons-list">
            <h4>🔍 Why This Session Was Flagged:</h4>
            <ul>
                @foreach($reasons as $reason)
                    <li>{{ $reason }}</li>
                @endforeach
            </ul>
            <p><em>Please note: These flags are automatically generated and may not indicate any wrongdoing.</em></p>
        </div>
    @endif

    <!-- Recommendations -->
    @if(!empty($recommendations))
        <div class="recommendations">
            <h4>💡 Recommendations for Better Learning:</h4>
            <ul>
                @foreach($recommendations as $recommendation)
                    <li>{{ $recommendation }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Important Note -->
    <div class="important-note">
        <h4>📝 Important Information</h4>
        <p>This is <strong>not an accusation</strong> of misconduct. We're committed to supporting your learning journey and want to ensure you have the best possible experience. Sometimes technical issues, distractions, or different learning styles can trigger these alerts.</p>
    </div>

    <!-- Call to Action -->
    <div class="cta-section">
        <h3>🤝 Let's Work Together</h3>
        <p>If you have any questions about this alert or need assistance with your learning, we're here to help!</p>
        <a href="mailto:{{ $supportEmail }}" class="cta-button">Contact Support</a>
    </div>

    <!-- Next Steps -->
    <h4>📋 What Happens Next?</h4>
    <ul>
        <li><strong>No immediate action required</strong> - Continue your learning as normal</li>
        <li>If you experienced technical issues during this session, please let us know</li>
        <li>Our team may reach out for a brief, friendly check-in</li>
        <li>We'll continue monitoring to help optimize your learning experience</li>
    </ul>

    <!-- Closing -->
    <p>Thank you for your attention to this matter and your commitment to learning. We appreciate your understanding and look forward to supporting your continued success.</p>

    <p>Best regards,<br>
        <strong>{{ $adminName }}</strong><br>
        Learning Analytics Team<br>
        {{ $companyName }}</p>

    <!-- Footer -->
    <div class="footer">
        <p>This email was sent by {{ $companyName }} Learning Management System</p>
        <p>If you have questions, reply to this email or contact: {{ $supportEmail }}</p>
        <p><em>This is an automated message, but replies are monitored by our support team.</em></p>
    </div>
</div>
</body>
</html>
