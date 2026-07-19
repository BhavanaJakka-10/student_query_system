<?php
session_start();

require_once "../config/config.php";

$error = "";

if(isset($_POST['login']))
{

    $email = trim($_POST['studentid']);
    $password = $_POST['password'];


    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE email=? AND role='student'"
    );

    $stmt->bind_param("s",$email);

    $stmt->execute();

    $result = $stmt->get_result();


    if($result->num_rows > 0)
    {

        $user = $result->fetch_assoc();


        // simple password check
        if($password == $user['password'])
{
    $_SESSION['student'] = $user['user_id'];
    $_SESSION['student_name'] = $user['name'];

    header("Location: student_dashboard.php");
    exit();
}
        else
        {
            $error = "Wrong Password";
        }

    }
    else
    {
        $error = "Student account not found";
    }

}
?>


<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Login | Study Material Sharing and Practice Portal</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Poppins',sans-serif;
}

body{

height:100vh;

display:flex;

justify-content:center;

align-items:center;

background:linear-gradient(135deg,#0F172A,#2563EB);

}

.container{

width:1100px;

height:650px;

background:white;

border-radius:20px;

overflow:hidden;

display:flex;

box-shadow:0 20px 40px rgba(0,0,0,.35);

}

.left{

width:50%;

background:linear-gradient(135deg,#1E3A8A,#2563EB);

color:white;

padding:50px;

display:flex;

flex-direction:column;

justify-content:center;

}

.logo{

width:90px;

height:90px;

border-radius:50%;

background:white;

display:flex;

justify-content:center;

align-items:center;

margin-bottom:20px;

}

.logo i{

font-size:40px;

color:#2563EB;

}

.left h1{

font-size:32px;

margin-bottom:8px;

}

.left h2{

font-size:20px;

font-weight:500;

margin-bottom:25px;

}

.left p{

line-height:28px;

margin-bottom:30px;

}

.features{

display:grid;

grid-template-columns:1fr 1fr;

gap:15px;

}

.features div{

background:rgba(255,255,255,.15);

padding:15px;

border-radius:10px;

font-size:15px;

}

.features i{

margin-right:8px;

}

.right{

width:50%;

background:#F8FAFC;

display:flex;

justify-content:center;

align-items:center;

}

.login-box{

width:380px;

background:white;

padding:40px;

border-radius:20px;

box-shadow:0 10px 30px rgba(0,0,0,.15);

}

.login-box h2{

text-align:center;

color:#1E3A8A;

margin-bottom:30px;

}
.input-box{
position:relative;
margin-bottom:20px;
}

.input-box i{
position:absolute;
top:16px;
left:16px;
color:#666;
font-size:16px;
}

.input-box input{
width:100%;
padding:14px 14px 14px 45px;
border:1px solid #ccc;
border-radius:8px;
font-size:15px;
outline:none;
transition:.3s;
}

.input-box input:focus{
border-color:#2563EB;
box-shadow:0 0 8px rgba(37,99,235,.3);
}

.row{
display:flex;
justify-content:space-between;
align-items:center;
font-size:14px;
margin-bottom:20px;
}

.row a{
text-decoration:none;
color:#2563EB;
font-weight:600;
}

button{
width:100%;
padding:14px;
background:#2563EB;
border:none;
border-radius:8px;
color:white;
font-size:17px;
font-weight:600;
cursor:pointer;
transition:.3s;
}

button:hover{
background:#1E3A8A;
}

.error{
background:#FEE2E2;
color:#B91C1C;
padding:10px;
border-radius:6px;
margin-bottom:15px;
text-align:center;
}

.footer{
margin-top:25px;
text-align:center;
font-size:13px;
color:#666;
}

@media(max-width:900px){

.container{
flex-direction:column;
width:95%;
height:auto;
}

.left,.right{
width:100%;
}

.left{
padding:35px;
}

.login-box{
margin:40px 0;
}

}

</style>

</head>

<body>

<div class="container">

<div class="left">

<div class="logo">
<i class="fa-solid fa-graduation-cap"></i>
</div>

<h1>Zeal College Of Engineering And Research</h1>

<h2>Study Material Sharing and Practice Portal</h2>

<p>

Access Study Materials, Syllabus, Lab Practice,
Question Bank, Previous Year Papers and Notifications
through one secure student portal.

</p>

<div class="features">

<div><i class="fa-solid fa-book"></i> Study Material</div>

<div><i class="fa-solid fa-file-lines"></i> Syllabus</div>

<div><i class="fa-solid fa-laptop-code"></i> Lab Practice</div>

<div><i class="fa-solid fa-circle-question"></i> Question Bank</div>

<div><i class="fa-solid fa-file-pdf"></i> Previous Papers</div>

<div><i class="fa-solid fa-bell"></i> Notifications</div>

</div>

</div>

<div class="right">

<div class="login-box">

<h2>
<i class="fa-solid fa-user-graduate"></i>
Student Login
</h2>

<?php
if(!empty($error))
{
echo "<div class='error'>$error</div>";
}
?>

<form method="POST">

<div class="input-box">

<i class="fa-solid fa-id-card"></i>

<input
type="email"
name="studentid"
placeholder="Enter Email"
required>

</div>

<div class="input-box">

<i class="fa-solid fa-lock"></i>

<input
type="password"
name="password"
placeholder="Enter Password"
required>

</div>

<div class="row">

<label>

<input type="checkbox">

Remember Me

</label>

<a href="forgot_password.php">Forgot Password?</a>

</div>

<button type="submit" name="login">

Login

</button>
</form>

<div class="footer">

<hr style="margin:20px 0;border:0;border-top:1px solid #ddd;">

<p><strong>Study Material Sharing and Practice Portal</strong></p>

<p>Student Learning Management System</p>

<p style="margin-top:10px;">
© 2026 All Rights Reserved
</p>

</div>

</div>

</div>

</div>

</body>

</html>