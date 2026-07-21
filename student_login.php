<?php
session_start();

// Initialize dynamic password if not set (Default: 12345)
if (!isset($_SESSION['student_password'])) {
    $_SESSION['student_password'] = "12345";
}

$studentId = "123"; 
$error = "";

// Initialize login attempts
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if (isset($_POST['login'])) {
    $id = trim($_POST['studentid']);
    $pass = trim($_POST['password']);

    if ($id === $studentId && $pass === $_SESSION['student_password']) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['student'] = $studentId;
        header("Location: student_dashboard.php");
        exit();
    } else {
        $_SESSION['login_attempts']++;
        $remaining = 5 - $_SESSION['login_attempts'];

        if ($_SESSION['login_attempts'] >= 5) {
            $error = "Account Locked! Please reset your password.";
        } else {
            $error = "Invalid Credentials! Attempts left: <b>$remaining</b>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login | Exam Portal</title>
    <!-- Modern Font -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --accent: #10b981;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --bg-body: #f8fafc;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--bg-body);
            /* Modern Mesh Gradient Background */
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.1) 0px, transparent 50%);
            padding: 20px;
        }

        .container {
            width: 1050px;
            height: 650px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            display: flex;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: containerEntry 0.8s ease-out;
        }

        @keyframes containerEntry {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Left Side Panel */
        .left {
            width: 45%;
            background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            color: white;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Decorative circles for left panel */
        .left::before {
            content: ""; position: absolute; width: 300px; height: 300px;
            background: rgba(255,255,255,0.1); border-radius: 50%;
            top: -100px; right: -100px;
        }

        .left h1 { font-size: 42px; font-weight: 800; line-height: 1.1; margin-bottom: 20px; }
        .left p { font-size: 16px; opacity: 0.9; line-height: 1.6; margin-bottom: 40px; }
        
        .left img {
            width: 100%;
            max-width: 280px;
            align-self: center;
            filter: drop-shadow(0 20px 30px rgba(0,0,0,0.2));
            animation: float 4s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }

        /* Right Side Login */
        .right {
            width: 55%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #ffffff;
            padding: 40px;
        }

        .login-card { width: 100%; max-width: 400px; }

        .header-meta { text-align: center; margin-bottom: 40px; }
        
        .icon-circle {
            width: 70px; height: 70px; background: #eef2ff;
            color: var(--primary); border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; margin: 0 auto 20px;
        }

        .header-meta h2 { font-size: 28px; color: var(--text-main); font-weight: 700; }
        .header-meta p { color: var(--text-muted); font-size: 14px; margin-top: 5px; }

        /* Form Inputs */
        .input-group { position: relative; margin-bottom: 20px; }
        .input-group i {
            position: absolute; left: 18px; top: 50%;
            transform: translateY(-50%); color: #94a3b8;
            transition: 0.3s;
        }

        .input-group input {
            width: 100%;
            padding: 16px 16px 16px 52px;
            border: 2px solid #f1f5f9;
            border-radius: 16px;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            outline: none;
            color: var(--text-main);
        }

        .input-group input:focus {
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .input-group input:focus + i { color: var(--primary); }

        .error-msg {
            background: #fff1f2; color: #e11d48;
            padding: 14px; border-radius: 12px;
            margin-bottom: 20px; font-size: 14px;
            display: flex; align-items: center; gap: 10px;
            border: 1px solid #ffe4e6;
        }

        .flex-row {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 30px; font-size: 14px;
        }

        .flex-row a { text-decoration: none; color: var(--primary); font-weight: 700; }
        .flex-row label { color: var(--text-muted); cursor: pointer; display: flex; align-items: center; gap: 8px; }

        button {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 16px;
            background: var(--primary);
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }

        button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.4);
        }

        button:disabled { background: #cbd5e1; cursor: not-allowed; transform: none; box-shadow: none; }

        .footer-text { text-align: center; margin-top: 30px; font-size: 13px; color: #94a3b8; }

        /* Mobile Adjustments */
        @media (max-width: 900px) {
            .container { flex-direction: column; height: auto; width: 100%; }
            .left, .right { width: 100%; padding: 40px 30px; }
            .left h1 { font-size: 32px; }
            .left img { display: none; }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Branding Section -->
    <div class="left">
        <h1>Welcome to Study Portal.</h1>
        <p>Your one-stop destination for study materials, question banks, and academic support.</p>
        <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="Student Illustration">
    </div>

    <!-- Form Section -->
    <div class="right">
        <div class="login-card">
            <div class="header-meta">
                <div class="icon-circle">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
                <h2>Student Login</h2>
                <p>Please enter your credentials to continue</p>
            </div>

            <?php if(!empty($error)): ?>
                <div class="error-msg">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo $error; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <i class="fa-solid fa-id-card"></i>
                    <input type="text" name="studentid" placeholder="Student ID (e.g. 123)" required>
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required 
                    <?php echo ($_SESSION['login_attempts'] >= 5) ? 'disabled' : ''; ?>>
                </div>

                <div class="flex-row">
                    <label>
                        <input type="checkbox" style="accent-color: var(--primary);"> 
                        Remember Me
                    </label>
                    <a href="student_forget_password.php">Forgot Password?</a>
                </div>

                <?php if($_SESSION['login_attempts'] < 5): ?>
                    <button name="login" type="submit">Sign In to Dashboard</button>
                <?php else: ?>
                    <a href="student_forget_password.php" style="text-decoration:none;">
                        <button type="button" style="background:#e11d48;">Reset My Account</button>
                    </a>
                <?php endif; ?>
            </form>

            <div class="footer-text">
                &copy; 2026 Academic Study Portal • Secure Access
            </div>
        </div>
    </div>
</div>

</body>
</html>