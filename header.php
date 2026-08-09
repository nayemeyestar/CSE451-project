<?php
// FILE: header.php
// Assumes the page already included auth_check.php (for students)
// or admin_auth.php (for admins), which start the session.

if (!isset($page_title)) { 
    $page_title = "StudyTrack360"; 
}

// Decide display name based on session
$displayName = 'Student';
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
    // if you ever reuse this header for admin pages
    $displayName = $_SESSION['admin_name'] ?? 'Admin';
} else {
    $displayName = $_SESSION['user_name'] ?? 'Student';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?> - StudyTrack360</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Font & Icons & Chart.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="navbar">
    <div class="navbar-left">
        <div class="logo">🎓 StudyTrack360</div>
    </div>

    <div class="navbar-right">

        <!-- 🔔 Notification Dropdown -->
        <div class="nav-icon notification-dropdown">
            <i class="fa-solid fa-bell"></i>
            <div class="dropdown-menu notif-menu">
                <div class="dropdown-header">
                    Notifications
                </div>
                <div class="notification-list">
                    <div class="notif-item">
                        <strong>Welcome!</strong><br>
                        Your StudyTrack360 account is ready.
                    </div>
                    <div class="notif-item">
                        <strong>Tip:</strong> Update your CGPA in the CGPA Tracker to see an accurate eligibility score.
                    </div>
                    <div class="notif-item">
                        <strong>Reminder:</strong> Check country guidelines before shortlisting universities.
                    </div>
                </div>
                <div class="notif-footer">
                    No more notifications.
                </div>
            </div>
        </div>

        <!-- 👤 Profile Dropdown -->
        <div class="nav-icon profile-dropdown">
            <i class="fa-solid fa-user"></i>
            <div class="dropdown-menu">
                <div class="dropdown-header">
                    <?= htmlspecialchars($displayName); ?>
                </div>
                <a href="edit_profile.php" class="dropdown-item">Edit Profile</a>
            </div>
        </div>

        <!-- ⏏ Logout -->
        <div class="nav-icon">
            <a href="logout.php" class="logout-link">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </div>
</header>

<div class="page-wrapper">
