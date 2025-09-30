<!DOCTYPE html>
<html>
<head>
    <title>{{ $subject ?? 'Personal Development Evaluation Results' }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .email-container {
            max-width: 800px;
            margin: 20px auto;
            background: #ffffff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 300;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            margin-bottom: 20px;
            font-size: 16px;
        }
        .intro-text {
            background: #f8f9ff;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
            font-style: italic;
        }
        .custom-message {
            background: #e7f3ff;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #2196F3;
            border-radius: 4px;
        }
        .data-section {
            margin: 30px 0;
        }
        .data-section h2 {
            color: #667eea;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        th {
            background: #667eea;
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: 500;
        }
        td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .signature {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9ff;
            border-radius: 5px;
        }
        .signature-name {
            font-weight: bold;
            color: #667eea;
        }
        .signature-title {
            color: #666;
            font-size: 14px;
        }
        .footer {
            background: #f8f9fa;
            padding: 25px;
            text-align: center;
            color: #666;
            border-top: 3px solid #667eea;
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>Personal Development Evaluation Results for Your Team</h1>
    </div>

    <div class="content">
        <div class="greeting">
            <strong>Dear {{ $manager['name'] }},</strong>
        </div>

        <p>I hope this message finds you well.</p>

        <div class="intro-text">
            As part of our ongoing commitment to supporting employee growth, I am sharing with you the evaluation results for your team members' Personal Development section. This information is intended to provide insight into each individual's progress, strengths, and areas for further improvement.
        </div>

        @if($customMessage)
            <div class="custom-message">
                <h3 style="margin-top: 0; color: #2196F3;">Additional Message:</h3>
                <p style="margin-bottom: 0;">{{ $customMessage }}</p>
            </div>
        @endif

        <div class="data-section">
            <h2>Team Member Evaluation Summary</h2>

            <table>
                <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Department</th>
                    <th>Course</th>
                    <th>Score</th>
                    <th>Incentive Amount</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                @foreach($employees as $employee)
                    @foreach($employee->evaluations as $evaluation)
                        <tr>
                            <td><strong>{{ $employee->name }}</strong></td>
                            <td>{{ $employee->department?->name ?? 'N/A' }}</td>
                            <td>{{ $evaluation->course?->name ?? 'N/A' }}</td>
                            <td>{{ $evaluation->total_score }}</td>
                            <td>${{ number_format($evaluation->incentive_amount, 2) }}</td>
                            <td>{{ $evaluation->created_at->format('M d, Y') }}</td>
                        </tr>
                    @endforeach
                @endforeach
                </tbody>
            </table>
        </div>

        <div style="background: #f8f9ff; padding: 20px; border-radius: 5px; margin: 25px 0;">
            <p style="margin: 0;"><strong>Your Role in Development:</strong></p>
            <p style="margin: 10px 0 0 0;">We greatly appreciate your dedication to fostering the professional development of your team. Your role as a direct manager is essential in creating an environment where employees feel supported, motivated, and empowered to grow.</p>
        </div>

        <p>If you have any questions about the results, or would like guidance on how best to use this information during your discussions, please don't hesitate to reach out.</p>

        <p>Thank you as always for your leadership and commitment.</p>

        <div class="signature">
            <div class="signature-name">Best regards,</div>
            <div class="signature-name">Harry Prescott</div>
            <div class="signature-title">Instructor</div>
            <div class="signature-title">The Development Zone Department</div>
            <div style="margin-top: 10px; font-size: 12px; color: #999;">
                This report was generated automatically on {{ now()->format('F j, Y') }}
            </div>
        </div>
    </div>

    <div class="footer">
        <p style="margin: 0; font-size: 14px;">
            This is an automated message from the Employee Development System.<br>
            For technical support, please contact the IT department.
        </p>
    </div>
</div>
</body>
</html>
