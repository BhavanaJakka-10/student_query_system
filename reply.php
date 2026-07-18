<?php
include 'db.php';

$id = $_GET['id'];

if(isset($_POST['submit']))
{
    $reply = $_POST['reply'];

    mysqli_query($conn,
    "UPDATE queries
     SET reply='$reply',
         status='Answered'
     WHERE id=$id");

    header("Location: teacher.php");
}

$data = mysqli_query($conn,
        "SELECT * FROM queries WHERE id=$id");

$row = mysqli_fetch_assoc($data);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reply Query</title>
</head>
<body>

<h2>Reply to Query</h2>

<p><b>Subject:</b> <?php echo $row['subject']; ?></p>
<p><b>Query:</b> <?php echo $row['message']; ?></p>

<form method="POST">
    <textarea name="reply" rows="5" cols="50" required></textarea>
    <br><br>

    <button type="submit" name="submit">
        Send Reply
    </button>
</form>

</body>
</html>