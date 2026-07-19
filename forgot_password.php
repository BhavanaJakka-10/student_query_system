<?php
require_once "../config/config.php";

$message = "";

if(isset($_POST['reset']))
{

    $email = $_POST['email'];
    $new_password = $_POST['password'];


    $check = mysqli_query($conn,
    "SELECT * FROM users WHERE email='$email' AND role='student'"
    );


    if(mysqli_num_rows($check) > 0)
    {

        $update = mysqli_query($conn,
        "UPDATE users SET password='$new_password' WHERE email='$email'"
        );


        if($update)
        {
            $message = "Password reset successfully. Now login.";
        }

    }
    else
    {
        $message = "Student email not found.";
    }

}

?>


<!DOCTYPE html>
<html>
<head>

<title>Forgot Password</title>

<style>

body{
height:100vh;
display:flex;
justify-content:center;
align-items:center;
background:linear-gradient(135deg,#0f172a,#2563eb);
font-family:Poppins,sans-serif;
}

.box{

width:380px;
background:white;
padding:40px;
border-radius:20px;
box-shadow:0 20px 40px rgba(0,0,0,.3);

}

h2{
text-align:center;
color:#2563eb;
margin-bottom:25px;
}

input{

width:100%;
padding:14px;
margin:10px 0;
border:1px solid #ccc;
border-radius:8px;

}


button{

width:100%;
padding:14px;
background:#2563eb;
color:white;
border:none;
border-radius:8px;
font-size:16px;
cursor:pointer;

}


.message{

text-align:center;
color:green;
margin-bottom:15px;

}


a{
text-decoration:none;
color:#2563eb;
}

</style>

</head>


<body>


<div class="box">


<h2>
Forgot Password
</h2>


<?php

if($message!="")
{
echo "<p class='message'>$message</p>";
}

?>


<form method="POST">


<input 
type="email"
name="email"
placeholder="Enter Student Email"
required>


<input 
type="password"
name="password"
placeholder="Enter New Password"
required>


<button name="reset">
Reset Password
</button>


</form>


<br>

<center>
<a href="student_login.php">
Back to Login
</a>
</center>


</div>


</body>
</html>