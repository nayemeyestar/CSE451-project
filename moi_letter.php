<?php
// FILE: moi_letter.php
require_once 'auth_check.php';

$page_title = "MOI Letter Generator";

$userId = $_SESSION['user_id'];

// Default values from user profile (if you want to pre-fill)
$stmt = $pdo->prepare("SELECT full_name, cgpa_current FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submit
$generatedLetter = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_moi'])) {
    $studentName    = trim($_POST['student_name'] ?? $user['full_name']);
    $studentId      = trim($_POST['student_id'] ?? '');
    $program        = trim($_POST['program'] ?? '');
    $department     = trim($_POST['department'] ?? '');
    $universityName = trim($_POST['university_name'] ?? '');
    $duration       = trim($_POST['study_duration'] ?? '');
    $cgpa           = trim($_POST['cgpa'] ?? $user['cgpa_current']);
    $passingYear    = trim($_POST['passing_year'] ?? '');
    $targetCountry  = trim($_POST['target_country'] ?? '');
    $targetUni      = trim($_POST['target_university'] ?? '');
    $language       = trim($_POST['language'] ?? 'English');
    $extraNotes     = trim($_POST['extra_notes'] ?? '');

    $today = date('F d, Y');

    $generatedLetter = "To Whom It May Concern,\n\n";
    $generatedLetter .= "This is to certify that {$studentName}";
    if ($studentId !== '') {
        $generatedLetter .= " (ID: {$studentId})";
    }
    $generatedLetter .= " was a student of the {$department} department, enrolled in the {$program} program at {$universityName}.\n\n";

    if ($duration !== '') {
        $generatedLetter .= "{$studentName} studied at this institution from {$duration}. ";
    }

    if ($cgpa !== '') {
        $generatedLetter .= "During this period, {$studentName} maintained a CGPA of {$cgpa} on a 4.00 scale. ";
    }

    $generatedLetter .= "\n\nWe hereby confirm that the entire program curriculum and examinations were conducted in {$language}. Therefore, this letter serves as a formal **Medium of Instruction** (MOI) certificate.\n\n";

    if ($targetCountry !== '' || $targetUni !== '') {
        $generatedLetter .= "This certificate has been issued upon the request of the student for submission to ";
        if ($targetUni !== '') {
            $generatedLetter .= "{$targetUni}";
            if ($targetCountry !== '') $generatedLetter .= ", ";
        }
        if ($targetCountry !== '') {
            $generatedLetter .= "{$targetCountry}";
        }
        $generatedLetter .= ".\n\n";
    }

    if ($extraNotes !== '') {
        $generatedLetter .= $extraNotes . "\n\n";
    }

    $generatedLetter .= "We wish {$studentName} every success in future academic and professional endeavors.\n\n";
    $generatedLetter .= "Sincerely,\n";
    $generatedLetter .= "______________________________\n";
    $generatedLetter .= "Head of Department / Registrar\n";
    $generatedLetter .= "{$universityName}\n";
    $generatedLetter .= "Date: {$today}\n";
}

include 'header.php';
include 'sidebar.php';
?>

<div class="page-title">
    <h1>MOI Letter Generator</h1>
    <p>Generate a professional Medium of Instruction letter based on your details.</p>
</div>

<div class="grid grid-2">
    <!-- Form -->
    <div class="glass-card">
        <h2>Student & Program Details</h2>
        <form method="post" class="form-vertical">
            <label>Student Name</label>
            <input type="text" name="student_name"
                   value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                   placeholder="Your full name" required>

            <label>Student ID</label>
            <input type="text" name="student_id" placeholder="e.g., 2018-1-60-001">

            <label>Program</label>
            <input type="text" name="program" placeholder="e.g., BSc in Computer Science & Engineering" required>

            <label>Department</label>
            <input type="text" name="department" placeholder="e.g., Department of CSE" required>

            <label>University Name</label>
            <input type="text" name="university_name" placeholder="Your university name" required>

            <label>Study Duration</label>
            <input type="text" name="study_duration" placeholder="e.g., September 2018 to August 2022">

            <div class="grid grid-2 compact-grid">
                <div>
                    <label>CGPA (on 4.00)</label>
                    <input type="number" name="cgpa" step="0.01" min="0" max="4"
                           value="<?= htmlspecialchars($user['cgpa_current'] ?? '') ?>"
                           placeholder="e.g., 3.50">
                </div>
                <div>
                    <label>Passing Year</label>
                    <input type="text" name="passing_year" placeholder="e.g., 2022">
                </div>
            </div>

            <div class="grid grid-2 compact-grid">
                <div>
                    <label>Target Country (Optional)</label>
                    <input type="text" name="target_country" placeholder="e.g., Germany">
                </div>
                <div>
                    <label>Target University (Optional)</label>
                    <input type="text" name="target_university" placeholder="e.g., TU Berlin">
                </div>
            </div>

            <label>Medium of Instruction Language</label>
            <input type="text" name="language" value="English">

            <label>Additional Notes (Optional)</label>
            <textarea name="extra_notes" rows="3"
                      placeholder="Any extra statement you want to add (e.g., class position, behavior, etc.)"></textarea>

            <button type="submit" name="generate_moi" class="btn-primary full-width mt-2">
                Generate MOI Letter
            </button>
        </form>
    </div>

    <!-- Preview -->
    <div class="glass-card">
        <h2>Preview & Copy</h2>
        <p class="small-text">
            After generating, you can copy the text or print it on your university letterhead.
        </p>

        <textarea id="moiLetterText" class="letter-preview" rows="20"
                  placeholder="Your generated MOI letter will appear here..."><?= htmlspecialchars($generatedLetter) ?></textarea>

        <div class="mt-2 flex-row">
            <button type="button" class="btn-secondary" onclick="copyMoiLetter()">
                Copy to Clipboard
            </button>
            <button type="button" class="btn-secondary" onclick="printMoiLetter()">
                Print
            </button>
        </div>
    </div>
</div>

<script>
    function copyMoiLetter() {
        const textarea = document.getElementById('moiLetterText');
        textarea.select();
        textarea.setSelectionRange(0, 99999);
        document.execCommand('copy');
        alert('MOI letter copied to clipboard!');
    }

    function printMoiLetter() {
        const content = document.getElementById('moiLetterText').value;
        const win = window.open('', '', 'height=600,width=800');
        win.document.write('<html><head><title>MOI Letter</title>');
        win.document.write('<style>body{font-family:Arial, sans-serif; margin:40px; white-space:pre-wrap;}</style>');
        win.document.write('</head><body>');
        win.document.write(content.replace(/\n/g, '<br>'));
        win.document.write('</body></html>');
        win.document.close();
        win.print();
    }
</script>

<style>
    .letter-preview {
        width: 100%;
        padding: 12px;
        border-radius: 10px;
        border: none;
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(12px);
        color: #111827;
        font-family: "Courier New", monospace;
        font-size: 14px;
        resize: vertical;
        box-shadow: 0 0 0 1px rgba(255,255,255,0.1);
    }
    .letter-preview:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(59,130,246,0.7);
    }
    .compact-grid > div { margin-bottom: 10px; }
    .flex-row {
        display: flex;
        gap: 10px;
        justify-content: flex-start;
        flex-wrap: wrap;
    }
    .small-text { font-size: 13px; opacity: 0.8; margin-bottom: 8px; }
</style>

<?php include 'footer.php'; ?>
