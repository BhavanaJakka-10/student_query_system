
<?php
session_start();

if (!isset($_SESSION['student'])) {
    header("Location: ../student_login.php");
    exit();
}

require_once "../config/config.php";

$id = $_SESSION['student'];

$query = "SELECT
users.name,
users.email,
student_profile.*
FROM users
INNER JOIN student_profile
ON users.user_id = student_profile.student_id
WHERE users.user_id='$id'";

$result = mysqli_query($conn,$query);

$student = mysqli_fetch_assoc($result);

function uploadFile($inputName,$folder)
{
    if(isset($_FILES[$inputName]) && $_FILES[$inputName]['error']==0)
    {
        $filename=time()."_".basename($_FILES[$inputName]['name']);

        $target="../assets/uploads/".$folder."/".$filename;

        move_uploaded_file($_FILES[$inputName]['tmp_name'],$target);

        return $filename;
    }

    return "";
}

if(isset($_POST['update']))
{

$name=$_POST['name'];
$email=$_POST['email'];

$dob = $_POST['dob'] ?? $student['dob'];
$gender = $_POST['gender'] ?? $student['gender'];
$blood = $_POST['blood'] ?? $student['blood_group'];

$mobile = $_POST['mobile'] ?? $student['mobile'];

$aadhaar = $_POST['aadhaar'] ?? $student['aadhaar_no'];
$abc = $_POST['abc'] ?? $student['abc_id'];

$address = $_POST['address'] ?? $student['address'];

$city = $_POST['city'] ?? $student['city'];
$state = $_POST['state'] ?? $student['state'];
$pincode = $_POST['pincode'] ?? $student['pincode'];

$father = $_POST['father'] ?? $student['father_name'];
$father_mobile = $_POST['father_mobile'] ?? $student['father_mobile'];

$mother = $_POST['mother'] ?? $student['mother_name'];
$mother_mobile = $_POST['mother_mobile'] ?? $student['mother_mobile'];

$guardian = $_POST['guardian'] ?? $student['guardian_name'];
$guardian_relation = $_POST['guardian_relation'] ?? $student['guardian_relation'];
$guardian_mobile = $_POST['guardian_mobile'] ?? $student['guardian_mobile'];
$guardian_email = $_POST['guardian_email'] ?? $student['guardian_email'];
$guardian_occupation = $_POST['guardian_occupation'] ?? $student['guardian_occupation'];

$medical = $_POST['medical'] ?? $student['medical_condition'];
$emergency = $_POST['emergency'] ?? $student['emergency_contact'];

$photo=uploadFile("photo","photos");
$aadhaar_file=uploadFile("aadhaar_file","aadhaar");
$pan_file=uploadFile("pan_file","pan");
$ssc_file=uploadFile("ssc_file","ssc");
$hsc_file=uploadFile("hsc_file","hsc");
$lc_file=uploadFile("lc_file","lc");
$caste_file=uploadFile("caste_file","caste");
$income_file=uploadFile("income_file","income");
$domicile_file=uploadFile("domicile_file","domicile");
$receipt_file=uploadFile("receipt_file","receipt");

mysqli_query($conn,"
UPDATE users SET

name='$name',
email='$email'

WHERE user_id='$id'
");

$sql="UPDATE student_profile SET

mobile='$mobile',

dob='$dob',
gender='$gender',
blood_group='$blood',

aadhaar_no='$aadhaar',
abc_id='$abc',

address='$address',
city='$city',
state='$state',
pincode='$pincode',

father_name='$father',
father_mobile='$father_mobile',

mother_name='$mother',
mother_mobile='$mother_mobile',

guardian_name='$guardian',
guardian_relation='$guardian_relation',
guardian_mobile='$guardian_mobile',
guardian_email='$guardian_email',
guardian_occupation='$guardian_occupation',

medical_condition='$medical',
emergency_contact='$emergency'";

if($photo!="")
$sql.=",photo='$photo'";

if($aadhaar_file!="")
$sql.=",aadhaar_file='$aadhaar_file'";

if($pan_file!="")
$sql.=",pan_file='$pan_file'";

if($ssc_file!="")
$sql.=",ssc_file='$ssc_file'";

if($hsc_file!="")
$sql.=",hsc_file='$hsc_file'";

if($lc_file!="")
$sql.=",lc_file='$lc_file'";

if($caste_file!="")
$sql.=",caste_file='$caste_file'";

if($income_file!="")
$sql.=",income_file='$income_file'";

if($domicile_file!="")
$sql.=",domicile_file='$domicile_file'";

if($receipt_file!="")
$sql.=",receipt_file='$receipt_file'";

$sql.=" WHERE student_id='$id'";

mysqli_query($conn,$sql);

echo "<script>
alert('Profile Updated Successfully');
window.location='profile.php';
</script>";

}
?>
<!DOCTYPE html>
<html>

<head>

<title>Edit Profile</title>

<link rel="stylesheet" href="../assets/css/profile.css">
<style>
body{
    margin:0;
    padding:30px;
    background:linear-gradient(135deg,#eef3fb,#d6e6ff);
    font-family:'Poppins',sans-serif;
}

.content{
    max-width:900px;
    margin:auto;
    background:#ffffff;
    padding:30px;
    border-radius:15px;
    box-shadow:0 10px 25px rgba(0,0,0,.15);
}

h1{
    color:#0d47a1;
    margin-bottom:20px;
}

label{
    display:block;
    margin-top:15px;
    margin-bottom:5px;
    font-weight:600;
}

input,
textarea{
    width:100%;
    padding:12px;
    border:1px solid #ccc;
    border-radius:8px;
    box-sizing:border-box;
}

button{
    margin-top:20px;
    padding:12px 25px;
    background:#1565c0;
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
    font-size:16px;
}

button:hover{
    background:#0d47a1;
}
</style>

</head>


<body>


<div class="content">


<h1>Edit Student Profile</h1>


<form method="POST" enctype="multipart/form-data">


<label>Name</label>
<input type="text" name="name"
value="<?php echo $student['name']; ?>">


<label>Roll Number</label>

<input type="text" 
value="IT1216"
readonly>


<label>Email</label>
<input type="email" name="email"
value="<?php echo $student['email']; ?>">



<label>Mobile</label>

<input type="text" name="mobile"
value="<?php echo $student['mobile']; ?>">


<label>Address</label>

<textarea name="address">

<?php echo $student['address']; ?>

</textarea>
<label>ABC ID</label>

<input type="text" name="abc"
value="<?php echo $student['abc_id']; ?>">

<label>Date of Birth</label>
<input type="date" name="dob"
value="<?php echo $student['dob']; ?>">


<label>Gender</label>
<input type="text" name="gender"
value="<?php echo $student['gender']; ?>">


<label>Blood Group</label>
<input type="text" name="blood"
value="<?php echo $student['blood_group']; ?>">

<label>Aadhaar Number</label>

<input type="text" name="aadhaar"
value="<?php echo $student['aadhaar_no']; ?>">



<label>Father Name</label>

<input type="text" name="father"
value="<?php echo $student['father_name']; ?>">



<label>Mother Name</label>

<input type="text" name="mother"
value="<?php echo $student['mother_name']; ?>">



<h2>Upload Documents</h2>


<label>Aadhaar PDF</label>

<input type="file" name="aadhaar_file">



<label>PAN PDF</label>

<input type="file" name="pan_file">



<label>SSC Marksheet</label>

<input type="file" name="ssc_file">

<label>HSC Marksheet</label>
<input type="file" name="hsc_file">


<label>Leaving Certificate</label>
<input type="file" name="lc_file">


<label>Caste Certificate</label>
<input type="file" name="caste_file">


<label>Income Certificate</label>
<input type="file" name="income_file">


<label>Domicile Certificate</label>
<input type="file" name="domicile_file">


<label>Fee Receipt</label>
<input type="file" name="receipt_file">



<br><br>


<button type="submit" name="update">

Update Profile

</button>



</form>



</div>


</body>

</html>