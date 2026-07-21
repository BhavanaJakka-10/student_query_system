<?php
session_start();
if (!isset($_SESSION['staff'])) {
    header("Location: staff_login.php");
    exit();
}

include("db.php");
$staffID = $_SESSION['staff'];
$message = "";
$msg_type = "";

if(isset($_POST['update'])){
    $name = mysqli_real_escape_string($conn,$_POST['name']);
    $email = mysqli_real_escape_string($conn,$_POST['email']);
    $phone = mysqli_real_escape_string($conn,$_POST['phone']);
    $department = mysqli_real_escape_string($conn,$_POST['department']);
    $designation = mysqli_real_escape_string($conn,$_POST['designation']);
    $gender = mysqli_real_escape_string($conn,$_POST['gender']);
    $dob = $_POST['dob'];
    $address = mysqli_real_escape_string($conn,$_POST['address']);

    $photoQuery="";
    if(isset($_FILES['photo']) && $_FILES['photo']['name']!=""){
        $folder="uploads/staff/";
        if(!is_dir($folder)){ mkdir($folder,0777,true); }
        $photo=time()."_".basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], $folder.$photo);
        $photoQuery=", photo='$photo'";
    }

    $update="UPDATE staff_profile SET 
        name='$name', email='$email', phone='$phone', 
        department='$department', designation='$designation', 
        gender='$gender', dob='$dob', address='$address' $photoQuery 
        WHERE staff_id='$staffID'";

    if($conn->query($update)){
        $message="Profile updated successfully!";
        $msg_type="success";
    }else{
        $message="Failed to update profile.";
        $msg_type="error";
    }
}

$result=$conn->query("SELECT * FROM staff_profile WHERE staff_id='$staffID'");
if($result->num_rows==0){ die("Staff profile not found."); }
$row=$result->fetch_assoc();

$image="uploads/staff/default.png";
if(!empty($row['photo']) && file_exists("uploads/staff/".$row['photo'])){
    $image="uploads/staff/".$row['photo'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Staff Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
code
Code
<style>
    :root {
        --primary: #6366f1;
        --primary-dark: #4f46e5;
        --success: #10b981;
        --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        --glass-bg: rgba(255, 255, 255, 0.9);
        --text-dark: #0f172a;
        --text-muted: #64748b;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

    body {
        background: var(--bg-gradient);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px 20px;
    }

    .container {
        width: 100%;
        max-width: 850px;
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border-radius: 28px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        padding: 50px;
        border: 1px solid rgba(255, 255, 255, 0.6);
        position: relative;
    }

    h2 {
        font-size: 26px;
        color: var(--text-dark);
        margin-bottom: 40px;
        font-weight: 800;
        text-align: center;
    }

    /* Message Box */
    .alert {
        padding: 15px;
        border-radius: 14px;
        margin-bottom: 25px;
        text-align: center;
        font-weight: 600;
        animation: slideDown 0.4s ease;
    }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .alert-success { background: #dcfce7; color: #15803d; }
    .alert-error { background: #fee2e2; color: #b91c1c; }

    /* Profile Image */
    .profile-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 40px;
    }
    .image-container {
        position: relative;
        width: 140px;
        height: 140px;
        border-radius: 50%;
        padding: 5px;
        background: linear-gradient(to right, var(--primary), #a855f7);
        margin-bottom: 15px;
    }
    .image-container img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #fff;
    }
    .file-upload-btn {
        display: none; /* Hidden by default, shown in edit mode */
        margin-top: 10px;
    }

    /* Form Grid */
    .profile-form {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }

    .input-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .input-group label {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    input, select, textarea {
        padding: 14px 18px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        font-size: 15px;
        background: #fff;
        color: var(--text-dark);
        transition: 0.3s;
    }

    /* Disabled State (View Mode) */
    input:disabled, select:disabled, textarea:disabled {
        background: #f8fafc;
        border-color: transparent;
        color: #475569;
        cursor: default;
        font-weight: 500;
    }

    input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .full-width { grid-column: span 2; }

    /* Buttons Section */
    .form-actions {
        grid-column: span 2;
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 30px;
    }

    .btn {
        padding: 15px 35px;
        border-radius: 14px;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        transition: 0.3s;
        display: flex;
        align-items: center;
        gap: 10px;
        border: none;
    }

    .btn-edit { background: var(--primary); color: white; box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2); }
    .btn-save { background: var(--success); color: white; display: none; box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2); }
    .btn-cancel { background: #f1f5f9; color: #64748b; display: none; }
    .btn-back { background: transparent; color: var(--text-muted); text-decoration: none; font-size: 14px; margin-top: 20px; display: inline-block; }

    .btn:hover { transform: translateY(-3px); filter: brightness(1.1); }

    @media (max-width: 600px) {
        .profile-form { grid-template-columns: 1fr; }
        .full-width { grid-column: span 1; }
    }
</style>
</head>
<body>
<div class="container">
    <a href="staff_dashboard.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
code
Code
<h2>My Profile</h2>

<?php if($message!=""): ?>
    <div class="alert alert-<?php echo $msg_type; ?>">
        <i class="fa-solid <?php echo ($msg_type == 'success') ? 'fa-circle-check' : 'fa-circle-xmark'; ?>"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" id="profileForm">
    
    <div class="profile-header">
        <div class="image-container">
            <img src="<?php echo $image; ?>" id="profilePreview">
        </div>
        <input type="file" name="photo" class="file-upload-btn" id="photoInput" onchange="previewImage(event)">
    </div>

    <div class="profile-form">
        <div class="input-group">
            <label>Staff ID</label>
            <input type="text" value="<?php echo $row['staff_id']; ?>" disabled>
        </div>

        <div class="input-group">
            <label>Full Name</label>
            <input type="text" name="name" value="<?php echo $row['name']; ?>" required disabled class="editable">
        </div>

        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email" value="<?php echo $row['email']; ?>" required disabled class="editable">
        </div>

        <div class="input-group">
            <label>Phone</label>
            <input type="text" name="phone" value="<?php echo $row['phone']; ?>" disabled class="editable">
        </div>

        <div class="input-group">
            <label>Department</label>
            <input type="text" name="department" value="<?php echo $row['department']; ?>" disabled class="editable">
        </div>

        <div class="input-group">
            <label>Designation</label>
            <input type="text" name="designation" value="<?php echo $row['designation']; ?>" disabled class="editable">
        </div>

        <div class="input-group">
            <label>Gender</label>
            <select name="gender" disabled class="editable">
                <option <?php if($row['gender']=="Male") echo "selected"; ?>>Male</option>
                <option <?php if($row['gender']=="Female") echo "selected"; ?>>Female</option>
                <option <?php if($row['gender']=="Other") echo "selected"; ?>>Other</option>
            </select>
        </div>

        <div class="input-group">
            <label>Date of Birth</label>
            <input type="date" name="dob" value="<?php echo $row['dob']; ?>" disabled class="editable">
        </div>

        <div class="input-group full-width">
            <label>Address</label>
            <textarea name="address" rows="3" disabled class="editable"><?php echo $row['address']; ?></textarea>
        </div>

        <div class="form-actions">
            <!-- Toggle Button -->
            <button type="button" class="btn btn-edit" id="editBtn" onclick="enableEditing()">
                <i class="fa-solid fa-user-pen"></i> Edit Profile
            </button>

            <!-- Save Changes (Initially Hidden) -->
            <button type="submit" name="update" class="btn btn-save" id="saveBtn">
                <i class="fa-solid fa-check"></i> Save Changes
            </button>

            <!-- Cancel (Initially Hidden) -->
            <button type="button" class="btn btn-cancel" id="cancelBtn" onclick="location.reload()">
                Cancel
            </button>
        </div>
    </div>
</form>
</div>
<script>
    function enableEditing() {
        // Enable all inputs with 'editable' class
        const inputs = document.querySelectorAll('.editable');
        inputs.forEach(input => {
            input.disabled = false;
        });

        // Show File Upload
        document.getElementById('photoInput').style.display = 'block';

        // Toggle Buttons
        document.getElementById('editBtn').style.display = 'none';
        document.getElementById('saveBtn').style.display = 'flex';
        document.getElementById('cancelBtn').style.display = 'flex';

        // Add a focus to the first name field
        inputs[0].focus();
    }

    // Preview image before uploading
    function previewImage(event) {
        var reader = new FileReader();
        reader.onload = function(){
            var output = document.getElementById('profilePreview');
            output.src = reader.result;
        }
        reader.readAsDataURL(event.target.files[0]);
    }
</script>
</body>
</html>