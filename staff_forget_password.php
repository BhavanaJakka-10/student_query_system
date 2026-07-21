<?php
include("db.php");
$message = "";
$msg_type = "";
date_default_timezone_set('Asia/Kolkata'); // Set this to your local timezone
if (isset($_POST['reset_request'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    $check = $conn->query("SELECT * FROM staff_profile WHERE email='$email'");
    
    if ($check->num_rows > 0) {
  // Generate a secure random token
$token = bin2hex(random_bytes(32));
// Set expiry to 30 minutes from now using PHP time
$expiry = date("Y-m-d H:i:s", strtotime("+30 minutes"));

$sql = "UPDATE staff_profile SET reset_token='$token', token_expiry='$expiry' WHERE email='$email'";
        
        if ($conn->query($sql)) {
            // FIXED: Use the token in the URL so the next page can read it
            $reset_link = "staff_reset_password.php?token=" . $token;
            
            // FIXED: We put the $reset_link variable inside the href
            $message = "Reset link generated: <a href='$reset_link' style='font-weight:bold; color:#4f46e5; text-decoration:underline;'>Click here to reset password</a>";
            $msg_type = "success";
        }
    } else {
        $message = "No account found with that email address.";
        $msg_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | Staff Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #4f46e5; --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg-gradient); display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: rgba(255, 255, 255, 0.95); padding: 40px; border-radius: 28px; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5); width: 100%; max-width: 400px; text-align: center; }
        h2 { font-weight: 800; color: #0f172a; margin-bottom: 10px; }
        input { width: 100%; padding: 14px; margin: 15px 0; border: 1px solid #e2e8f0; border-radius: 12px; box-sizing: border-box; }
        .btn { background: var(--primary); color: white; border: none; padding: 14px; width: 100%; border-radius: 12px; cursor: pointer; font-weight: 700; transition: 0.3s; }
        .btn:hover { transform: translateY(-2px); }
        .msg { padding: 12px; margin-bottom: 15px; border-radius: 10px; font-size: 14px; font-weight: 600; text-align: left; }
        .success { background: #dcfce7; color: #15803d; border: 1px solid #bcf0da; }
        .error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        a { color: var(--primary); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Forgot Password</h2>
        <p style="color: #64748b; font-size: 14px; margin-bottom: 20px;">Enter your email to receive a reset link</p>

        <?php if($message != ""): ?>
            <div class="msg <?php echo $msg_type; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="staff@example.com" required>
            <button type="submit" name="reset_request" class="btn">Send Reset Link</button>
        </form>
        <br>
        <a href="staff_login.php">Back to Login</a>
    </div>
</body>
</html>