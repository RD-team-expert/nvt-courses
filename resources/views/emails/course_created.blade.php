<!DOCTYPE html>
<html>
<head>
    <title>New Course Available</title>
</head>
<body>
<h1>Hello, {{ $userName }}!</h1>
<p>We’re excited to announce a new course: <strong>{{ $courseName }}</strong></p>
<p>{{ $description }}</p>
<p>Starts on: {{ $startDate ? $startDate->format('F j, Y') : 'TBA' }}</p>
<p>Check it out in your course dashboard!</p>
<p>Best regards,<br>Your Learning Team</p>
</body>
</html>
