<?php
session_start();
include("db.php");

// Security Check
if(!isset($_SESSION['staff'])){
    header("Location: staff_login.php");
    exit();
}

$staffName = $_SESSION['staff_name'];

/* --- UPLOAD LOGIC --- */
if(isset($_POST['upload'])){
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $filename = $_FILES['file']['name'];
    $temp = $_FILES['file']['tmp_name'];
    $newname = time()."_".$filename;

    if(move_uploaded_file($temp, "uploads/".$newname)){
        // Insert into study materials
        $conn->query("INSERT INTO study_materials(subject, title, description, file_name) VALUES('$subject','$title','$description','$newname')");
        
        // Log Activity
        $msg = "Uploaded new material: $title ($subject)";
        $conn->query("INSERT INTO portal_activity (user_name, user_role, action_type, message) VALUES ('$staffName', 'Staff', 'Upload', '$msg')");
        
        header("Location: staff_studymaterial.php?success=1");
    }
}

/* --- DELETE LOGIC --- */
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $res = $conn->query("SELECT title, file_name FROM study_materials WHERE id=$id");
    $row = $res->fetch_assoc();

    if($row){
        unlink("uploads/".$row['file_name']);
        $title = $row['title'];
        $conn->query("DELETE FROM study_materials WHERE id=$id");
        
        // Log Activity
        $msg = "Deleted material: $title";
        $conn->query("INSERT INTO portal_activity (user_name, user_role, action_type, message) VALUES ('$staffName', 'Staff', 'Delete', '$msg')");
    }
    header("Location: staff_studymaterial.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Study Material | Staff</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        :root {
            --primary: #6366f1;
            --sidebar: #0f172a;
            --bg: #f8fafc;
            --white: #ffffff;
            --text-dark: #1e293b;
            --text-light: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }

        body { background: var(--bg); display: flex; min-height: 100vh; }

        /* Sidebar Styling */
        .sidebar {
            width: 280px;
            background: var(--sidebar);
            padding: 30px 20px;
            position: fixed;
            height: 100vh;
            color: white;
            z-index: 100;
        }

        .sidebar h2 { font-size: 20px; margin-bottom: 40px; color: var(--primary); font-weight: 800; letter-spacing: 1px; }
        .sidebar ul { list-style: none; }
        .sidebar a {
            display: flex; align-items: center; gap: 12px;
            text-decoration: none; color: #94a3b8;
            padding: 14px 20px; border-radius: 12px; transition: 0.3s; margin-bottom: 8px;
        }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.1); color: #fff; }
        .sidebar a.active { background: var(--primary); box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3); }

        /* Main Content */
        .main-content { margin-left: 280px; flex: 1; padding: 40px; }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .header h1 { font-size: 28px; color: var(--text-dark); }

        /* Form Card */
        .form-card {
            background: var(--white);
            padding: 30px;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.02);
            margin-bottom: 40px;
            border: 1px solid #e2e8f0;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .full-row { grid-column: span 2; }

        input, textarea {
            width: 100%;
            padding: 14px 18px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }

        input:focus, textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }

        .btn-upload {
            background: var(--primary);
            color: white; border: none;
            padding: 14px 30px; border-radius: 12px;
            font-weight: 700; cursor: pointer;
            transition: 0.3s; margin-top: 10px;
        }
        .btn-upload:hover { background: #4f46e5; transform: translateY(-2px); }

        /* Table Styling */
        .table-card {
            background: var(--white);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.02);
            border: 1px solid #e2e8f0;
        }

        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; color: var(--text-light); font-weight: 600; text-align: left; padding: 18px 25px; font-size: 13px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        td { padding: 18px 25px; color: var(--text-dark); font-size: 14px; border-bottom: 1px solid #f1f5f9; }

        .badge {
            padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .bg-subject { background: #e0e7ff; color: #4338ca; }

        .action-btns a {
            text-decoration: none; font-size: 18px; margin-right: 15px; transition: 0.3s;
        }
        .btn-view { color: #10b981; }
        .btn-del { color: #ef4444; }
        .action-btns a:hover { opacity: 0.7; }

        /* Responsive */
        @media (max-width: 1000px) {
            .sidebar { width: 80px; padding: 20px 10px; }
            .sidebar h2, .sidebar span { display: none; }
            .main-content { margin-left: 80px; }
            .form-grid { grid-template-columns: 1fr; }
            .full-row { grid-column: span 1; }
        }
    </style>
</head>
<body>

    <!-- Sidebar Dashboard -->
    <div class="sidebar">
        <h2>STAFF HUB</h2>
        <ul>
            <li><a href="staff_dashboard.php"><i class="fa-solid fa-house"></i> <span>Dashboard</span></a></li>
            <li><a href="staff_studymaterial.php" class="active"><i class="fa-solid fa-file-arrow-up"></i> <span>Study Material</span></a></li>
            <li><a href="staff_questionbank.php"><i class="fa-solid fa-laptop-code"></i> <span>Question Bank</span></a></li>
            <li><a href="Staff_alert.php"><i class="fa-solid fa-bell"></i> <span>Portal Activity</span></a></li>
            <li><a href="staff_profile.php"><i class="fa-solid fa-user"></i> <span>Profile</span></a></li>
            <li><a href="staff_logout.php" style="margin-top: 50px; color: #f87171;"><i class="fa-solid fa-power-off"></i> <span>Logout</span></a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div>
                <h1>Study Materials</h1>
                <p style="color: var(--text-light);">Upload and manage academic resources for students</p>
            </div>
            <div style="text-align: right;">
                <p style="font-weight: 700;"><?php echo $staffName; ?></p>
                <p style="font-size: 12px; color: var(--primary);">Academic Staff</p>
            </div>
        </div>

        <!-- Upload Form -->
        <div class="form-card">
            <h3 style="margin-bottom: 20px;">Upload New Resource</h3>
            <form method="post" enctype="multipart/form-data">
                <div class="form-grid">
                    <input type="text" name="subject" placeholder="Subject Name (e.g. Mathematics)" required>
                    <input type="text" name="title" placeholder="Material Title (e.g. Algebra Chapter 1)" required>
                    <div class="full-row">
                        <textarea name="description" placeholder="Short description about the material..." rows="3"></textarea>
                    </div>
                    <input type="file" name="file" required>
                    <button name="upload" class="btn-upload">
                        <i class="fa-solid fa-cloud-arrow-up"></i> Upload Material
                    </button>
                </div>
            </form>
        </div>

        <!-- Materials Table -->
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Material Details</th>
                        <th>Upload Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM study_materials ORDER BY id DESC");
                    if($result->num_rows > 0):
                        while($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><span class="badge bg-subject"><?= $row['subject']; ?></span></td>
                        <td>
                            <strong><?= $row['title']; ?></strong><br>
                            <small style="color: #64748b;"><?= $row['description']; ?></small>
                        </td>
                        <td><?= date('d M Y', strtotime($row['upload_date'])); ?></td>
                        <td class="action-btns">
                            <a href="uploads/<?= $row['file_name']; ?>" target="_blank" class="btn-view" title="View File">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="?delete=<?= $row['id']; ?>" onclick="return confirm('Delete this file?')" class="btn-del" title="Delete">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: #94a3b8;">
                            No materials uploaded yet.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>