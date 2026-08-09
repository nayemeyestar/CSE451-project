<?php
// FILE: eligibility.php
require_once 'auth_check.php';

$page_title = "Eligibility Score Calculator";
$userId = $_SESSION['user_id'];

// Fetch current user data
$stmtUser = $pdo->prepare("SELECT full_name, cgpa_current, ielts_score FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

// Fetch English course scores (eng101, eng103, eng105)
$stmtEng = $pdo->prepare("SELECT * FROM english_scores WHERE user_id = ?");
$stmtEng->execute([$userId]);
$eng = $stmtEng->fetch(PDO::FETCH_ASSOC);

$eng101 = $eng['eng101'] ?? 0;
$eng103 = $eng['eng103'] ?? 0;
$eng105 = $eng['eng105'] ?? 0;

// Save English course scores
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_eng_scores'])) {
    $eng101 = (float)$_POST['eng101'];
    $eng103 = (float)$_POST['eng103'];
    $eng105 = (float)$_POST['eng105'];

    if ($eng) {
        // Update existing row
        $stmt = $pdo->prepare("UPDATE english_scores SET eng101=?, eng103=?, eng105=? WHERE user_id=?");
        $stmt->execute([$eng101, $eng103, $eng105, $userId]);
    } else {
        // Insert new row
        $stmt = $pdo->prepare("INSERT INTO english_scores (user_id, eng101, eng103, eng105) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $eng101, $eng103, $eng105]);
    }

    $message = "English scores updated successfully!";
}

// Calculate Eligibility Score
$cgpa = (float)$user['cgpa_current'];
$ielts = (float)$user['ielts_score'];
$engAvg = ($eng101 + $eng103 + $eng105) / 3;

$eligibilityScore = round(
    ($cgpa / 4.0) * 40 +        // CGPA contributes 40%
    ($engAvg / 100) * 40 +      // English course avg contributes 40%
    ($ielts / 9.0) * 20         // IELTS contributes 20%
);

include 'header.php';
include 'sidebar.php';
?>

<div class="page-title">
    <h1>Eligibility Calculator</h1>
    <p>Measure your overall readiness for studying abroad.</p>
</div>

<div class="grid grid-2 mt-2">

    <!-- Left: English Scores Form -->
    <div class="glass-card">
        <h2>English Course Scores</h2>

        <?php if ($message): ?>
            <div class="alert success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post">

            <label>ENG101 Score (0-100)</label>
            <input type="number" name="eng101" min="0" max="100" step="1" value="<?= $eng101 ?>" required>

            <label>ENG103 Score (0-100)</label>
            <input type="number" name="eng103" min="0" max="100" step="1" value="<?= $eng103 ?>" required>

            <label>ENG105 Score (0-100)</label>
            <input type="number" name="eng105" min="0" max="100" step="1" value="<?= $eng105 ?>" required>

            <button type="submit" name="save_eng_scores" class="btn-primary mt-2 full-width">
                Save English Scores
            </button>

        </form>
    </div>

    <!-- Right: Eligibility Summary -->
    <div class="glass-card">
        <h2>Your Eligibility Score</h2>

        <div class="elig-score-box">
            <h1><?= $eligibilityScore ?>/100</h1>
        </div>

        <ul class="elig-details">
            <li><strong>CGPA (40%)</strong>: <?= number_format($cgpa,2) ?> / 4.00</li>
            <li><strong>IELTS (20%)</strong>: <?= number_format($ielts,1) ?> / 9.00</li>
            <li><strong>English Course Avg (40%)</strong>: <?= number_format($engAvg,2) ?> / 100</li>
        </ul>

        <?php if ($eligibilityScore >= 80): ?>
            <p class="elig-high">Excellent! You have a strong profile for studying abroad.</p>
        <?php elseif ($eligibilityScore >= 60): ?>
            <p class="elig-medium">Good! You meet most requirements; keep improving.</p>
        <?php else: ?>
            <p class="elig-low">Low eligibility. Focus on improving CGPA, English scores, or IELTS.</p>
        <?php endif; ?>
    </div>

</div>

<style>
    .elig-score-box {
        text-align: center;
        margin-bottom: 12px;
    }
    .elig-score-box h1 {
        font-size: 48px;
        margin: 0;
        color: #3b82f6;
    }
    .elig-details {
        list-style: none;
        padding: 0;
        margin: 15px 0;
        font-size: 15px;
    }
    .elig-details li {
        margin: 6px 0;
    }
    .elig-high { color: green; }
    .elig-medium { color: orange; }
    .elig-low { color: red; }
</style>

<?php include 'footer.php'; ?>
