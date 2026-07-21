<?php
include("db.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student | Question Bank</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
:root {
    --primary: #4361ee;
    --primary-light: #4895ef;
    --secondary: #3f37c9;
    --text-dark: #1e293b;
    --text-light: #64748b;
    --bg-body: #f8faff;
    --glass: rgba(255, 255, 255, 0.9);
    --shadow: 0 10px 25px -5px rgba(67, 97, 238, 0.1), 0 8px 10px -6px rgba(67, 97, 238, 0.05);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Inter', 'Poppins', sans-serif;
}

body {
    background: var(--bg-body);
    background-image: 
        radial-gradient(at 0% 0%, rgba(67, 97, 238, 0.05) 0px, transparent 50%),
        radial-gradient(at 100% 100%, rgba(72, 149, 239, 0.05) 0px, transparent 50%);
    min-height: 100vh;
    padding: 40px 20px;
}

.container {
    max-width: 1100px;
    margin: auto;
}

/* Header Styling */
.header {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    padding: 60px 40px;
    border-radius: 24px;
    text-align: center;
    color: white;
    margin-bottom: -50px; /* Overlap effect */
    box-shadow: 0 20px 40px rgba(63, 55, 201, 0.25);
    position: relative;
    z-index: 1;
}

.header h1 {
    font-size: 2.5rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    margin-bottom: 10px;
}

.header p {
    font-size: 1.1rem;
    opacity: 0.9;
    font-weight: 300;
}

/* Search Bar Styling */
.search-container {
    position: relative;
    z-index: 2;
    max-width: 700px;
    margin: 0 auto 50px;
}

.search-container input {
    width: 100%;
    padding: 20px 30px 20px 60px;
    border-radius: 18px;
    border: none;
    background: white;
    font-size: 1.1rem;
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    outline: none;
}

.search-container input:focus {
    transform: translateY(-2px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.12);
}

/* Add search icon via pseudo-element */
.search-container::before {
    content: "\f002";
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    position: absolute;
    left: 25px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--primary);
    font-size: 1.2rem;
}

/* Grid & Cards */
.grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 25px;
    padding-top: 20px;
}

.card {
    background: var(--glass);
    backdrop-filter: blur(10px);
    padding: 30px;
    border-radius: 22px;
    border: 1px solid rgba(255, 255, 255, 0.6);
    box-shadow: var(--shadow);
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.card:hover {
    transform: translateY(-10px);
    background: white;
    box-shadow: 0 30px 60px -15px rgba(0,0,0,0.1);
    border-color: var(--primary-light);
}

.subject-tag {
    display: inline-block;
    background: rgba(67, 97, 238, 0.1);
    color: var(--primary);
    padding: 6px 14px;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 20px;
}

.card h3 {
    font-size: 1.25rem;
    color: var(--text-dark);
    margin-bottom: 12px;
    line-height: 1.4;
    font-weight: 700;
}

.card p {
    color: var(--text-light);
    font-size: 0.95rem;
    line-height: 1.6;
    margin-bottom: 25px;
    /* Limit to 2 lines */
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #f1f5f9;
    padding-top: 20px;
}

.year {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: var(--text-light);
    font-weight: 500;
}

.year i {
    color: var(--primary-light);
}

.download-btn {
    background: var(--primary);
    color: white;
    text-decoration: none;
    padding: 10px 22px;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.download-btn:hover {
    background: var(--secondary);
    box-shadow: 0 8px 15px rgba(63, 55, 201, 0.3);
}

/* Empty State Styling */
.no-results {
    grid-column: 1 / -1;
    text-align: center;
    padding: 50px;
    color: var(--text-light);
}

@media (max-width: 768px) {
    .header {
        padding: 40px 20px;
        margin-bottom: -30px;
    }
    .header h1 { font-size: 1.8rem; }
    .grid { grid-template-columns: 1fr; }
}
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Question Bank</h1>
        <p>Download previous year question papers and study materials.</p>
    </div>

    <!-- Simple Search Bar -->
    <div class="search-container">
        <input type="text" id="searchInput" placeholder="Search by subject or year..." onkeyup="filterCards()">
    </div>

    <div class="grid" id="cardGrid">
        <?php
        $res = $conn->query("SELECT * FROM question_bank ORDER BY id DESC");
        if($res->num_rows > 0){
            while($row = $res->fetch_assoc()){
        ?>
            <div class="card">
                <span class="subject-tag"><?= $row['subject'] ?></span>
                <h3><?= $row['title'] ?></h3>
                <p><?= $row['description'] ?></p>
                <div class="card-footer">
                    <span class="year"><i class="fa-regular fa-calendar"></i> <?= $row['year'] ?></span>
                    <a href="uploads/<?= $row['file_name'] ?>" class="download-btn" target="_blank">
                        <i class="fa-solid fa-download"></i> View
                    </a>
                </div>
            </div>
        <?php 
            }
        } else {
            echo "<p>No materials available at the moment.</p>";
        }
        ?>
    </div>
</div>

<script>
// Real-time Search Function
function filterCards() {
    let input = document.getElementById('searchInput').value.toLowerCase();
    let cards = document.getElementsByClassName('card');

    for (let i = 0; i < cards.length; i++) {
        let content = cards[i].innerText.toLowerCase();
        if (content.includes(input)) {
            cards[i].style.display = "";
        } else {
            cards[i].style.display = "none";
        }
    }
}
</script>

</body>
</html>