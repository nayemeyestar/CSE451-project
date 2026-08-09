<?php
// FILE: edit_profile.php
require_once 'auth_check.php';

$page_title = "Edit Profile";
$userId = $_SESSION['user_id'];


$stmt = $pdo->prepare("SELECT id, full_name, email, cgpa_current, ielts_score FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

$fullName = $user['full_name'];
$email    = $user['email'];
$cgpa     = $user['cgpa_current'];
$ielts    = $user['ielts_score'];

$message = "";
$error   = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $newName  = trim($_POST['full_name'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newCgpa  = isset($_POST['cgpa']) ? (float)$_POST['cgpa'] : 0;
    $newIelts = isset($_POST['ielts']) ? (float)$_POST['ielts'] : 0;
    $newPassword = $_POST['password'] ?? "";

    if ($newName === "" || $newEmail === "") {
        $error = "Name and Email are required.";
    } else {

        // If user typed a new password, update password_hash too
        if ($newPassword !== "") {
            $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users
                SET full_name = ?, email = ?, cgpa_current = ?, ielts_score = ?, password_hash = ?
                WHERE id = ?
            ");
            $stmt->execute([$newName, $newEmail, $newCgpa, $newIelts, $hashed, $userId]);
        } else {
            // Update without changing password
            $stmt = $pdo->prepare("
                UPDATE users
                SET full_name = ?, email = ?, cgpa_current = ?, ielts_score = ?
                WHERE id = ?
            ");
            $stmt->execute([$newName, $newEmail, $newCgpa, $newIelts, $userId]);
        }

        $_SESSION['user_name'] = $newName;
        $message = "Profile updated successfully!";

        // refresh variables for form
        $fullName = $newName;
        $email    = $newEmail;
        $cgpa     = $newCgpa;
        $ielts    = $newIelts;
    }
}

include 'header.php';
include 'sidebar.php';
?>

<div class="page-title">
    <h1>Edit Profile</h1>
    <p>Update your account information.</p>
</div>

<div class="glass-card" style="max-width: 700px;">
    <?php if ($message): ?>
        <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" class="grid grid-3">
        <div class="full-width">
            <label>Full Name</label>
            <input type="text" name="full_name"
                   value="<?= htmlspecialchars($fullName) ?>" required>
        </div>

        <div class="full-width">
            <label>Email</label>
            <input type="email" name="email"
                   value="<?= htmlspecialchars($email) ?>" required>
        </div>

        <div>
            <label>Current CGPA (0–4)</label>
            <input type="number" name="cgpa" step="0.01" min="0" max="4"
                   value="<?= htmlspecialchars($cgpa) ?>">
        </div>

        <div>
            <label>IELTS Score (0–9)</label>
            <input type="number" name="ielts" step="0.5" min="0" max="9"
                   value="<?= htmlspecialchars($ielts) ?>">
        </div>

        <div>
            <label>New Password (optional)</label>
            <input type="password" name="password"
                   placeholder="Leave empty to keep current password">
        </div>

        <div class="full-width">
            <button type="submit" class="btn-primary full-width">
                Save Changes
            </button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>
