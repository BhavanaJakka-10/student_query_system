<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STUDY PORTAL | Welcome</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #4f46e5;
            --student-color: #10b981;
            --staff-color: #3b82f6;
            --text-dark: #1e293b;
            --text-light: #64748b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            /* Modern Mesh Gradient Background */
            background-color: #f8fafc;
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(16, 185, 129, 0.1) 0px, transparent 50%);
            padding: 20px;
        }

        .container {
            width: 1100px;
            max-width: 100%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            overflow: hidden;
            display: flex;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Left Side: Informative Hero */
        .left {
            width: 45%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
            position: relative;
        }

        .left h1 {
            font-size: 42px;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 20px;
        }

        .left p {
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .features {
            list-style: none;
        }

        .features li {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 15px;
            background: rgba(255, 255, 255, 0.1);
            padding: 12px 20px;
            border-radius: 12px;
            transition: 0.3s;
        }

        .features li:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(10px);
        }

        .features i {
            font-size: 20px;
            color: #fbbf24;
        }

        /* Right Side: Selection Cards */
        .right {
            width: 55%;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #fff;
        }

        .header-box {
            margin-bottom: 40px;
            text-align: center;
        }

        .header-box h2 {
            font-size: 28px;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .header-box p {
            color: var(--text-light);
        }

        .login-cards {
            display: grid;
            gap: 20px;
        }

        .login-option {
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 25px;
            border-radius: 20px;
            border: 2px solid #f1f5f9;
            transition: all 0.3s ease;
            position: relative;
        }

        .login-option:hover {
            border-color: var(--primary);
            background: #f5f3ff;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.1);
        }

        .icon-box {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 20px;
        }

        .student-icon { background: #dcfce7; color: var(--student-color); }
        .staff-icon { background: #dbeafe; color: var(--staff-color); }

        .option-text h3 {
            color: var(--text-dark);
            font-size: 18px;
            margin-bottom: 4px;
        }

        .option-text p {
            color: var(--text-light);
            font-size: 13px;
        }

        .arrow {
            margin-left: auto;
            color: #cbd5e1;
            transition: 0.3s;
        }

        .login-option:hover .arrow {
            color: var(--primary);
            transform: translateX(5px);
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 13px;
            color: #94a3b8;
        }

        /* Mobile Optimization */
        @media (max-width: 992px) {
            .container { flex-direction: column; width: 100%; height: auto; }
            .left, .right { width: 100%; padding: 40px 30px; }
            .left h1 { font-size: 32px; }
        }
    </style>
</head>
<body>

    <div class="container">
        <!-- Informative Left Panel -->
        <div class="left">
            <h1>Study material sheraing and practice poratl.</h1>
            <p>A centralized hub for academic excellence. Access resources, track progress, and communicate seamlessly.</p>
            
            <ul class="features">
                <li>
                    <i class="fa-solid fa-circle-check"></i>
                    <span><strong>Study Materials:</strong> Access high-quality notes and PDFs anytime.</span>
                </li>
                <li>
                    <i class="fa-solid fa-circle-check"></i>
                    <span><strong>Question Banks:</strong> Practice with previous year papers.</span>
                </li>
                <li>
                    <i class="fa-solid fa-circle-check"></i>
                    <span><strong>Real-time Support:</strong> Staff help and query resolutions.</span>
                </li>
            </ul>
        </div>

        <!-- Actionable Right Panel -->
        <div class="right">
            <div class="header-box">
                <h2>Welcome Back</h2>
                <p>Please select your account type to login</p>
            </div>

            <div class="login-cards">
                <!-- Student Card -->
                <a href="student_login.php" class="login-option">
                    <div class="icon-box student-icon">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                    <div class="option-text">
                        <h3>Student Portal</h3>
                        <p>View materials, download QB, and ask queries.</p>
                    </div>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </a>

                <!-- Staff Card -->
                <a href="staff_login.php" class="login-option">
                    <div class="icon-box staff-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div class="option-text">
                        <h3>Staff Portal</h3>
                        <p>Manage uploads, respond to students, and logs.</p>
                    </div>
                    <i class="fa-solid fa-chevron-right arrow"></i>
                </a>
            </div>

            <div class="footer">
                &copy; 2026 Academic Study Portal • Secure Access Only
            </div>
        </div>
    </div>

</body>
</html>