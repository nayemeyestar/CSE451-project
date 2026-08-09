<?php
// FILE: cgpa_tracker.php
require_once 'auth_check.php';

$page_title = "CGPA Progress Tracker";

$userId  = $_SESSION['user_id'];
$message = '';
$error   = '';

/* -----------------------------------------------------------
   INSERT SEMESTER CGPA
----------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cgpa'])) {
    $semesterName = trim($_POST['semester_name'] ?? '');
    $cgpaValue    = $_POST['cgpa'] ?? '';

    if ($semesterName === '' || $cgpaValue === '') {
        $error = "Semester and CGPA are required.";
    } else {
        $cgpaFloat = (float)$cgpaValue;

        if ($cgpaFloat < 0 || $cgpaFloat > 4) {
            $error = "CGPA must be between 0.00 and 4.00.";
        } else {
            // Insert
            $stmt = $pdo->prepare("INSERT INTO semesters (user_id, semester_name, cgpa) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $semesterName, $cgpaFloat]);

            // Update current CGPA in users table
            $stmt = $pdo->prepare("UPDATE users SET cgpa_current = ? WHERE id = ?");
            $stmt->execute([$cgpaFloat, $userId]);

            $message = "CGPA for {$semesterName} saved successfully.";
        }
    }
}

/* -----------------------------------------------------------
   LOAD USER CGPA + SEMESTERS
----------------------------------------------------------- */
$stmt = $pdo->prepare("SELECT cgpa_current FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$currentCgpa = (float)($user['cgpa_current'] ?? 0);

$stmt = $pdo->prepare("SELECT semester_name, cgpa FROM semesters WHERE user_id = ? ORDER BY id ASC");
$stmt->execute([$userId]);
$semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* -----------------------------------------------------------
   STATS
----------------------------------------------------------- */
$labels = [];
$values = [];
$totalSem = 0;
$avgCgpa  = 0;
$maxCgpa  = 0;

if ($semesters) {
    $sum = 0;
    foreach ($semesters as $row) {
        $labels[] = $row['semester_name'];
        $values[] = (float)$row['cgpa'];
        $sum     += (float)$row['cgpa'];
        if ((float)$row['cgpa'] > $maxCgpa) {
            $maxCgpa = (float)$row['cgpa'];
        }
    }
    $totalSem = count($semesters);
    $avgCgpa  = $totalSem ? $sum / $totalSem : 0;
}

$riskThreshold = 3.0;
$isAtRisk = $currentCgpa > 0 && $currentCgpa < $riskThreshold;

include 'header.php';
include 'sidebar.php';
?>

<div class="page-title">
    <h1>CGPA Progress Tracker</h1>
    <p>Monitor your academic performance semester by semester.</p>
</div>

<div class="grid grid-2">

    <!-- LEFT CARD: ADD CGPA -->
    <div class="glass-card">
        <h2>Add / Update CGPA</h2>

        <?php if ($message): ?>
            <div class="alert success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <label>Semester</label>
            <input type="text" name="semester_name" placeholder="e.g., Fall 2023, Spring 2024" required>

            <label>CGPA</label>
            <input type="number" name="cgpa" step="0.01" min="0" max="4" placeholder="0.00 - 4.00" required>

            <button type="submit" name="save_cgpa" class="btn-primary full-width">
                Save CGPA
            </button>
        </form>

        <div class="stats-box mt-3">
            <h3>Statistics</h3>
            <div class="stats-row"><span>Total Semesters:</span> <strong><?= $totalSem ?></strong></div>
            <div class="stats-row"><span>Average CGPA:</span> <strong><?= number_format($avgCgpa, 2) ?></strong></div>
            <div class="stats-row"><span>Highest CGPA:</span> <strong><?= number_format($maxCgpa, 2) ?></strong></div>
            <div class="stats-row"><span>Current CGPA:</span> <strong><?= number_format($currentCgpa, 2) ?></strong></div>
        </div>
    </div>

    <!-- RIGHT CARD: CHART -->
    <div class="glass-card">
        <h2>CGPA Progress Chart</h2>

        <div class="cgpa-chart-container">
            <canvas id="cgpaChart"></canvas>
        </div>

        <?php if ($isAtRisk): ?>
            <div class="alert warning mt-3">
                <strong>At Risk:</strong> Your current CGPA (<?= number_format($currentCgpa, 2) ?>)
                is below the recommended threshold of <?= number_format($riskThreshold, 1) ?>.
            </div>
        <?php elseif ($currentCgpa >= $riskThreshold && $currentCgpa > 0): ?>
            <div class="alert success mt-3">
                <strong>Good job!</strong> Your current CGPA (<?= number_format($currentCgpa, 2) ?>)
                meets or exceeds the recommended threshold of <?= number_format($riskThreshold, 1) ?>.
            </div>
        <?php else: ?>
            <div class="alert info mt-3">
                Add CGPA records to see risk or progress.
            </div>
        <?php endif; ?>
    </div>
</div>


<!-- ============================================
     CHART SCRIPT (COMPACT + WHITE TEXT)
=============================================== -->
<script>
(function () {

    const labels = <?= json_encode($labels) ?>;
    const values = <?= json_encode($values) ?>;
    const canvas = document.getElementById('cgpaChart');
    const ctx     = canvas.getContext('2d');

    // No data placeholder
    if (!labels.length) {
        ctx.font = '14px Arial';
        ctx.fillStyle = '#ffffff';
        ctx.fillText('No CGPA data yet. Add a semester to see the chart.', 20, 60);
        return;
    }

    // GLOBAL CHART COLORS
    Chart.defaults.color        = '#ffffff';
    Chart.defaults.borderColor  = 'rgba(255,255,255,0.25)';

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                borderColor: '#60a5fa',
                backgroundColor: 'rgba(96,165,250,0.25)',
                borderWidth: 2,
                pointRadius: 4,
                pointBackgroundColor: '#ffffff',
                tension: 0.35,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,  // IMPORTANT FIX FOR SIZE
            scales: {
                x: {
                    ticks: { color: '#ffffff' },
                    grid:  { color: 'rgba(255,255,255,0.08)' }
                },
                y: {
                    min: 0,
                    max: 4,
                    ticks: {
                        stepSize: 0.5,
                        color: '#ffffff'
                    },
                    grid: { color: 'rgba(255,255,255,0.08)' },
                    title: {
                        display: true,
                        text: 'CGPA',
                        color: '#ffffff'
                    }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
})();
</script>


<!-- ============================================
     CSS (COMPACT CHART SIZING)
=============================================== -->
<style>
.cgpa-chart-container {
    width: 100%;
    max-height: 220px;
    padding: 6px 0;
}

.cgpa-chart-container canvas {
    max-height: 180px !important;
    height: 180px !important;
}

.stats-box {
    margin-top: 10px;
    font-size: 14px;
}

.stats-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 4px;
}
</style>

<?php include 'footer.php'; ?>
