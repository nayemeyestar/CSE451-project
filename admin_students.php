<?php
// FILE: admin_students.php
require_once 'admin_auth.php';
$page_title = "Manage Students";

// Activate / deactivate / delete
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'activate') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($_GET['action'] === 'deactivate') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$id]);
    } elseif ($_GET['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    }
    header('Location: admin_students.php');
    exit;
}

$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeProfiles = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$withCgpa = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE cgpa_current > 0")->fetchColumn();
$targetSet = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE target_country IS NOT NULL AND target_country <> ''")->fetchColumn();

$studentsStmt = $pdo->query("
    SELECT id, full_name, email, country, cgpa_current, ielts_score, status, created_at
    FROM users
    ORDER BY created_at DESC
");
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

include 'admin_header.php';
?>

<div class="page-title">
    <h1>Manage Students</h1>
    <p>View and manage student accounts and profiles.</p>
</div>

<div class="grid grid-4">
    <div class="glass-card stat-card">
        <h3>Total Students</h3>
        <p class="stat-value"><?= $totalStudents ?></p>
    </div>
    <div class="glass-card stat-card">
        <h3>Active Profiles</h3>
        <p class="stat-value"><?= $activeProfiles ?></p>
    </div>
    <!-- <div class="glass-card stat-card">
        <h3>With CGPA</h3>
        <p class="stat-value"><?= $withCgpa ?></p>
    </div> -->
    <!-- <div class="glass-card stat-card">
        <h3>Target Set</h3>
        <p class="stat-value"><?= $targetSet ?></p>
    </div> -->
</div>

<div class="glass-card mt-3">
    <h2>Student List</h2>
    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse; font-size:14px;">
            <thead>
            <tr>
                <th align="left">Student</th>
                <th align="left">Profile</th>
                <th align="left">Academic</th>
                <th align="left">Status</th>
                <th align="left">Joined</th>
                <th align="left">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($students): ?>
                <?php foreach ($students as $s): ?>
                    <tr style="border-top:1px solid rgba(255,255,255,0.1);">
                        <td>
                            <strong><?= htmlspecialchars($s['full_name']) ?></strong><br>
                            <?= htmlspecialchars($s['email']) ?>
                        </td>
                        <td><?= htmlspecialchars($s['country'] ?? 'N/A') ?></td>
                        <td>
                            CGPA: <?= number_format($s['cgpa_current'], 2) ?><br>
                            IELTS: <?= $s['ielts_score'] > 0 ? $s['ielts_score'] : 'N/A' ?>
                        </td>
                        <td>
                            <?php if ($s['status'] === 'active'): ?>
                                <span class="badge" style="background:#4ade80;">Active</span>
                            <?php else: ?>
                                <span class="badge" style="background:#f97373;">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(date('M d, Y', strtotime($s['created_at']))) ?></td>
                        <td>
                            <?php if ($s['status'] === 'active'): ?>
                                <a href="admin_students.php?action=deactivate&id=<?= $s['id'] ?>" style="color:#f97373;">Deactivate</a>
                            <?php else: ?>
                                <a href="admin_students.php?action=activate&id=<?= $s['id'] ?>" style="color:#22c55e;">Activate</a>
                            <?php endif; ?>
                            &nbsp;|&nbsp;
                            <a href="admin_students.php?action=delete&id=<?= $s['id'] ?>" style="color:#ef4444;"
                               onclick="return confirm('Delete this student?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">No students yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
