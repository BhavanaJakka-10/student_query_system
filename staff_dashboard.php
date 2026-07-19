<?php
session_start();

if(!isset($_SESSION['staff'])){
    header("Location: staff_login.php");
    exit();
}

$staffName = $_SESSION['staff'];
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Staff Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
}

body{
    background:linear-gradient(135deg,#eef4ff,#d8e8ff,#edf2ff,#f5f3ff);
    background-size:400% 400%;
    animation:bgMove 12s ease infinite;
    min-height:100vh;
    overflow:hidden;
}

@keyframes bgMove{
    0%{background-position:0% 50%;}
    50%{background-position:100% 50%;}
    100%{background-position:0% 50%;}
}

/*================ HEADER =================*/

.header{
    height:80px;
    width:100%;
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:0 40px;
    background:linear-gradient(90deg,#1d4ed8,#4338ca,#6d28d9);
    color:#fff;
    box-shadow:0 10px 30px rgba(0,0,0,.2);
    position:sticky;
    top:0;
    z-index:1000;
}

.logo{
    font-size:28px;
    font-weight:700;
    letter-spacing:1px;
}

.profile{
    display:flex;
    align-items:center;
    gap:15px;
    background:rgba(255,255,255,.15);
    padding:8px 20px;
    border-radius:60px;
    backdrop-filter:blur(15px);
    transition:.4s;
}

.profile:hover{
    background:rgba(255,255,255,.25);
    transform:scale(1.05);
}

.profile img{
    width:55px;
    height:55px;
    border-radius:50%;
    border:3px solid white;
    object-fit:cover;
}

.profile span{
    font-size:17px;
    font-weight:600;
}

/*================ MAIN =================*/

.main{
    display:flex;
    height:calc(100vh - 80px);
}

/*================ SIDEBAR =================*/

.sidebar{
    width:270px;
    background:linear-gradient(180deg,#111827,#1e3a8a,#312e81);
    padding:25px 15px;
    overflow:auto;
    box-shadow:10px 0 30px rgba(0,0,0,.2);
}

.sidebar ul{
    list-style:none;
}

.sidebar li{
    margin-bottom:12px;
}

.sidebar a{
    display:flex;
    align-items:center;
    gap:15px;
    text-decoration:none;
    color:#dbeafe;
    padding:15px 18px;
    border-radius:14px;
    font-size:16px;
    transition:.35s;
}

.sidebar a i{
    width:28px;
    font-size:20px;
}

.sidebar a:hover{
    background:linear-gradient(90deg,#3b82f6,#6366f1);
    color:#fff;
    transform:translateX(8px);
    box-shadow:0 10px 25px rgba(59,130,246,.4);
}

.sidebar a:active{
    transform:scale(.97);
}

/*================ CONTENT =================*/

.content{
    flex:1;
    overflow-y:auto;
    padding:35px;
}

/*================ WELCOME =================*/

.welcome{
    background:rgba(255,255,255,.65);
    backdrop-filter:blur(18px);
    border-radius:20px;
    padding:35px;
    box-shadow:0 15px 40px rgba(0,0,0,.08);
    border:1px solid rgba(255,255,255,.4);
    margin-bottom:35px;
    animation:fadeUp .7s ease;
}

.welcome h2{
    color:#1d4ed8;
    margin-bottom:10px;
    font-size:32px;
}

.welcome p{
    color:#555;
    font-size:16px;
    line-height:28px;
}

/*================ CARDS =================*/

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:25px;
}

.card{
    background:rgba(255,255,255,.75);
    backdrop-filter:blur(20px);
    border-radius:22px;
    padding:35px 25px;
    text-align:center;
    border:1px solid rgba(255,255,255,.5);
    transition:.4s;
    cursor:pointer;
    position:relative;
    overflow:hidden;
    box-shadow:0 15px 35px rgba(0,0,0,.08);
}

.card::before{
    content:'';
    position:absolute;
    top:-100%;
    left:-100%;
    width:250%;
    height:250%;
    background:linear-gradient(135deg,
        rgba(255,255,255,.25),
        transparent,
        rgba(255,255,255,.1));
    transform:rotate(25deg);
    transition:.6s;
}

.card:hover::before{
    top:-40%;
    left:-20%;
}

.card:hover{
    transform:translateY(-12px);
    box-shadow:0 20px 45px rgba(37,99,235,.25);
}

.card i{
    font-size:60px;
    margin-bottom:18px;
    background:linear-gradient(135deg,#2563eb,#7c3aed);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

.card h3{
    color:#1e293b;
    font-size:24px;
    margin-bottom:12px;
}

.card p{
    color:#64748b;
    line-height:26px;
}

/*================ LOGOUT =================*/

.logout{
    margin-top:40px;
}

.logout a{
    display:inline-flex;
    align-items:center;
    gap:10px;
    text-decoration:none;
    background:linear-gradient(90deg,#ef4444,#dc2626);
    color:#fff;
    padding:15px 35px;
    border-radius:14px;
    font-weight:600;
    transition:.35s;
    box-shadow:0 10px 25px rgba(239,68,68,.3);
}

.logout a:hover{
    transform:translateY(-5px);
    box-shadow:0 18px 35px rgba(239,68,68,.45);
}

/*================ SCROLLBAR =================*/

::-webkit-scrollbar{
    width:10px;
}

::-webkit-scrollbar-track{
    background:#e2e8f0;
}

::-webkit-scrollbar-thumb{
    background:linear-gradient(#3b82f6,#6366f1);
    border-radius:30px;
}

/*================ ANIMATION =================*/

@keyframes fadeUp{
    from{
        opacity:0;
        transform:translateY(40px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

.card,
.welcome{
    animation:fadeUp .7s ease;
}

/*================ RESPONSIVE =================*/

@media(max-width:992px){

.main{
    flex-direction:column;
}

.sidebar{
    width:100%;
    height:auto;
}

.content{
    padding:20px;
}

.cards{
    grid-template-columns:repeat(2,1fr);
}

}

@media(max-width:700px){

.header{
    padding:15px;
}

.logo{
    font-size:20px;
}

.profile span{
    display:none;
}

.cards{
    grid-template-columns:1fr;
}

.sidebar a{
    font-size:15px;
}

.content{
    padding:15px;
}

.welcome h2{
    font-size:25px;
}

}

</style>

</head>

<body>

<!-- Header -->

<div class="header">

<div class="logo">
Study Material Sharing & Practice Portal
</div>

<div class="profile">

<img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png">

<span><?php echo $staffName; ?></span>

</div>

</div>

<div class="main">

<!-- Sidebar -->

<div class="sidebar">

<ul>

<li><a href="staff.dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>

<li><a href="staff_studymaterial.php"><i class="fa-solid fa-file-arrow-up"></i> Update Study Material</a></li>


<li><a href="staff_questionbank.php"><i class="fa-solid fa-laptop-code"></i> Manage Practice Questions</a></li>

<li><a href="staff_lab.php"><i class="fa-solid fa-flask"></i> Lab Programs</a></li>

<li><a href="student_queries..php"><i class="fa-solid fa-comments"></i> Student Queries</a></li>

<li><a href="Staff_alert.php"><i class="fa-solid fa-bell"></i> Notifications</a></li>

<li><a href="staff_profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>

</ul>

</div>

<!-- Content -->

<div class="content">

<div class="welcome">

<h2>Welcome, <?php echo $staffName; ?> 👋</h2>

<p>
Manage study materials, question banks, practice questions, labs, and student queries from one place.
</p>

</div>

<div class="cards">

<div class="card">

<i class="fa-solid fa-book"></i>

<h3>Study Materials</h3>

<p>Upload and manage study materials.</p>

</div>

<div class="card">

<i class="fa-solid fa-file-circle-question"></i>

<h3>Question Bank</h3>

<p>Add and update question banks.</p>

</div>

<div class="card">

<i class="fa-solid fa-code"></i>

<h3>Practice Questions</h3>

<p>Create coding practice questions.</p>

</div>

<div class="card">

<i class="fa-solid fa-flask"></i>

<h3>Lab Programs</h3>

<p>Upload and manage lab experiments.</p>

</div>

<div class="card">

<i class="fa-solid fa-comments"></i>

<h3>Student Queries</h3>

<p>View and reply to student questions.</p>

</div>

<div class="card">

<i class="fa-solid fa-chart-line"></i>

<h3>Reports</h3>

<p>View portal usage and activities.</p>

</div>

</div>

<div class="logout">

<a href="staff_logout.php">
<i class="fa-solid fa-right-from-bracket"></i>
Logout
</a>

</div>

</div>

</div>

</body>
</html>