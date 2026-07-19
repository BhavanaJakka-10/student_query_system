<?php
session_start();
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Logging Out</title>

<meta http-equiv="refresh" content="3;url=../student_login.php">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
    background:linear-gradient(135deg,#2563eb,#4f46e5,#7c3aed);
    overflow:hidden;
}

/* Animated Background */

body::before{
    content:"";
    position:absolute;
    width:600px;
    height:600px;
    background:rgba(255,255,255,.08);
    border-radius:50%;
    top:-200px;
    left:-200px;
}

body::after{
    content:"";
    position:absolute;
    width:500px;
    height:500px;
    background:rgba(255,255,255,.06);
    border-radius:50%;
    bottom:-150px;
    right:-150px;
}

.logout-box{
    position:relative;
    z-index:1;
    width:420px;
    background:rgba(255,255,255,.15);
    backdrop-filter:blur(18px);
    border-radius:20px;
    padding:45px 35px;
    text-align:center;
    color:white;
    box-shadow:0 20px 40px rgba(0,0,0,.25);
    border:1px solid rgba(255,255,255,.25);
}

.icon{
    width:90px;
    height:90px;
    margin:auto;
    border-radius:50%;
    background:white;
    color:#2563eb;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:42px;
    font-weight:bold;
    animation:pop .8s ease;
}

h2{
    margin-top:25px;
    font-size:30px;
}

p{
    margin-top:12px;
    line-height:28px;
    color:#f3f4f6;
}

.loader{
    width:70%;
    height:8px;
    background:rgba(255,255,255,.25);
    margin:30px auto;
    border-radius:30px;
    overflow:hidden;
}

.progress{
    height:100%;
    background:white;
    animation:loading 3s linear forwards;
}

.login-btn{
    display:inline-block;
    margin-top:10px;
    text-decoration:none;
    background:white;
    color:#2563eb;
    padding:12px 28px;
    border-radius:10px;
    font-weight:600;
    transition:.3s;
}

.login-btn:hover{
    transform:translateY(-3px);
    box-shadow:0 10px 20px rgba(255,255,255,.3);
}

@keyframes loading{

    from{
        width:0%;
    }

    to{
        width:100%;
    }

}

@keyframes pop{

    0%{
        transform:scale(.4);
        opacity:0;
    }

    100%{
        transform:scale(1);
        opacity:1;
    }

}

</style>

</head>

<body>

<div class="logout-box">

<div class="icon">
✓
</div>

<h2>Logged Out Successfully</h2>

<p>
Thank you for using the Study Material Sharing & Practice Portal.
<br>
Redirecting to the Student Login page...
</p>

<div class="loader">
<div class="progress"></div>
</div>

<a href="student_login.php" class="login-btn">
Login Again
</a>

</div>

<script>

function comingSoon(){

Swal.fire({
title:"🚧 Coming Soon",
text:"This feature is under development.",
icon:"info",
confirmButtonText:"OK",
confirmButtonColor:"#2563EB"
});

}

</script>

</body>

</html>