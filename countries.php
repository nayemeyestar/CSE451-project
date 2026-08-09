<?php
// FILE: countries.php
require_once 'auth_check.php';

$page_title = "Country Guidelines";

// Fetch all countries
$stmt = $pdo->query("SELECT * FROM countries ORDER BY name ASC");
$countries = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
include 'sidebar.php';
?>

<div class="page-title">
    <h1>Country-Specific Guidelines</h1>
    <p>Explore requirements, costs, and deadlines for your target countries.</p>
</div>

<div class="glass-card">
    <input type="text" id="countrySearch" class="search-box"
           placeholder="Search countries...">
</div>

<div class="grid grid-3 mt-3" id="countryContainer">
    <?php foreach ($countries as $c): ?>
        <div class="glass-card country-card" data-name="<?= strtolower($c['name']) ?>">
            <div class="country-header">
                <h3><?= htmlspecialchars($c['name']) ?></h3>
                <div class="requirements-badges">
                    <span class="badge">Min CGPA: <?= number_format($c['min_cgpa'], 2) ?></span>
                    <span class="badge">Min IELTS: <?= number_format($c['min_ielts'], 1) ?></span>
                </div>
            </div>

            <div class="country-body">
                <h4>Requirements</h4>
                <p><?= nl2br(htmlspecialchars($c['requirements_text'])) ?></p>

                <h4>Estimated Costs</h4>
                <p><?= htmlspecialchars($c['estimated_cost_min']) ?> – <?= htmlspecialchars($c['estimated_cost_max']) ?> / year</p>

                <h4>Application Deadlines</h4>
                <p><?= htmlspecialchars($c['application_deadline']) ?></p>

                <!-- Eligibility calculation -->
                <?php
                // Load logged-in student CGPA + IELTS
                $stmtUser = $pdo->prepare("SELECT cgpa_current, ielts_score FROM users WHERE id = ?");
                $stmtUser->execute([$_SESSION['user_id']]);
                $stu = $stmtUser->fetch(PDO::FETCH_ASSOC);

                $matchCount = 0;
                $totalReq = 2;

                if ($stu['cgpa_current'] >= $c['min_cgpa']) $matchCount++;
                if ($stu['ielts_score'] >= $c['min_ielts']) $matchCount++;

                $percent = round(($matchCount / $totalReq) * 100);
                $eligible = $percent >= 100;
                ?>

                <h4>Your Eligibility</h4>
                <?php if ($percent == 100): ?>
                    <p class="eligible yes">✔ Eligible (100%)</p>
                <?php else: ?>
                    <p class="eligible no">✖ Needs Improvement (<?= $percent ?>%)</p>
                <?php endif; ?>

                <ul class="eligibility-details">
                    <li>
                        CGPA:
                        <?php if ($stu['cgpa_current'] >= $c['min_cgpa']): ?>
                            <span class="pass">✔</span>
                        <?php else: ?>
                            <span class="fail">✖ (<?= number_format($stu['cgpa_current'],2) ?> < <?= number_format($c['min_cgpa'],2) ?>)</span>
                        <?php endif; ?>
                    </li>

                    <li>
                        IELTS:
                        <?php if ($stu['ielts_score'] >= $c['min_ielts']): ?>
                            <span class="pass">✔</span>
                        <?php else: ?>
                            <span class="fail">✖ (<?= number_format($stu['ielts_score'],1) ?> < <?= number_format($c['min_ielts'],1) ?>)</span>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    // Country search filter
    const searchBox = document.getElementById('countrySearch');
    const cards = document.querySelectorAll('.country-card');

    searchBox.addEventListener('input', () => {
        const term = searchBox.value.toLowerCase();
        cards.forEach(card => {
            const name = card.dataset.name;
            card.style.display = name.includes(term) ? 'block' : 'none';
        });
    });
</script>

<style>
    .search-box { width: 100%; padding: 10px; font-size: 16px; }
    .country-card { transition: 0.3s; cursor: pointer; }
    .country-card:hover { transform: translateY(-5px); }
    .country-header h3 { margin: 0; }
    .badge { background: #3b82f6; color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 13px; }
    .eligible.yes { color: green; font-weight: bold; }
    .eligible.no { color: red; font-weight: bold; }
    .pass { color:green; }
    .fail { color:red; }
</style>

<?php include 'footer.php'; ?>
