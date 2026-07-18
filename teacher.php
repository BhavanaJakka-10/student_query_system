<?php
include 'db.php';

$count = mysqli_query($conn,
        "SELECT * FROM queries WHERE status='Pending'");
$total = mysqli_num_rows($count);

$result = mysqli_query($conn,
        "SELECT * FROM queries ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Teacher Dashboard</title>
</head>
<body>

<h2>Teacher Dashboard</h2>

<h3>🔔 Pending Queries: <?php echo $total; ?></h3>

<table border="1" cellpadding="10">
<tr>
    <th>ID</th>
    <th>Subject</th>
    <th>Query</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php
while($row = mysqli_fetch_assoc($result))
{
?>
<tr>
    <td><?php echo $row['id']; ?></td>
    <td><?php echo $row['subject']; ?></td>
    <td><?php echo $row['message']; ?></td>
    <td><?php echo $row['status']; ?></td>
    <td>
        <a href="reply.php?id=<?php echo $row['id']; ?>">
            Reply
        </a>
    </td>
</tr>
<?php
}
?>

</table>

</body>
</html>