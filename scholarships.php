<?php
// FILE: scholarships.php
require_once 'auth_check.php';

$page_title = "Scholarship Matcher";

// Load student data
$userId = $_SESSION['user_id'];
$stmtStu = $pdo->prepare("SELECT cgpa_current, ielts_score FROM users WHERE id = ?");
$stmtStu->execute([$userId]);
$student = $stmtStu->fetch(PDO::FETCH_ASSOC);

$stuCgpa  = (float)$student['cgpa_current'];
$stuIelts = (float)$student['ielts_score'];

// Load scholarships (NO JOIN, use text country column)
$stmt = $pdo->query("
    SELECT *
    FROM scholarships
    ORDER BY name ASC
");
$scholarships = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
include 'sidebar.php';
?>

<div class="page-title">
    <h1>Scholarship Matcher</h1>
    <p>Find scholarships that match your CGPA, IELTS score, and study goals.</p>
</div>

<div class="glass-card">
    <input type="text" id="schSearch" class="search-box"
           placeholder="Search scholarships, countries...">
</div>

<div class="grid grid-3 mt-3" id="schContainer">
    <?php foreach ($scholarships as $s): ?>
        <?php
        // Matching algorithm
        $requirements = 0;
        $met = 0;

        // 1. CGPA
        $requirements++;
        if ($stuCgpa >= $s['min_cgpa']) $met++;

        // 2. IELTS
        $requirements++;
        if ($stuIelts >= $s['min_ielts']) $met++;

        // 3. Research (optional)
        $requirements++;
        if ($s['requires_research_proposal'] == 0) $met++;

        // 4. Financial Proof
        $requirements++;
        if ($s['requires_financial_proof'] == 0) $met++;

        $matchPercent = round(($met / $requirements) * 100);

        $countryLabel = $s['country']; // use text column
        ?>

        <div class="glass-card sch-card"
             data-name="<?= strtolower($s['name'] . ' ' . $countryLabel) ?>">
            <div class="sch-header">
                <h3><?= htmlspecialchars($s['name']) ?></h3>
                <p class="country-tag"><?= htmlspecialchars($countryLabel) ?></p>
            </div>

            <div class="sch-body">
                <h4>Requirements</h4>
                <ul class="requirements-list">
                    <li>Min CGPA: <?= number_format($s['min_cgpa'], 2) ?></li>
                    <li>Min IELTS: <?= number_format($s['min_ielts'], 1) ?></li>
                    <li>Research Proposal: <?= $s['requires_research_proposal'] ? 'Required' : 'Not Required' ?></li>
                    <li>Financial Proof: <?= $s['requires_financial_proof'] ? 'Required' : 'Not Required' ?></li>
                </ul>

                <h4>Description</h4>
                <p><?= nl2br(htmlspecialchars($s['description'])) ?></p>

                <h4>Monthly Stipend</h4>
                <p><?= $s['stipend_per_month'] > 0 ? $s['stipend_per_month'] . ' USD/month' : 'N/A' ?></p>

                <h4>Your Match Score</h4>
                <?php if ($matchPercent >= 75): ?>
                    <p class="match high">Excellent Match (<?= $matchPercent ?>%)</p>
                <?php elseif ($matchPercent >= 50): ?>
                    <p class="match medium">Moderate Match (<?= $matchPercent ?>%)</p>
                <?php else: ?>
                    <p class="match low">Low Match (<?= $matchPercent ?>%)</p>
                <?php endif; ?>

                <ul class="eligibility-details">
                    <!-- CGPA -->
                    <li>
                        CGPA:
                        <?php if ($stuCgpa >= $s['min_cgpa']): ?>
                            <span class="pass">✔</span>
                        <?php else: ?>
                            <span class="fail">
                                ✖ (<?= number_format($stuCgpa,2) ?> < <?= number_format($s['min_cgpa'],2) ?>)
                            </span>
                        <?php endif; ?>
                    </li>

                    <!-- IELTS -->
                    <li>
                        IELTS:
                        <?php if ($stuIelts >= $s['min_ielts']): ?>
                            <span class="pass">✔</span>
                        <?php else: ?>
                            <span class="fail">
                                ✖ (<?= number_format($stuIelts,1) ?> < <?= number_format($s['min_ielts'],1) ?>)
                            </span>
                        <?php endif; ?>
                    </li>

                    <!-- Research -->
                    <li>
                        Research Proposal:
                        <?php if ($s['requires_research_proposal'] == 0): ?>
                            <span class="pass">✔ Not required</span>
                        <?php else: ?>
                            <span class="warning">⚠ Required</span>
                        <?php endif; ?>
                    </li>

                    <!-- Financial Proof -->
                    <li>
                        Financial Proof:
                        <?php if ($s['requires_financial_proof'] == 0): ?>
                            <span class="pass">✔ Not required</span>
                        <?php else: ?>
                            <span class="warning">⚠ Required</span>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    // Search filter
    const schSearch = document.getElementById('schSearch');
    const schCards = document.querySelectorAll('.sch-card');

    schSearch.addEventListener('input', () => {
        const term = schSearch.value.toLowerCase();
        schCards.forEach(card => {
            const name = card.dataset.name;
            card.style.display = name.includes(term) ? 'block' : 'none';
        });
    });
</script>

<style>
    .sch-card { transition: 0.3s; cursor: pointer; }
    .sch-card:hover { transform: translateY(-6px); }

    .country-tag {
        font-size: 14px;
        background: #3b82f6;
        color: white;
        padding: 3px 10px;
        border-radius: 5px;
        display: inline-block;
        margin-top: 4px;
    }

    .match.high { color: green; font-weight: bold; }
    .match.medium { color: orange; font-weight: bold; }
    .match.low { color: red; font-weight: bold; }

    .pass { color: green; }
    .fail { color: red; }
    .warning { color: orange; }
</style>

<?php include 'footer.php'; ?>
