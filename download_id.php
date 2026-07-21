<?php
session_start();

if(!isset($_SESSION['student']))
{
    header("Location: student_login.php");
    exit();
}

require_once "../config/config.php";

$id=$_SESSION['student'];

$query="
SELECT users.name,
student_profile.photo,
student_academic.prn,
student_academic.department,
student_academic.semester

FROM users

LEFT JOIN student_profile
ON users.user_id=student_profile.student_id

LEFT JOIN student_academic
ON users.user_id=student_academic.student_id

WHERE users.user_id='$id'
";

$result=mysqli_query($conn,$query);
$student=mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>ID Card</title>

<style>

body{
font-family:Arial;
display:flex;
justify-content:center;
padding:40px;
background:#eee;
}

.card{

width:340px;
border:3px solid #0d47a1;
border-radius:12px;
padding:20px;
background:white;
text-align:center;

}

.card img{
border-radius:50%;
width:100px;
height:100px;
object-fit:cover;
}

h2{
color:#0d47a1;
}

button{
margin-top:20px;
padding:10px 20px;
cursor:pointer;
}

@media print{

button{
display:none;
}

body{
background:white;
}

}

</style>

</head>

<body>

<div class="card">

<h2>STUDENT ID CARD</h2>

<?php
if(!empty($student['photo']))
{
echo "<img src='../assets/uploads/photos/".$student['photo']."'>";
}
?>

<h3><?php echo $student['name']; ?></h3>

<p><b>PRN :</b> <?php echo $student['prn']; ?></p>

<p><b>Department :</b> <?php echo $student['department']; ?></p>

<p><b>Semester :</b> <?php echo $student['semester']; ?></p>

<img src="../assets/images/qr.png" width="90">

<br>

<button onclick="window.print()">

Download / Print

</button>

</div>

</body>
</html>