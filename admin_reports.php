<?php
// FILE: admin_reports.php
require_once 'admin_auth.php';

$page_title = "Reports & Analytics";

/*
  Assumptions:
  - users table has: id, created_at (DATETIME), status, target_country
  - semesters table: id, user_id, cgpa (we just count all rows)
  - ielts_results table: id, user_id, score (we just count all rows)
*/

// --------- Date Filter Logic ----------
$today = new DateTime();
$defaultStart = (clone $today)->modify('-30 days')->format('Y-m-d');
$defaultEnd   = $today->format('Y-m-d');

$startDate = $_GET['start_date'] ?? $defaultStart;
$endDate   = $_GET['end_date'] ?? $defaultEnd;

try {
    $startObj = new DateTime($startDate);
    $endObj   = new DateTime($endDate);
} catch (Exception $e) {
    $startObj = new DateTime($defaultStart);
    $endObj   = new DateTime($defaultEnd);
}

$startDateSql = $startObj->format('Y-m-d 00:00:00');
$endDateSql   = $endObj->format('Y-m-d 23:59:59');

// --------- Summary Numbers (filtered for students) ----------
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE created_at BETWEEN ? AND ?");
$stmt->execute([$startDateSql, $endDateSql]);
$totalStudents = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM users
    WHERE status = 'active' AND created_at BETWEEN ? AND ?
");
$stmt->execute([$startDateSql, $endDateSql]);
$activeProfiles = (int) $stmt->fetchColumn();

// CGPA records (overall)
$cgpaRecords = (int)$pdo->query("SELECT COUNT(*) FROM semesters")->fetchColumn();

// English scores (overall IELTS attempts)
$englishScores = 0;
if ($pdo->query("SHOW TABLES LIKE 'ielts_results'")->rowCount() > 0) {
    $englishScores = (int)$pdo->query("SELECT COUNT(*) FROM ielts_results")->fetchColumn();
}

// --------- Student Registration Trends (line chart) ----------
$trendStmt = $pdo->prepare("
    SELECT DATE(created_at) AS d, COUNT(*) AS total_reg
    FROM users
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at)
");
$trendStmt->execute([$startDateSql, $endDateSql]);
$trendRows = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

$trendLabels = [];
$trendValues = [];
foreach ($trendRows as $row) {
    $trendLabels[] = date('M d', strtotime($row['d']));
    $trendValues[] = (int)$row['total_reg'];
}

// --------- Target Country Distribution (doughnut chart) ----------
$targetRows = $pdo->query("
    SELECT target_country, COUNT(*) AS cnt
    FROM users
    WHERE target_country IS NOT NULL AND target_country <> ''
    GROUP BY target_country
    ORDER BY cnt DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

$targetLabels = array_column($targetRows, 'target_country');
$targetValues = array_map('intval', array_column($targetRows, 'cnt'));

include 'admin_header.php';
?>

<div class="page-title">
    <h1>Reports &amp; Analytics</h1>
    <p>Comprehensive insights into system performance and student data.</p>
</div>

<!-- Filter by Date Range -->
<div class="glass-card">
    <h2>Filter by Date Range</h2>
    <form method="get" class="grid grid-3" style="align-items:flex-end;">
        <div>
            <label>Start Date</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($startObj->format('Y-m-d')) ?>">
        </div>
        <div>
            <label>End Date</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endObj->format('Y-m-d')) ?>">
        </div>
        <div>
            <button type="submit" class="btn-primary" style="margin-top:4px;">Apply Filter</button>
        </div>
    </form>
</div>

<!-- Summary cards -->
<div class="grid grid-4 mt-3">
    <div class="glass-card stat-card">
        <h3>Total Students</h3>
        <p class="stat-value"><?= $totalStudents ?></p>
    </div>
    <div class="glass-card stat-card">
        <h3>Active Profiles</h3>
        <p class="stat-value"><?= $activeProfiles ?></p>
    </div>
    <!-- <div class="glass-card stat-card">
        <h3>CGPA Records</h3>
        <p class="stat-value"><?= $cgpaRecords ?></p>
    </div> -->
    <!-- <div class="glass-card stat-card">
        <h3>English Scores</h3>
        <p class="stat-value"><?= $englishScores ?></p>
    </div> -->
</div>

<!-- Charts -->
<div class="grid grid-2 mt-3">
    <div class="glass-card">
        <h2>Student Registration Trends</h2>
        <div style="height:240px;">
            <canvas id="regTrendChart"></canvas>
        </div>
    </div>

    <!-- <div class="glass-card">
        <h2>Target Country Distribution</h2>
        <div style="height:240px;">
            <canvas id="targetCountryChart"></canvas>
        </div>
    </div> -->
</div>

<script>
    // Use white text by default for charts on dark background
    Chart.defaults.color       = '#ffffff';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.25)';

    // --- Registration trend line chart ---
    const regLabels = <?= json_encode($trendLabels) ?>;
    const regValues = <?= json_encode($trendValues) ?>;

    (function () {
        const canvas = document.getElementById('regTrendChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        if (!regLabels.length) {
            ctx.font = '14px Arial';
            ctx.fillStyle = '#ffffff';
            ctx.fillText('No registrations in this period.', 20, 60);
            return;
        }

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: regLabels,
                datasets: [{
                    data: regValues,
                    borderColor: '#60a5fa',
                    backgroundColor: 'rgba(96,165,250,0.25)',
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 3,
                    pointBackgroundColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,0.08)' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.08)' }
                    }
                }
            }
        });
    })();

    // --- Target country doughnut chart ---
    const targetLabels = <?= json_encode($targetLabels) ?>;
    const targetValues = <?= json_encode($targetValues) ?>;

    (function () {
        const canvas = document.getElementById('targetCountryChart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');

        if (!targetLabels.length) {
            ctx.font = '14px Arial';
            ctx.fillStyle = '#ffffff';
            ctx.fillText('No target country data yet.', 20, 60);
            return;
        }

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: targetLabels,
                datasets: [{
                    data: targetValues,
                    backgroundColor: [
                        '#60a5fa','#a855f7','#f97316','#22c55e',
                        '#eab308','#f43f5e','#06b6d4','#8b5cf6'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '55%' // donut style similar to your design
            }
        });
    })();
</script>

<?php include 'admin_footer.php'; ?>
