<?php
// FILE: admin_countries.php
require_once 'admin_auth.php';

$page_title = "Manage Countries";

$message = "";
$error   = "";

// Handle country submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $min_cgpa = (float)($_POST['min_cgpa'] ?? 0);
    $min_ielts = (float)($_POST['min_ielts'] ?? 0);
    $cost_min = (int)($_POST['estimated_cost_min'] ?? 0);
    $cost_max = (int)($_POST['estimated_cost_max'] ?? 0);
    $deadline = trim($_POST['application_deadline'] ?? '');
    $requirements = trim($_POST['requirements_text'] ?? '');

    if ($name === '') {
        $error = "Country name is required.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO countries 
            (name, min_cgpa, min_ielts, estimated_cost_min, estimated_cost_max, application_deadline, requirements_text)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $name,
            $min_cgpa,
            $min_ielts,
            $cost_min,
            $cost_max,
            $deadline,
            $requirements
        ]);

        $message = "Country added successfully!";
    }
}

// Load all countries
$countries = $pdo->query("SELECT * FROM countries ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

include 'admin_header.php';
?>

<div class="page-title">
    <h1>Manage Countries</h1>
    <p>Add, edit, and manage country requirements.</p>
</div>

<div class="glass-card">
    <h2>Add New Country</h2>

    <?php if ($message): ?>
        <div class="alert success"><?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" class="grid grid-2">

        <div>
            <label>Country Name *</label>
            <input type="text" name="name" required placeholder="e.g., Australia, Germany">
        </div>

        <div>
            <label>Minimum CGPA</label>
            <input type="number" step="0.01" name="min_cgpa" value="3.0">
        </div>

        <div>
            <label>Minimum IELTS</label>
            <input type="number" step="0.1" name="min_ielts" value="6.5">
        </div>

        <div>
            <label>Estimated Cost (Min)</label>
            <input type="number" name="estimated_cost_min" placeholder="e.g., 25000">
        </div>

        <div>
            <label>Estimated Cost (Max)</label>
            <input type="number" name="estimated_cost_max" placeholder="e.g., 35000">
        </div>

        <div class="full-width">
            <label>Application Deadline</label>
            <input type="text" name="application_deadline" placeholder="e.g., November 30 for February intake">
        </div>

        <div class="full-width">
            <label>Requirements</label>
            <textarea name="requirements_text" rows="3" placeholder="List requirements..."></textarea>
        </div>

        <div class="full-width">
            <button type="submit" class="btn-primary">Add Country</button>
        </div>

    </form>
</div>

<!-- Existing Countries -->
<div class="glass-card mt-3">
    <h2>Existing Countries</h2>

    <?php if ($countries): ?>
        <ul style="margin-left: 20px;">
            <?php foreach ($countries as $c): ?>
                <li>
                    <strong><?= htmlspecialchars($c['name']) ?></strong>
                    – Min CGPA: <?= number_format($c['min_cgpa'], 2) ?>,
                    Min IELTS: <?= number_format($c['min_ielts'], 1) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No countries found.</p>
    <?php endif; ?>

</div>

<?php include 'admin_footer.php'; ?>
