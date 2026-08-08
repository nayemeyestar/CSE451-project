<?php
// FILE: admin_scholarships.php
require_once 'admin_auth.php';

$page_title = "Manage Scholarships";

$message = "";
$error   = "";

/*
Your scholarships table (from screenshot) looks like:

id  | name | country | country_id | min_cgpa | min_ielts | stipend_per_month
    | description | requires_research_proposal | requires_financial_proof

We will ONLY use:
name, country, min_cgpa, min_ielts, stipend_per_month,
description, requires_research_proposal, requires_financial_proof
*/

// Handle Add Scholarship
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name        = trim($_POST['name'] ?? '');
    $country     = trim($_POST['country'] ?? '');
    $min_cgpa    = (float)($_POST['min_cgpa'] ?? 0);
    $min_ielts   = (float)($_POST['min_ielts'] ?? 0);
    $stipend     = (int)($_POST['stipend'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $requires_research  = isset($_POST['requires_research']) ? 1 : 0;
    $requires_financial = isset($_POST['requires_financial']) ? 1 : 0;

    if ($name === '' || $country === '') {
        $error = "Scholarship name and country are required.";
    } else {
        // Insert matching your actual table structure
        $stmt = $pdo->prepare("
            INSERT INTO scholarships
                (name, country, min_cgpa, min_ielts, stipend_per_month,
                 description, requires_research_proposal, requires_financial_proof)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $name,
            $country,
            $min_cgpa,
            $min_ielts,
            $stipend,
            $description,
            $requires_research,
            $requires_financial
        ]);

        $message = "Scholarship added successfully!";
    }
}

// Fetch Existing Scholarships
$schStmt = $pdo->query("SELECT * FROM scholarships ORDER BY id DESC");
$scholarships = $schStmt->fetchAll(PDO::FETCH_ASSOC);

include 'admin_header.php';
?>

<div class="page-title">
    <h1>Manage Scholarships</h1>
    <p>Add, edit, and manage scholarship opportunities.</p>
</div>

<div class="glass-card">

    <?php if ($message): ?>
        <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <h2>Add New Scholarship</h2>

    <form method="POST" class="grid grid-2">

        <div>
            <label>Scholarship Name *</label>
            <input type="text" name="name" placeholder="e.g., Chevening Scholarship" required>
        </div>

        <div>
            <label>Country *</label>
            <input type="text" name="country" placeholder="e.g., UK, Germany, Australia" required>
        </div>

        <div>
            <label>Minimum CGPA</label>
            <input type="number" step="0.01" name="min_cgpa" value="3.0">
        </div>

        <div>
            <label>Minimum IELTS Score</label>
            <input type="number" step="0.5" name="min_ielts" value="6.5">
        </div>

        <div>
            <label>Stipend Per Month</label>
            <input type="number" name="stipend" placeholder="e.g., 850">
        </div>

        <div class="full-width">
            <label>Description</label>
            <textarea name="description" rows="3"
                      placeholder="Short description of this scholarship..."></textarea>
        </div>

        <div>
            <label>
                <input type="checkbox" name="requires_research">
                Requires Research Proposal
            </label>
        </div>

        <div>
            <label>
                <input type="checkbox" name="requires_financial">
                Requires Financial Proof
            </label>
        </div>

        <div class="full-width">
            <button class="btn-primary" type="submit">Add Scholarship</button>
        </div>

    </form>
</div>

<div class="glass-card mt-3">
    <h2>Existing Scholarships</h2>

    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Country</th>
                    <th>Min CGPA</th>
                    <th>Min IELTS</th>
                    <th>Stipend</th>
                    <th>Research?</th>
                    <th>Financial?</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($scholarships): ?>
                <?php foreach ($scholarships as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['name']) ?></td>
                        <td><?= htmlspecialchars($s['country']) ?></td>
                        <td><?= htmlspecialchars($s['min_cgpa']) ?></td>
                        <td><?= htmlspecialchars($s['min_ielts']) ?></td>
                        <td><?= htmlspecialchars($s['stipend_per_month']) ?></td>

                        <td>
                            <span class="<?= $s['requires_research_proposal'] ? 'badge-yes' : 'badge-no' ?>">
                                <?= $s['requires_research_proposal'] ? 'Yes' : 'No' ?>
                            </span>
                        </td>

                        <td>
                            <span class="<?= $s['requires_financial_proof'] ? 'badge-yes' : 'badge-no' ?>">
                                <?= $s['requires_financial_proof'] ? 'Yes' : 'No' ?>
                            </span>
                        </td>

                        <td class="description-cell">
                            <?= htmlspecialchars($s['description']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="8">No scholarships added yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
