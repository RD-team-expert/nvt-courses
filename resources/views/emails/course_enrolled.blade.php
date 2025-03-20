<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Course Enrollment Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4f46e5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .enrollment-details {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            text-align: center;
        }
        .button {
            display: inline-block;
            background-color: #4f46e5;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to Your New Course!</h1>
    </div>
    <div class="content">
        <h2>Hello, {{ $userName }}! 👋</h2>
        
        <div class="enrollment-details">
            <h3>You've Successfully Enrolled In</h3>
            <p style="font-size: 1.2em;"><strong>{{ $courseName }}</strong></p>
        </div>
        
        <p>We're thrilled to have you on board! Your learning journey begins now, and we're here to support you every step of the way.</p>
        
        <p>To get started:</p>
        <ul>
            <li>Visit your course dashboard</li>
            <li>Review the course materials</li>
            <li>Set your learning goals</li>
        </ul>
        
        <p>If you have any questions, our support team is always here to help.</p>
        
        <p>Best regards</p>
    </div>
    <div class="footer">
        <p>© {{ date('Y') }} NVT Courses</p>
    </div>
</body>
</html>
