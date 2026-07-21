
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

        if(!is_dir("../assets/uploads/".$folder))
{
    mkdir("../assets/uploads/".$folder,0777,true);
}

        $target="../assets/uploads/".$folder."/".$filename;

        $allowed=['pdf','jpg','jpeg','png'];

$ext=strtolower(pathinfo($_FILES[$inputName]['name'],PATHINFO_EXTENSION));

if(!in_array($ext,$allowed))
{
die("Invalid File Type");
}

if($_FILES[$inputName]['size']>2097152)
{
die("Maximum 2MB File Allowed");
}

        move_uploaded_file($_FILES[$inputName]['tmp_name'],$target);

        return $filename;
    }

    return "";
}

if(isset($_POST['update']))
{

    $name = trim($_POST['name']);

$email = strtolower(trim($_POST['email']));


if(!preg_match("/^[A-Za-z ]+$/",$name))
{
    die("Invalid Name");
}


if(!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|in|org)$/",$email))
{
    die("Invalid Email Format");
}

if(!preg_match("/^[6-9][0-9]{9}$/",$_POST['mobile']))
{
    die("Invalid Mobile Number");
}



if(!preg_match("/^[6-9][0-9]{9}$/",$_POST['father_mobile']))
{
    die("Invalid Father Mobile Number");
}

if(!preg_match("/^[6-9][0-9]{9}$/",$_POST['mother_mobile']))
{
    die("Invalid Mother Mobile Number");
}

if(!preg_match("/^[6-9][0-9]{9}$/", $_POST['emergency_mobile']))
{
    die("Invalid Emergency Contact");
}

if(!preg_match("/^[0-9]{12,20}$/",$_POST['abc']))
{
    die("Invalid ABC ID");
}

if(!preg_match("/^[0-9]{6}$/",$_POST['pincode']))
{
    die("Invalid Pincode");
}

if(!preg_match("/^[A-Za-z ]+$/",$_POST['father']))
{
    die("Invalid Father Name");
}

if(!preg_match("/^[A-Za-z ]+$/",$_POST['mother']))
{
    die("Invalid Mother Name");
}

if(!preg_match("/^[A-Za-z .-]+$/",$_POST['father_occupation']))
{
    die("Invalid Father Occupation");
}

if(!preg_match("/^[A-Za-z .-]+$/",$_POST['mother_occupation']))
{
    die("Invalid Mother Occupation");
}


$dob = $_POST['dob'] ?? $student['dob'];
if(strtotime($dob)>time())
{
die("Future Date Not Allowed");
}
$gender = $_POST['gender'] ?? $student['gender'];
$blood = $_POST['blood'] ?? $student['blood_group'];

$mobile = $_POST['mobile'] ?? $student['mobile'];

$_POST['abc'] = str_replace(' ','',$_POST['abc']);
$abc = $_POST['abc'] ?? $student['abc_id'];

$address = $_POST['address'] ?? $student['address'];

$city = $_POST['city'] ?? $student['city'];
$state = $_POST['state'] ?? $student['state'];
$pincode = $_POST['pincode'] ?? $student['pincode'];

$father = $_POST['father'] ?? $student['father_name'];
$father_mobile = $_POST['father_mobile'] ?? $student['father_mobile'];

$mother = $_POST['mother'] ?? $student['mother_name'];
$mother_mobile = $_POST['mother_mobile'] ?? $student['mother_mobile'];

$father_occupation = $_POST['father_occupation'] ?? $student['father_occupation'];
$mother_occupation = $_POST['mother_occupation'] ?? $student['mother_occupation'];


$medical = $_POST['medical'] ?? $student['medical_condition'];
$emergency = $_POST['emergency_mobile'] ?? $student['emergency_contact'];

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

abc_id='$abc',

address='$address',
city='$city',
state='$state',
pincode='$pincode',

father_name='$father',
father_mobile='$father_mobile',
father_occupation='$father_occupation',

mother_name='$mother',
mother_mobile='$mother_mobile',
mother_occupation='$mother_occupation',

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
<input
type="text"
name="name"
value="<?php echo htmlspecialchars($student['name']); ?>"
required
maxlength="50"
pattern="[A-Za-z ]+"
oninput="this.value=this.value.replace(/[^A-Za-z ]/g,'')"
title="Only alphabets and spaces allowed">


<label>Email</label>


<input
type="email"
name="email"
value="<?php echo htmlspecialchars($student['email']); ?>"
required
maxlength="100"
pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}"
oninput="this.value=this.value.replace(/\s/g,'')"
title="Enter Valid Email Address"
>


<label>Mobile</label>

<input
type="tel"
name="mobile"
value="<?php echo htmlspecialchars($student['mobile']); ?>"
required
maxlength="10"
pattern="[6-9]{1}[0-9]{9}"
inputmode="numeric"
oninput="this.value=this.value.replace(/[^0-9]/g,'')">


<label>Address</label>

<textarea
name="address"
required
maxlength="250"><?php echo htmlspecialchars($student['address']); ?></textarea>

<label>ABC ID</label>

<input
type="text"
name="abc"
value="<?php echo htmlspecialchars($student['abc_id']); ?>"
maxlength="20"
required
pattern="[0-9]{12,20}"
inputmode="numeric"
oninput="this.value=this.value.replace(/[^0-9]/g,'')"
title="ABC ID must contain 12 to 20 digits">

<label>City</label>

<select name="city" id="city" required>

<option value="">Select City</option>

</select>

<label>State</label>

<select name="state" id="state" required>

<option value="">Select State</option>

<option value="Maharashtra" <?php if($student['state']=="Maharashtra") echo "selected"; ?>>
Maharashtra
</option>

<option value="Gujarat" <?php if($student['state']=="Gujarat") echo "selected"; ?>>
Gujarat
</option>

<option value="Rajasthan" <?php if($student['state']=="Rajasthan") echo "selected"; ?>>
Rajasthan
</option>

<option value="Karnataka" <?php if($student['state']=="Karnataka") echo "selected"; ?>>
Karnataka
</option>

<option value="Goa" <?php if($student['state']=="Goa") echo "selected"; ?>>
Goa
</option>

<option value="Delhi" <?php if($student['state']=="Delhi") echo "selected"; ?>>
Delhi
</option>

</select>

<label>Pincode</label>

<input
type="text"
name="pincode"
value="<?php echo htmlspecialchars($student['pincode']); ?>"
required
maxlength="6"
pattern="[0-9]{6}"
inputmode="numeric"
oninput="this.value=this.value.replace(/[^0-9]/g,'')">

<label>Date of Birth</label>


<input
type="date"
name="dob"
value="<?php echo htmlspecialchars($student['dob']); ?>">

<label>Gender</label>

<select name="gender" required>

<option value="">Select Gender</option>

<option value="Male"
<?php if($student['gender']=="Male") echo "selected"; ?>>
Male
</option>

<option value="Female"
<?php if($student['gender']=="Female") echo "selected"; ?>>
Female
</option>

<option value="Other"
<?php if($student['gender']=="Other") echo "selected"; ?>>
Other
</option>

</select>

<select name="blood" required>

<option value="">Select Blood Group</option>

<option value="A+" <?php if($student['blood_group']=="A+") echo "selected"; ?>>A+</option>

<option value="A-" <?php if($student['blood_group']=="A-") echo "selected"; ?>>A-</option>

<option value="B+" <?php if($student['blood_group']=="B+") echo "selected"; ?>>B+</option>

<option value="B-" <?php if($student['blood_group']=="B-") echo "selected"; ?>>B-</option>

<option value="AB+" <?php if($student['blood_group']=="AB+") echo "selected"; ?>>AB+</option>

<option value="AB-" <?php if($student['blood_group']=="AB-") echo "selected"; ?>>AB-</option>

<option value="O+" <?php if($student['blood_group']=="O+") echo "selected"; ?>>O+</option>

<option value="O-" <?php if($student['blood_group']=="O-") echo "selected"; ?>>O-</option>

</select>


<label>Father Name</label>

<input
type="text"
name="father"
value="<?php echo htmlspecialchars($student['father_name']); ?>"
required
maxlength="50"
pattern="[A-Za-z ]+"
oninput="this.value=this.value.replace(/[^A-Za-z ]/g,'')">


<label>Father Mobile</label>

<input
type="tel"
name="father_mobile"
value="<?php echo htmlspecialchars($student['father_mobile']); ?>"
required
maxlength="10"
pattern="[6-9]{1}[0-9]{9}"
inputmode="numeric"
oninput="this.value=this.value.replace(/[^0-9]/g,'')">


<label>Father Occupation</label>

<input
type="text"
name="father_occupation"
value="<?php echo htmlspecialchars($student['father_occupation']); ?>"
required
maxlength="40"
pattern="[A-Za-z .-]+"
oninput="this.value=this.value.replace(/[^A-Za-z .-]/g,'')">


<label>Mother Name</label>

<input
type="text"
name="mother"
value="<?php echo htmlspecialchars($student['mother_name']); ?>"
required
maxlength="50"
pattern="[A-Za-z ]+"
oninput="this.value=this.value.replace(/[^A-Za-z ]/g,'')">


<label>Mother Mobile</label>

<input
type="tel"
name="mother_mobile"
value="<?php echo htmlspecialchars($student['mother_mobile']); ?>"
required
maxlength="10"
pattern="[6-9]{1}[0-9]{9}"
inputmode="numeric"
oninput="this.value=this.value.replace(/[^0-9]/g,'')">

<label>Mother Occupation</label>

<input
type="text"
name="mother_occupation"
value="<?php echo htmlspecialchars($student['mother_occupation']); ?>"
required
maxlength="40"
pattern="[A-Za-z .-]+"
oninput="this.value=this.value.replace(/[^A-Za-z .-]/g,'')">

<h2>Medical Information</h2>

<label>Medical Condition</label>

<select name="medical" required>

<option value="None" <?php if($student['medical_condition']=="None") echo "selected"; ?>>None</option>

<option value="Asthma" <?php if($student['medical_condition']=="Asthma") echo "selected"; ?>>Asthma</option>

<option value="Diabetes" <?php if($student['medical_condition']=="Diabetes") echo "selected"; ?>>Diabetes</option>

<option value="Heart Disease" <?php if($student['medical_condition']=="Heart Disease") echo "selected"; ?>>Heart Disease</option>

<option value="Blood Pressure" <?php if($student['medical_condition']=="Blood Pressure") echo "selected"; ?>>Blood Pressure</option>

<option value="Other" <?php if($student['medical_condition']=="Other") echo "selected"; ?>>Other</option>

</select>

<label>Emergency Contact</label>

<input
type="tel"
name="emergency_mobile"
value="<?php echo htmlspecialchars($student['emergency_contact']); ?>"
required
maxlength="10"
pattern="[6-9]{1}[0-9]{9}"
inputmode="numeric"
oninput="this.value=this.value.replace(/[^0-9]/g,'')">

<h2>Upload Documents</h2>


<label>Aadhaar PDF</label>

<input
type="file"
name="aadhaar_file"
accept=".pdf,.jpg,.jpeg,.png">


<label>PAN PDF</label>

<input type="file" name="pan_file" accept=".pdf,.jpg,.jpeg,.png">



<label>SSC Marksheet</label>

<input type="file" name="ssc_file" accept=".pdf,.jpg,.jpeg,.png">


<label>HSC Marksheet</label>

<input type="file" name="hsc_file" accept=".pdf,.jpg,.jpeg,.png">

<label>Leaving Certificate</label>

<input type="file" name="lc_file" accept=".pdf,.jpg,.jpeg,.png">


<label>Caste Certificate</label>

<input type="file" name="caste_file" accept=".pdf,.jpg,.jpeg,.png">


<label>Income Certificate</label>

<input type="file" name="income_file" accept=".pdf,.jpg,.jpeg,.png">


<label>Domicile Certificate</label>

<input type="file" name="domicile_file" accept=".pdf,.jpg,.jpeg,.png">


<label>Fee Receipt</label>

<input type="file" name="receipt_file" accept=".pdf,.jpg,.jpeg,.png">


<br><br>


<button type="submit" name="update">

Update Profile

</button>



</form>



</div>

<script>

let cities = {

"Maharashtra":[
"Pune",
"Mumbai",
"Nashik",
"Nagpur"
],

"Gujarat":[
"Ahmedabad",
"Surat",
"Vadodara",
"Rajkot",
"Gandhinagar"
],

"Rajasthan":[
"Jaipur",
"Jodhpur",
"Kota",
"Udaipur"
],

"Karnataka":[
"Bangalore",
"Mysore",
"Mangalore"
],

"Goa":[
"Panaji",
"Margao"
],

"Delhi":[
"New Delhi"
]

};


let state=document.getElementById("state");
let city=document.getElementById("city");


function loadCities(selectedCity="")
{

city.innerHTML='<option value="">Select City</option>';

let list=cities[state.value];


if(list)
{
list.forEach(function(item){

let option=document.createElement("option");

option.value=item;
option.text=item;


if(item=="<?php echo $student['city']; ?>")
{
option.selected=true;
}


city.appendChild(option);


});

}

}


state.addEventListener("change",function(){

loadCities();

});


// page load pe city show karega

loadCities();

</script>


</body>

</html>