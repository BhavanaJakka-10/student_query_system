<?php
session_start();
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>student Logout</title>

<meta http-equiv="refresh" content="3;url=student_login.php">

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
    background:linear-gradient(135deg,#0f172a,#1d4ed8,#4f46e5);
    overflow:hidden;
}

/* Background Circles */

body::before{
    content:"";
    position:absolute;
    width:550px;
    height:550px;
    background:rgba(255,255,255,.08);
    border-radius:50%;
    top:-180px;
    left:-180px;
}

body::after{
    content:"";
    position:absolute;
    width:450px;
    height:450px;
    background:rgba(255,255,255,.06);
    border-radius:50%;
    bottom:-150px;
    right:-150px;
}

/* Logout Card */

.logout-box{
    width:450px;
    padding:45px;
    text-align:center;
    border-radius:22px;
    background:rgba(255,255,255,.15);
    backdrop-filter:blur(18px);
    border:1px solid rgba(255,255,255,.25);
    color:white;
    position:relative;
    z-index:1;
    box-shadow:0 20px 45px rgba(0,0,0,.30);
}

.icon{
    width:95px;
    height:95px;
    margin:auto;
    border-radius:50%;
    background:white;
    color:#2563eb;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:42px;
    font-weight:bold;
    animation:zoom .7s ease;
}

h1{
    margin-top:25px;
    font-size:30px;
}

p{
    margin-top:15px;
    line-height:28px;
    color:#e5e7eb;
    font-size:16px;
}

.loader{
    width:80%;
    height:8px;
    background:rgba(255,255,255,.20);
    border-radius:30px;
    overflow:hidden;
    margin:30px auto;
}

.progress{
    width:0%;
    height:100%;
    background:white;
    animation:load 3s linear forwards;
}

.btn{
    display:inline-block;
    margin-top:10px;
    text-decoration:none;
    background:white;
    color:#1d4ed8;
    padding:14px 32px;
    border-radius:12px;
    font-weight:600;
    transition:.3s;
}

.btn:hover{
    transform:translateY(-4px);
    box-shadow:0 10px 20px rgba(255,255,255,.35);
}

.footer{
    margin-top:25px;
    font-size:13px;
    color:#d1d5db;
}

@keyframes load{
    from{width:0%;}
    to{width:100%;}
}

@keyframes zoom{
    from{
        transform:scale(.3);
        opacity:0;
    }
    to{
        transform:scale(1);
        opacity:1;
    }
}

@media(max-width:550px){

.logout-box{
    width:90%;
    padding:35px 25px;
}

h1{
    font-size:24px;
}

}

</style>

</head>

<body>

<div class="logout-box">

<div class="icon">
✓
</div>

<h1>student Logged Out</h1>

<p>
You have successfully signed out of the
<b>Study Material Sharing & Practice Portal</b>.
<br><br>
Redirecting to the Staff Login page...
</p>

<div class="loader">
<div class="progress"></div>
</div>

<a href="student_login.php" class="btn">
Login Again
</a>

<div class="footer">
© 2026 Study Material Sharing & Practice Portal
</div>

</div>

</body>

</html>