
<?php
session_start();

if(!isset($_SESSION['student']))
{
    header("Location: student_login.php");
    exit();
}

require_once "../config/config.php";

$id = $_SESSION['student'];

$query = "SELECT name FROM users WHERE user_id='$id'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

$studentName = $row['name'];
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Poppins',sans-serif;
}

body{
    margin:0;
    overflow-x:hidden;
    min-height:100vh;
    background:linear-gradient(135deg,#eef4ff,#dbeafe,#ffffff);
}

.container{
display:flex;
width:100%;
}

/* Sidebar */

.sidebar{
    position:fixed;
    top:75px;
    left:0;
    width:290px;
    height:calc(100vh - 75px);
    overflow-y:auto;
    padding:20px 15px;

    background:linear-gradient(180deg,#0f172a,#1e3a8a,#2563eb);
border-right:3px solid rgba(255,255,255,.1);
    box-shadow:8px 0 25px rgba(0,0,0,.25);
}

.sidebar h2{
    font-size:24px;
    font-weight:700;
    text-align:center;
    margin-bottom:35px;
    color:#fff;
    padding-bottom:15px;
    border-bottom:1px solid rgba(255,255,255,.2);
}

.sidebar ul{

list-style:none;

}

.sidebar ul li{
    padding:16px 20px;
    margin:10px 0;
    border-radius:14px;
    font-size:18px;
    font-weight:600;
    transition:.35s;
    cursor:pointer;
}

.sidebar ul li i{
font-size:18px;
width:28px;
}

.sidebar ul li:hover{
background:rgba(255,255,255,.15);
    transform:translateX(8px);
    box-shadow:0 8px 20px rgba(0,0,0,.25);
}


.sidebar ul li.active{
background:linear-gradient(90deg,#3b82f6,#2563eb);
box-shadow:0 10px 25px rgba(37,99,235,.4);
}

.sidebar ul li.active a{
color:#fff;
}

.sidebar ul li.active i{
color:#fff;
}

.sidebar ul li i{

margin-right:12px;

width:20px;

}

.sidebar a{
    color:#fff;
    text-decoration:none;
    display:flex;
    align-items:center;
    gap:12px;
}

.sidebar i{
    width:24px;
    font-size:18px;
}

.sidebar::-webkit-scrollbar{
    width:6px;
}

.sidebar::-webkit-scrollbar-thumb{
    background:#60a5fa;
    border-radius:10px;
}

.sidebar::-webkit-scrollbar-track{
    background:transparent;
}

/* Main Content */

.main{
    margin-left:290px;
    margin-top:75px;
    padding:35px;
    flex:1;
    min-height:calc(100vh - 75px);
}



.profile{

background:white;

padding:10px 20px;

border-radius:30px;

box-shadow:0 5px 15px rgba(0,0,0,.1);

font-weight:600;

}

.cards{

display:grid;

grid-template-columns:repeat(3,1fr);

gap:20px;

margin-top:20px;

}

.card{
    background:#fff;
    padding:32px;
    border-radius:22px;
    border:none;
    box-shadow:0 18px 40px rgba(0,0,0,.08);
    transition:.4s;
    overflow:hidden;
    position:relative;
}

.card::before{
content:"";
position:absolute;
left:0;
top:0;
height:100%;
width:6px;
background:#2563eb;
}

.card:hover{
transform:translateY(-12px) scale(1.02);
box-shadow:0 25px 55px rgba(37,99,235,.25);
}

.card:hover{

transform:translateY(-10px);
box-shadow:0 20px 40px rgba(0,0,0,.18);

}

.card i{

font-size:35px;

color:#2563EB;

margin-bottom:15px;

}

.card h3{

margin-bottom:10px;

color:#0F172A;

}

.card p{

color:#666;

font-size:14px;

line-height:22px;

}

.header{
position:fixed;
top:0;
left:0;
right:0;
height:75px;
padding:0 35px;

display:flex;
justify-content:space-between;
align-items:center;

background:rgba(15,23,42,.92);

backdrop-filter:blur(15px);

border-bottom:1px solid rgba(255,255,255,.15);

z-index:1000;
}

.logo{
font-size:28px;
font-weight:700;
color:#60a5fa;
display:flex;
align-items:center;
gap:12px;
letter-spacing:1px;
}

.logo i{
font-size:30px;
color:#38bdf8;
}

.profile{
display:flex;
align-items:center;
gap:15px;
}

.profile img{
width:45px;
height:45px;
border-radius:50%;
border:2px solid white;
}



.welcome h2{

margin-bottom:10px;

}

.recent{
    background:#fff;
    padding:25px;
    border-radius:18px;
    margin-top:30px;
    margin-bottom:30px;
    box-shadow:0 10px 25px rgba(0,0,0,.08);
}

.recent h2{
font-size:34px;
}

.recent li{
font-size:18px;
padding:18px;
}

.recent h2{

margin-bottom:20px;

color:#0F172A;

}

.recent ul{

list-style:none;

}

.recent ul li{

padding:12px;

border-bottom:1px solid #eee;

}

.recent ul li:last-child{

border:none;

}

@media(max-width:992px){

.main{
margin-left:0;
padding:90px 20px;
}

.sidebar{
position:relative;
top:0;
width:100%;
height:auto;
}

.hero{
flex-direction:column;
text-align:center;
}



.hero img{
width:220px;
}

.stat{
padding:35px;
border-radius:22px;
}

.stat h2{
font-size:46px;
}

.stat p{
font-size:20px;
}

.cards{
display:grid;
grid-template-columns:repeat(3,1fr);
gap:28px;
margin-top:35px;
}

.card{
padding:35px;
border-radius:22px;
min-height:240px;
}

.card i{
font-size:42px;
}

.card h3{
font-size:28px;
margin:18px 0;
}

.card p{
font-size:18px;
line-height:30px;
}



}

.hero{
    width:100%;
    padding:45px;
    border-radius:25px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    background:linear-gradient(135deg,#1e3a8a,#2563eb,#4f46e5);
    box-shadow:0 20px 45px rgba(37,99,235,.35);
    position:relative;
    overflow:hidden;
}

.hero::before{
    content:"";
    position:absolute;
    width:220px;
    height:220px;
    background:rgba(255,255,255,.08);
    border-radius:50%;
    right:-60px;
    top:-70px;
}

.hero::after{
    content:"";
    position:absolute;
    width:300px;
    height:300px;
    background:rgba(255,255,255,.05);
    border-radius:50%;
    left:-120px;
    bottom:-150px;
}

.hero h1{
font-size:42px;
font-weight:700;
color:white;
}

.hero p{
font-size:18px;
color:#eef4ff;
margin-top:12px;
}



.hero-btns{
margin-top:20px;
}

.hero-btns a{
background:white;
color:#2563EB;
padding:12px 22px;
text-decoration:none;
border-radius:10px;
margin-right:10px;
font-weight:600;
}
.stats{
display:grid;
grid-template-columns:repeat(4,1fr);
gap:20px;
margin:30px 0;
}

.stat{
background:#fff;
padding:25px;
border-radius:18px;
text-align:center;
box-shadow:0 10px 25px rgba(0,0,0,.08);
transition:.3s;
}

.stat:hover{
transform:translateY(-8px);
}

.stat h2{
font-size:34px;
color:#2563EB;
margin-bottom:8px;
}

.stat p{
color:#666;
font-weight:500;
}

.footer{
    margin-left:290px;
    width:calc(100% - 290px);
    padding:30px;
    background:#0f172a;
    color:#fff;
    text-align:center;
    border-top:3px solid #2563eb;
}

@media(max-width:992px){
    .footer{
        margin-left:0;
        width:100%;
    }
}

</style>

</head>

<body>
    <div class="header">

<div class="logo">
<i class="fa-solid fa-graduation-cap"></i>
Zeal EduHub
</div>

<div class="profile">

<span>
    
Welcome,
<?php echo $_SESSION['student_name']; ?>
</span>

<img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png">

</div>

</div>

<div class="container">

<div class="sidebar">

<h2>🎓 Student Portal</h2>

<ul>

<li class="active"><a href="student_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>

<li><a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>

<li><a href="#" onclick="comingSoon(); return false;"><i class="fa-solid fa-book"></i> Study Material</a></li>

<li><a href="#" onclick="comingSoon()"><i class="fa-solid fa-file-lines"></i> Syllabus</a></li>

<li><a href="#" onclick="comingSoon()"><i class="fa-solid fa-laptop-code"></i> Lab Practice</a></li>

<li><a href="#" onclick="comingSoon(); return false;"><i class="fa-solid fa-circle-question"></i> Question Bank</a></li>

<li><a href="#" onclick="comingSoon(); return false;"><i class="fa-solid fa-file-pdf"></i> Previous Papers</a></li>

<li><a href="#" onclick="comingSoon(); return false;"><i class="fa-solid fa-envelope"></i> Raise Query</a></li>

<li><a href="#" onclick="comingSoon(); return false;"><i class="fa-solid fa-comments"></i> My Queries</a></li>

<li><a href="#" onclick="comingSoon(); return false;"><i class="fa-solid fa-bell"></i> Notifications</a></li>

<li><a href="student_logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>

</ul>

</div>

<div class="main">

<div class="hero">

<div>

<h1>Welcome Back 👋</h1>


<p style="margin-top:15px;font-size:16px;color:#dbeafe;">
Explore study materials, previous papers and all academic resources in one place.
</p>


</div>

<img src="https://cdn-icons-png.flaticon.com/512/3135/3135755.png" width="180">
</div>


<div class="cards">

<div class="card" onclick="comingSoon()">
    <i class="fa-solid fa-book-open"></i>
    <h3>Study Material</h3>
    <p>View and download notes, PDFs and reference materials uploaded by staff.</p>
</div>

<div class="card" onclick="comingSoon()">
    <i class="fa-solid fa-file-lines"></i>
    <h3>Syllabus</h3>
    <p>Check the latest syllabus for all subjects of your semester.</p>
</div>

<div class="card" onclick="comingSoon()">
    <i class="fa-solid fa-laptop-code"></i>
    <h3>Lab Practice</h3>
    <p>Practice C, C++, Java, Python, PHP and SQL programs online.</p>
</div>

<div class="card" onclick="comingSoon()">
    <i class="fa-solid fa-circle-question"></i>
    <h3>Question Bank</h3>
    <p>Prepare for exams using important questions and model question banks.</p>
</div>

<div class="card" onclick="comingSoon()">
    <i class="fa-solid fa-file-pdf"></i>
    <h3>Previous Papers</h3>
    <p>Download previous year university examination papers.</p>
</div>

<div class="card" onclick="comingSoon()">
    <i class="fa-solid fa-bell"></i>
    <h3>Notifications</h3>
    <p>View latest announcements, updates and important notices.</p>
</div>

</div>

<div class="recent">

<h2>Recent Activities</h2>

<ul>

<li>📚 Java Programming Notes uploaded</li>

<li>💻 PHP Lab Practice added</li>

<li>📄 DBMS Previous Year Paper uploaded</li>

<li>📝 New Question Bank available</li>

<li>🔔 Assignment submission deadline updated</li>

</ul>

</div>


</div> <!-- recent -->

</div> <!-- main -->

</div> <!-- container -->

<footer class="footer">
    <p><strong>Study Material Sharing and Practice Portal</strong></p>
    <p>Department of Information Technology</p>
    <p>© 2026 All Rights Reserved</p>
</footer>

<script>
function comingSoon() {
    Swal.fire({
        icon: 'info',
        title: '🚧 Under Maintenance',
        html: `
            <h3 style="color:#2563EB;margin-bottom:10px;">
                This module is currently under maintenance.
            </h3>
            <p style="font-size:15px;color:#555;line-height:24px;">
                Our development team is working to make this feature available in the next update.
                <br><br>
                Thank you for your patience and continued support.
            </p>
        `,
        confirmButtonText: 'OK',
        confirmButtonColor: '#2563EB',
        width: '500px'
    });
}
</script>

</body>

</html>
