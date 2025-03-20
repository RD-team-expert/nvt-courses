<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Course Available</title>
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
        .course-details {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
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
        <h1>New Course Available</h1>
    </div>
    <div class="content">
        <p>Hello {{ $user->name }},</p>
        
        <p>We're excited to announce a new course that's now available on our platform:</p>
        
        <div class="course-details">
            <h2>{{ $course->name }}</h2>
            <p><strong>Start Date:</strong> {{ date('F j, Y', strtotime($course->start_date)) }}</p>
            <p><strong>End Date:</strong> {{ date('F j, Y', strtotime($course->end_date)) }}</p>
            <p><strong>Status:</strong> {{ ucfirst($course->status) }}</p>
            
            @if($course->level)
                <p><strong>Level:</strong> {{ ucfirst($course->level) }}</p>
            @endif
            
            @if($course->duration)
                <p><strong>Duration:</strong> {{ $course->duration }}</p>
            @endif
        </div>
        
        <p>Course Description:</p>
        <div>{!! $course->description !!}</div>
        
        <p>Interested in this course? Click the button below to view details and register:</p>
        
        <a href="{{ url('/courses/' . $course->id) }}" class="button">View Course Details</a>
        
        <p>If you have any questions, please don't hesitate to contact us.</p>
        
        <p>Best regards,<br>NVT Courses Team</p>
    </div>
    <div class="footer">
        <p>© {{ date('Y') }} NVT Courses.</p>
        <p>This email was sent to {{ $user->email }}</p>
        {{-- <p>If you prefer not to receive these notifications, you can update your preferences in your account settings.</p> --}}
    </div>
</body>
</html>