<?php
session_start();
include("db.php");

// Security Check
if(!isset($_SESSION['staff'])){
    header("Location: staff_login.php");
    exit();
}

$staffName = $_SESSION['staff_name'];

// Handle File Upload
if(isset($_POST['upload'])){
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $filename = $_FILES['file']['name'];
    $temp = $_FILES['file']['tmp_name'];
    $newname = time()."_".$filename;

    if(move_uploaded_file($temp, "uploads/".$newname)){
        $conn->query("INSERT INTO question_bank (subject, year, title, description, file_name) 
                      VALUES ('$subject', '$year', '$title', '$description', '$newname')");
        
        // Log Activity
        $msg = "Uploaded Question Bank: $title ($subject - $year)";
        $conn->query("INSERT INTO portal_activity (user_name, user_role, action_type, message) VALUES ('$staffName', 'Staff', 'Upload', '$msg')");
        
        $success_msg = "Document uploaded to bank successfully!";
    }
}

// Handle File Deletion
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $res = $conn->query("SELECT title, file_name FROM question_bank WHERE id=$id");
    if($row = $res->fetch_assoc()){
        unlink("uploads/".$row['file_name']);
        $title = $row['title'];
        $conn->query("DELETE FROM question_bank WHERE id=$id");
        
        // Log Activity
        $msg = "Deleted Question Bank item: $title";
        $conn->query("INSERT INTO portal_activity (user_name, user_role, action_type, message) VALUES ('$staffName', 'Staff', 'Delete', '$msg')");
        
        header("Location: staff_questionbank.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question Bank Management | Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --sidebar-bg: #0f172a;
            --bg-main: #f8fafc;
            --white: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body { background: var(--bg-main); display: flex; min-height: 100vh; }

        /* Sidebar Styling */
        .sidebar {
            width: 280px; background: var(--sidebar-bg); padding: 30px 20px;
            position: fixed; height: 100vh; color: white; z-index: 100;
        }

        .sidebar h2 { font-size: 20px; margin-bottom: 40px; color: var(--primary); font-weight: 800; letter-spacing: 1px; }
        .sidebar ul { list-style: none; }
        .sidebar a {
            display: flex; align-items: center; gap: 12px; text-decoration: none;
            color: #94a3b8; padding: 14px 20px; border-radius: 12px;
            transition: 0.3s; margin-bottom: 8px; font-size: 14px;
        }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar a.active { background: var(--primary); box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3); }

        /* Main Content */
        .main-content { margin-left: 280px; flex: 1; padding: 40px; }

        .header-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
        .header-top h1 { font-size: 26px; color: var(--text-dark); font-weight: 800; }

        /* Upload Card */
        .card {
            background: var(--white); border-radius: 24px; padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.02); border: 1px solid #e2e8f0;
            margin-bottom: 40px;
        }

        .card h3 { margin-bottom: 20px; font-size: 18px; color: var(--text-dark); }

        .grid-form { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .full-width { grid-column: span 3; }

        label { font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; display: block; }
        
        input, textarea {
            width: 100%; padding: 14px; border-radius: 12px;
            border: 1px solid #e2e8f0; font-size: 14px; outline: none; transition: 0.3s;
        }
        input:focus, textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }

        .btn-submit {
            background: var(--primary); color: white; border: none;
            padding: 14px 28px; border-radius: 12px; font-weight: 700;
            cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 10px;
        }
        .btn-submit:hover { background: #4f46e5; transform: translateY(-2px); }

        /* Table Styling */
        .table-container {
            background: var(--white); border-radius: 20px; overflow: hidden;
            border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);
        }

        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; color: var(--text-muted); padding: 16px 20px; text-align: left; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }

        .badge-year { background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        
        .action-link { text-decoration: none; font-weight: 600; margin-right: 15px; }
        .link-view { color: var(--primary); }
        .link-delete { color: #ef4444; }

        .alert { padding: 15px; border-radius: 12px; margin-bottom: 20px; background: #dcfce7; color: #166534; font-size: 14px; font-weight: 600; }

        @media (max-width: 1024px) {
            .grid-form { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

    <!-- Sidebar Dashboard -->
    <div class="sidebar">
        <h2>STAFF HUB</h2>
        <ul>
            <li><a href="staff_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li><a href="staff_studymaterial.php"><i class="fa-solid fa-file-arrow-up"></i> Study Material</a></li>
            <li><a href="staff_questionbank.php" class="active"><i class="fa-solid fa-laptop-code"></i> Question Bank</a></li>
            <li><a href="student_queries.php"><i class="fa-solid fa-comments"></i> Student Queries</a></li>
            <li><a href="Staff_alert.php"><i class="fa-solid fa-bell"></i> Portal Activity</a></li>
            <li><a href="staff_profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
            <li><a href="staff_logout.php" style="margin-top: 50px; color: #f87171;"><i class="fa-solid fa-power-off"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header-top">
            <div>
                <h1>Question Bank Management</h1>
                <p style="color: var(--text-muted);">Organize previous year papers and important question sets</p>
            </div>
            <div style="text-align: right;">
                <p style="font-weight: 700; color: var(--text-dark);"><?php echo $staffName; ?></p>
                <span style="font-size: 12px; color: var(--primary); font-weight: 600;">Academic Staff</span>
            </div>
        </div>

        <?php if(isset($success_msg)) echo "<div class='alert'><i class='fa-solid fa-circle-check'></i> $success_msg</div>"; ?>

        <div class="card">
            <h3><i class="fa-solid fa-plus-circle"></i> Add New Document</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="grid-form">
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" placeholder="e.g. Database Systems" required>
                    </div>
                    <div class="form-group">
                        <label>Academic Year</label>
                        <input type="text" name="year" placeholder="e.g. 2023-24" required>
                    </div>
                    <div class="form-group">
                        <label>Document Title</label>
                        <input type="text" name="title" placeholder="e.g. Mid-Term Question Paper" required>
                    </div>
                    <div class="full-width">
                        <label>Description</label>
                        <textarea name="description" rows="2" placeholder="Briefly describe the contents of this file..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Upload File (PDF/Docs)</label>
                        <input type="file" name="file" required>
                    </div>
                    <div class="full-width">
                        <button name="upload" class="btn-submit">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Upload to Bank
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <h3><i class="fa-solid fa-list-check"></i> Recently Uploaded Papers</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Year</th>
                        <th>Title / Description</th>
                        <th>Upload Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = $conn->query("SELECT * FROM question_bank ORDER BY id DESC");
                    if($res->num_rows > 0):
                        while($row = $res->fetch_assoc()):
                    ?>
                    <tr>
                        <td><strong><?php echo $row['subject']; ?></strong></td>
                        <td><span class="badge-year"><?php echo $row['year']; ?></span></td>
                        <td>
                            <div style="font-weight: 600;"><?php echo $row['title']; ?></div>
                            <div style="font-size: 12px; color: #94a3b8;"><?php echo $row['description']; ?></div>
                        </td>
                        <td><?php echo date('d M Y', strtotime($row['upload_date'])); ?></td>
                        <td>
                            <a href="uploads/<?php echo $row['file_name']; ?>" target="_blank" class="action-link link-view">View</a>
                            <a href="?delete=<?php echo $row['id']; ?>" class="action-link link-delete" onclick="return confirm('Delete this document forever?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;">No records found in the bank.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>