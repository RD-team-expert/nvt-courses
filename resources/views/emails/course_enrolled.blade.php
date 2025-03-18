<!-- resources/views/emails/course_enrolled.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Course Enrollment Confirmation</title>
</head>
<body>
<h1>Welcome, {{ $userName }}!</h1>
<p>You have successfully enrolled in the course: <strong>{{ $courseName }}</strong></p>
<p>We're excited to have you on board. Get started by visiting the course page!</p>
<p>Best regards,<br>Your Learning Team</p>
</body>
</html>
