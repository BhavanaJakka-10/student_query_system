<?php
include 'db.php';

if(isset($_POST['submit']))
{
    $subject = $_POST['subject'];
    $message = $_POST['message'];

    $sql = "INSERT INTO queries(subject, message)
            VALUES('$subject', '$message')";

    if(mysqli_query($conn, $sql))
    {
        echo "<script>alert('Query Submitted Successfully');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Query Page</title>
</head>
<body>

<h2>Raise a Query</h2>

<form method="POST">

    Subject:<br>
    <input type="text" name="subject" required><br><br>

    Query:<br>
    <textarea name="message" rows="5" cols="40" required></textarea><br><br>

    <button type="submit" name="submit">Submit Query</button>
</form>

</body>
</html>