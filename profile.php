
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if(!isset($_SESSION['student']))
{
    header("Location: student_login.php");
    exit();
}

require_once "../config/config.php";


$student_id = $_SESSION['student'];


// users + student_profile join

$query = "
SELECT 
users.name,
users.email,
student_profile.*,
student_academic.*

FROM users

LEFT JOIN student_profile
ON users.user_id = student_profile.student_id

LEFT JOIN student_academic
ON users.user_id = student_academic.student_id

WHERE users.user_id='$student_id'
";


$result = mysqli_query($conn,$query);


if(mysqli_num_rows($result)>0)
{
    $student = mysqli_fetch_assoc($result);
}
else
{
    die("Student Profile Not Found");
}

?>

<!DOCTYPE html>
<html>

<head>

<meta charset="UTF-8">

<title>Student Profile</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<link rel="stylesheet" href="../css/profile.css">
<style>
body{
background:#eef3fb;
}
</style>
</head>

<body>

<div class="container">

<div class="sidebar">

<img src="../assets/images/student.png">
<h2><?php echo $student['name']; ?></h2>

<p><?php echo $student['department']; ?></p>

<hr>

<ul>

<li><a href="student_dashboard.php"><i class="fa fa-house"></i> Dashboard</a></li>

<li class="active"><a href="#"><i class="fa fa-user"></i> Profile</a></li>

<li><a href="../logout.php"><i class="fa fa-right-from-bracket"></i> Logout</a></li>

</ul>

</div>

<div class="content">

<h1>Student Profile</h1>
<a href="edit_profile.php" class="edit-btn">
<i class="fa fa-edit"></i>
Edit Profile
</a>

<div class="profile-card">

<h2>Personal Information</h2>

<table>

<tr>

<th>Full Name</th>

<td><?php echo $student['name']; ?></td>

</tr>

<tr>

<tr>

<th>Roll Number</th>

<td><?php echo $student['roll']; ?></td>

</tr>
</tr>

<tr>

<th>PRN Number</th>

<td><?php echo $student['prn']; ?></td>

</tr>

<tr>

<th>ABC ID</th>

<td><?php echo $student['abc_id']; ?></td>

</tr>

<tr>

<th>Department</th>

<td><?php echo $student['department']; ?></td>

</tr>

<tr>

<th>Semester</th>

<td><?php echo $student['semester']; ?></td>

</tr>

<tr>

<th>Date of Birth</th>

<td><?php echo $student['dob']; ?></td>

</tr>

<tr>

<th>Gender</th>

<td><?php echo $student['gender']; ?></td>

</tr>

<tr>

<th>Blood Group</th>

<td><?php echo $student['blood_group']; ?></td>

</tr>

<tr>

<th>Mobile</th>

<td><?php echo $student['mobile']; ?></td>

</tr>

<tr>

<th>Email</th>

<td><?php echo $student['email']; ?></td>

</tr>

<tr>

<th>Aadhaar Number</th>

<td><?php echo $student['aadhaar_no']; ?></td>

</tr>

<tr>

<th>Address</th>

<td><?php echo $student['address']; ?></td>

</tr>

</table>

</div>

<div class="profile-card">

<h2>Parents Details</h2>

<table>

<tr>

<th>Father Name</th>

<td><?php echo $student['father_name']; ?></td>

</tr>

<tr>

<th>Mother Name</th>

<td><?php echo $student['mother_name']; ?></td>

</tr>

</table>

</div>

<div class="profile-card">

<h2>Academic Information</h2>

<table>

<tr>

<th>Attendance</th>

<td>91%</td>

</tr>

<tr>

<th>CGPA</th>

<td>8.75</td>

</tr>

<tr>

<th>Fee Status</th>

<td><span style="color:green;font-weight:bold;">Paid</span></td>

</tr>

<tr>

<th>Mentor</th>

<td>Prof. ABC XYZ</td>

</tr>

</table>

</div>
<!-- ================= Documents Section ================= -->

<div class="profile-card">

<h2><i class="fa-solid fa-folder-open"></i> Student Documents</h2>

<table>

<tr>
<th>Aadhaar Card</th>

<td>

<?php 
if(!empty($student['aadhaar_file']))
{
?>

<a href="../assets/uploads/aadhaar/<?php echo $student['aadhaar_file']; ?>" 
target="_blank" 
class="btn">
View
</a>

<?php
}
else
{
echo "Not Uploaded";
}
?>

</td>

<tr>
<th>PAN Card</th>

<td>

<?php 
if(!empty($student['pan_file']))
{
?>

<a href="../assets/uploads/pan/<?php echo $student['pan_file']; ?>" 
target="_blank" 
class="btn">
View
</a>

<?php
}
else
{
echo "Not Uploaded";
}
?>

</td>

</tr>

<tr>
<th>SSC Marksheet</th>

<td>

<?php 
if(!empty($student['ssc_file']))
{
?>

<a href="../assets/uploads/ssc/<?php echo $student['ssc_file']; ?>" 
target="_blank" 
class="btn">
View
</a>

<?php
}
else
{
echo "Not Uploaded";
}
?>

</td>

</tr>

<tr>
<th>HSC Marksheet</th>

<td>

<?php 
if(!empty($student['hsc_file']))
{
?>

<a href="../assets/uploads/hsc/<?php echo $student['hsc_file']; ?>" 
target="_blank" 
class="btn">
View
</a>

<?php
}
else
{
echo "Not Uploaded";
}
?>

</td>

</tr>

<tr>
<th>Caste Certificate</th>

<td>

<?php 
if(!empty($student['caste_file']))
{
?>

<a href="../assets/uploads/caste/<?php echo $student['caste_file']; ?>" 
target="_blank" 
class="btn">
View
</a>

<?php
}
else
{
echo "Not Uploaded";
}
?>

</td>

</tr>

<tr>
<th>Income Certificate</th>

<td>

<?php 
if(!empty($student['income_file']))
{
?>

<a href="../assets/uploads/income/<?php echo $student['income_file']; ?>" 
target="_blank" 
class="btn">
View
</a>

<?php
}
else
{
echo "Not Uploaded";
}
?>

</td>

</tr>

<tr>
<th>Domicile Certificate</th>

<td>

<?php 
if(!empty($student['domicile_file']))
{
?>

<a href="../assets/uploads/domicile/<?php echo $student['domicile_file']; ?>" 
target="_blank" 
class="btn">
View
</a>

<?php
}
else
{
echo "Not Uploaded";
}
?>

</td>

</tr>

<tr>
<th>Fee Receipt</th>

<td>

<?php 
if(!empty($student['receipt_file']))
{
?>

<a href="../assets/uploads/receipt/<?php echo $student['receipt_file']; ?>" 
target="_blank" 
class="btn">
Download
</a>

<?php
}
else
{
echo "Not Uploaded";
}
?>

</td>

</tr>

</table>

</div>


<!-- ================= Fees Section ================= -->

<div class="profile-card">

<h2><i class="fa-solid fa-wallet"></i> Fee Details</h2>

<table>

<tr>
<th>Total Fees</th>
<td>₹95,000</td>
</tr>

<tr>
<th>Paid Amount</th>
<td style="color:green;">₹95,000</td>
</tr>

<tr>
<th>Pending Amount</th>
<td style="color:red;">₹0</td>
</tr>

<tr>
<th>Receipt</th>
<td><a href="../assets/uploads/receipt.pdf" target="_blank" class="btn">Download</a></td>
</tr>

</table>

</div>


<!-- ================= Guardian Section ================= -->

<div class="profile-card">

<h2><i class="fa-solid fa-people-roof"></i> Guardian Details</h2>

<table>

<tr>
<th>Guardian Name</th>
<td>Mr. XYZ Jadhav</td>
</tr>

<tr>
<th>Relationship</th>
<td>Father</td>
</tr>

<tr>
<th>Occupation</th>
<td>Business</td>
</tr>

<tr>
<th>Mobile</th>
<td>9876543210</td>
</tr>

<tr>
<th>Email</th>
<td>guardian@gmail.com</td>
</tr>

</table>

</div>


<!-- ================= Medical Section ================= -->

<div class="profile-card">

<h2><i class="fa-solid fa-heart-pulse"></i> Medical Information</h2>

<table>

<tr>
<th>Blood Group</th>
<td>O+</td>
</tr>

<tr>
<th>Medical Condition</th>
<td>None</td>
</tr>

<tr>
<th>Emergency Contact</th>
<td>9876543210</td>
</tr>

</table>

</div>


<!-- ================= Digital ID ================= -->

<div class="profile-card">
    <div class="profile-card">

<h2><i class="fa-solid fa-chart-line"></i> Attendance Summary</h2>

<table>

<tr>
<th>Overall Attendance</th>
<td><span style="color:green;font-weight:bold;">91%</span></td>
</tr>

<tr>
<th>Total Lectures</th>
<td>420</td>
</tr>

<tr>
<th>Present</th>
<td>382</td>
</tr>

<tr>
<th>Absent</th>
<td>38</td>
</tr>

</table>

</div>



<div class="profile-card">

<h2><i class="fa-solid fa-graduation-cap"></i> Semester Results</h2>

<table>

<tr>

<th>Semester</th>

<th>SGPA</th>

<th>Status</th>

</tr>

<tr>

<td>Semester I</td>

<td>8.21</td>

<td style="color:green;">PASS</td>

</tr>

<tr>

<td>Semester II</td>

<td>8.42</td>

<td style="color:green;">PASS</td>

</tr>

<tr>

<td>Semester III</td>

<td>8.55</td>

<td style="color:green;">PASS</td>

</tr>

<tr>

<td>Semester IV</td>

<td>8.63</td>

<td style="color:green;">PASS</td>

</tr>

<tr>

<td>Semester V</td>

<td>8.75</td>

<td style="color:green;">PASS</td>

</tr>

</table>

</div>



<div class="profile-card">

<h2><i class="fa-solid fa-trophy"></i> Achievements</h2>

<ul class="achievement-list">

<li>🏆 Winner - Coding Competition 2025</li>

<li>🥇 Smart India Hackathon Participant</li>

<li>📜 Java Programming Certificate</li>

<li>📜 Python Programming Certificate</li>

<li>💻 Web Development Workshop</li>

</ul>

</div>



<div class="profile-card">

<h2><i class="fa-solid fa-briefcase"></i> Internship Details</h2>

<table>

<tr>

<th>Company</th>

<td>ABC Technologies Pvt. Ltd.</td>

</tr>

<tr>

<th>Duration</th>

<td>2 Months</td>

</tr>

<tr>

<th>Domain</th>

<td>PHP Full Stack Development</td>

</tr>

<tr>

<th>Status</th>

<td style="color:green;">Completed</td>

</tr>

</table>

</div>

<h2><i class="fa-solid fa-id-card"></i> Digital Student ID</h2>

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;">

<div>

<p><strong>Name :</strong> <?php echo $student['name']; ?></p>

<p><strong>PRN :</strong> <?php echo $student['prn']; ?></p>

<p><strong>Department :</strong> <?php echo $student['department']; ?></p>

<p><strong>Semester :</strong> <?php echo $student['semester']; ?></p>

</div>

<div>

<img src="../assets/images/qr.png" width="140">

</div>

</div>

<br>

<button class="download-btn">

<i class="fa-solid fa-download"></i>

Download ID Card

</button>

</div>

</div>

</div>

</body>

</html>
