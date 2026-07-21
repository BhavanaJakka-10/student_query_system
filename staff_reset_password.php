<?php
include("db.php");
$message = "";
$msg_type = "";
$token_valid = false;
date_default_timezone_set('Asia/Kolkata'); // Set this to your local timezone
// Check if token exists in the URL
if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    $current_time = date("Y-m-d H:i:s"); // Get current PHP time
    
    // Look for token where expiry is GREATER than current PHP time
    $sql = "SELECT * FROM staff_profile WHERE reset_token = '$token' AND token_expiry > '$current_time'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $token_valid = true;
        $user_data = $result->fetch_assoc();
    } else {
        $message = "Invalid or expired reset link. Please request a new one.";
        $msg_type = "error";
    }
}


if (isset($_POST['update_password']) && $token_valid) {
    $new_pass = $_POST['new_password'];
    $conf_pass = $_POST['confirm_password'];

    if ($new_pass === $conf_pass) {
        $staff_id = $user_data['staff_id'];
        
        // Update password and clear token
        $update_sql = "UPDATE staff_profile SET 
                       password = '$new_pass', 
                       reset_token = NULL, 
                       token_expiry = NULL 
                       WHERE staff_id = '$staff_id'";
        
        if ($conn->query($update_sql)) {
            $message = "Password updated! <a href='staff_login.php' style='color:#4f46e5; text-decoration:underline;'>Login now</a>";
            $msg_type = "success";
            $token_valid = false; 
        } else {
            $message = "Error updating password.";
            $msg_type = "error";
        }
    } else {
        $message = "Passwords do not match!";
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set New Password | Staff Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f1f5f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        .alert { padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 600; text-align: left; }
        .alert-success { background: #dcfce7; color: #15803d; }
        .alert-error { background: #fee2e2; color: #b91c1c; }
        .form-group { text-align: left; margin-bottom: 15px; }
        label { display: block; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
        input { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; box-sizing: border-box; }
        .btn { background: #4f46e5; color: white; border: none; padding: 14px; width: 100%; border-radius: 10px; font-weight: 700; cursor: pointer; width: 100%; }
    </style>
</head>
<body>
    <div class="card">
        <h2>New Password</h2>
        <?php if($message != ""): ?>
            <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if($token_valid): ?>
            <form method="POST">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required minlength="5">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required minlength="5">
                </div>
                <button type="submit" name="update_password" class="btn">Update Password</button>
            </form>
        <?php else: ?>
            <a href="staff_forget_password.php" style="color: #4f46e5; text-decoration: none; font-weight: 600;">Request a new link</a>
        <?php endif; ?>
    </div>
</body>
</html>