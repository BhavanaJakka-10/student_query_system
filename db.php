<?php
$conn = mysqli_connect("localhost", "root", "", "student_query_system");

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}
?>