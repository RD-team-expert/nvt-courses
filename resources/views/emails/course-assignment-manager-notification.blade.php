<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manager Notification - Course Assignment</title>
</head>
<body>
<h1>🎯 MANAGER NOTIFICATION TEST</h1>

<p>Hi ,</p>

<p><strong></strong> has assigned the course <strong>""</strong> to  member(s) of your team:</p>

<ul>
    @foreach($assignedUsers as $user)
        <li></li>
    @endforeach
</ul>

<hr>
<p><strong>Course Details:</strong></p>
<ul>
    <li>Course: </li>
    <li>Manager:</li>
    <li>Admin: </li>
    <li>Date: </li>
</ul>

<p>This is a test manager notification email.</p>

<p>Best regards,<br>Learning Management System</p>
</body>
</html>
