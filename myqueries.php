<?php
include 'db.php';

$result = mysqli_query($conn,
        "SELECT * FROM queries ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Replies</title>
</head>
<body>

<h2>Query Status</h2>

<table border="1" cellpadding="10">
<tr>
    <th>ID</th>
    <th>Subject</th>
    <th>Query</th>
    <th>Reply</th>
    <th>Status</th>
</tr>

<?php
while($row = mysqli_fetch_assoc($result))
{
?>
<tr>
    <td><?php echo $row['id']; ?></td>
    <td><?php echo $row['subject']; ?></td>
    <td><?php echo $row['message']; ?></td>
    <td>
        <?php
        if($row['reply'] == "")
            echo "No reply yet";
        else
            echo $row['reply'];
        ?>
    </td>
    <td><?php echo $row['status']; ?></td>
</tr>
<?php
}
?>

</table>

</body>
</html>