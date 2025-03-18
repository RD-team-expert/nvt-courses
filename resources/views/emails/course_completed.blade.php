<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Course Completion Congratulations</title>
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
        .achievement {
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
        <h1>Course Completion Achievement</h1>
    </div>
    <div class="content">
        <h2>Congratulations, {{ $userName }}! 🎉</h2>
        
        <div class="achievement">
            <h3>You've Successfully Completed</h3>
            <p style="font-size: 1.2em;"><strong>{{ $courseName }}</strong></p>
        </div>
        
        <p>We're proud of your dedication and commitment to learning. This achievement marks an important milestone in your educational journey.</p>
        
        <p>Your feedback is valuable to us and helps improve the learning experience for future students.</p>
        
        <p>Keep up the great work!</p>
        
        <p>Best regards</p>
    </div>
    <div class="footer">
        <p>© {{ date('Y') }} NVT Courses</p>
    </div>
</body>
</html>
