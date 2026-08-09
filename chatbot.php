<?php
// FILE: chatbot.php
require_once 'auth_check.php';

// This file acts as a simple AJAX API endpoint for the chatbot.
// It expects a JSON POST body: { "question": "..." }
// and returns: { "status": "ok", "answer": "..." }

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid request method. Use POST.'
    ]);
    exit;
}

// Read JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$question = trim($data['question'] ?? '');

if ($question === '') {
    echo json_encode([
        'status' => 'ok',
        'answer' => 'Please type a question so I can help you.'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

// Load user basic info (CGPA, IELTS)
$stmtUser = $pdo->prepare("SELECT full_name, cgpa_current, ielts_score FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

$fullName = $user['full_name'] ?? 'Student';
$cgpa     = (float)($user['cgpa_current'] ?? 0);
$ielts    = (float)($user['ielts_score'] ?? 0);

// Load English scores for eligibility
$stmtEng = $pdo->prepare("SELECT eng101, eng103, eng105 FROM english_scores WHERE user_id = ?");
$stmtEng->execute([$userId]);
$eng = $stmtEng->fetch(PDO::FETCH_ASSOC);

$eng101 = (float)($eng['eng101'] ?? 0);
$eng103 = (float)($eng['eng103'] ?? 0);
$eng105 = (float)($eng['eng105'] ?? 0);
$engAvg = ($eng101 + $eng103 + $eng105) ? (($eng101 + $eng103 + $eng105) / 3) : 0;

// Calculate same eligibility score as eligibility.php
$eligibilityScore = round(
    ($cgpa / 4.0) * 40 +        // CGPA contributes 40%
    ($engAvg / 100) * 40 +      // English course avg contributes 40%
    ($ielts / 9.0) * 20         // IELTS contributes 20%
);

// Simple rule-based FAQ engine
$q = strtolower($question);
$answer = "";

// Greeting / intro
if (preg_match('/\b(hi|hello|hey|assalam|salam)\b/', $q)) {
    $answer = "Hello {$fullName}! 👋 I’m your StudyTrack helper. "
        . "You can ask me about:\n"
        . "• Your eligibility score\n"
        . "• CGPA / IELTS requirements\n"
        . "• Scholarships & countries\n"
        . "• How to use each feature of this dashboard.";
}

// Eligibility-related
elseif (strpos($q, 'eligibility') !== false || strpos($q, 'eligible') !== false) {
    $answer = "Here’s your current eligibility snapshot:\n\n"
        . "• Eligibility Score: **{$eligibilityScore}/100**\n"
        . "• CGPA: " . number_format($cgpa, 2) . " / 4.00\n"
        . "• IELTS: " . number_format($ielts, 1) . " / 9.00\n"
        . "• English course average: " . number_format($engAvg, 2) . " / 100\n\n"
        . "You can improve this score by:\n"
        . "1) Increasing your CGPA (CGPA contributes 40%)\n"
        . "2) Performing better in ENG101/103/105 (another 40%)\n"
        . "3) Achieving a higher IELTS band (20%).";
}

// CGPA-related
elseif (strpos($q, 'cgpa') !== false) {
    $answer = "Your current CGPA is **" . number_format($cgpa, 2) . " / 4.00**.\n\n"
        . "Use the **CGPA Progress Tracker** from the sidebar to:\n"
        . "• Add each semester’s CGPA\n"
        . "• See a line chart of your progress\n"
        . "• Get a quick risk warning if your CGPA goes below a safe threshold (e.g., 3.00).";
}

// IELTS-related
elseif (strpos($q, 'ielts') !== false) {
    $answer = "Your saved IELTS score is **" . number_format($ielts, 1) . " / 9.0**.\n\n"
        . "To prepare for IELTS:\n"
        . "• Go to **IELTS Practice** from the sidebar\n"
        . "• Choose the skill (Reading/Listening/etc.) and difficulty\n"
        . "• Answer the questions and submit to see your score out of 100.\n\n"
        . "You can repeat tests to track improvement.";
}

// Scholarship-related
elseif (strpos($q, 'scholarship') !== false || strpos($q, 'funding') !== false) {
    // Count scholarships that are at least 50% match
    $stmtSch = $pdo->query("
        SELECT s.*, c.name AS country_name
        FROM scholarships s
        INNER JOIN countries c ON s.country_id = c.id
    ");
    $scholarships = $stmtSch->fetchAll(PDO::FETCH_ASSOC);

    $goodMatches = 0;
    foreach ($scholarships as $s) {
        $requirements = 0;
        $met = 0;

        $requirements++;
        if ($cgpa >= $s['min_cgpa']) $met++;

        $requirements++;
        if ($ielts >= $s['min_ielts']) $met++;

        $requirements++;
        if ($s['requires_research_proposal'] == 0) $met++;

        $requirements++;
        if ($s['requires_financial_proof'] == 0) $met++;

        if ($requirements > 0) {
            $matchPercent = ($met / $requirements) * 100;
            if ($matchPercent >= 50) {
                $goodMatches++;
            }
        }
    }

    $answer = "Based on your current profile:\n"
        . "• You have **{$goodMatches}** scholarships in the system that are at least 50% matched to you.\n\n"
        . "To see detailed matching:\n"
        . "• Open **Scholarship Matcher** from the sidebar.\n"
        . "There you’ll see:\n"
        . "• Match percentage for each scholarship\n"
        . "• Which requirements you already meet (CGPA, IELTS, research proposal, financial proof).";
}

// Country guidelines
elseif (strpos($q, 'country') !== false || strpos($q, 'guideline') !== false || strpos($q, 'requirements') !== false) {
    $answer = "You can explore **Country-Specific Guidelines** from the sidebar.\n\n"
        . "For each country you’ll see:\n"
        . "• Minimum CGPA & IELTS requirements\n"
        . "• Estimated yearly cost\n"
        . "• Application deadlines\n"
        . "• Detailed admission requirements\n\n"
        . "Your own CGPA and IELTS are automatically compared to show how close you are to the requirements.";
}

// MOI letter
elseif (strpos($q, 'moi') !== false || strpos($q, 'medium of instruction') !== false) {
    $answer = "To generate a **Medium of Instruction (MOI) letter**:\n"
        . "1) Click **MOI Letter Generator** in the sidebar.\n"
        . "2) Fill in your details (program, department, university, CGPA, etc.).\n"
        . "3) Click **Generate MOI Letter**.\n"
        . "4) Copy the text or print it on your university letterhead.\n\n"
        . "The letter clearly states that your program was conducted in English (or your chosen language).";
}

// How the app works in general
elseif (strpos($q, 'how to use') !== false || strpos($q, 'help') !== false || strpos($q, 'what can you do') !== false) {
    $answer = "Here’s what each feature in the sidebar does:\n\n"
        . "• **Dashboard** – Shows a summary: CGPA, eligibility score, and scholarship count.\n"
        . "• **CGPA Progress Tracker** – Enter semester-wise CGPA and see progress on a chart.\n"
        . "• **Country Guidelines** – See requirements, costs, and deadlines for each country plus your eligibility.\n"
        . "• **Scholarship Matcher** – See match percentage between your profile and each scholarship.\n"
        . "• **MOI Letter Generator** – Create a professional Medium of Instruction letter.\n"
        . "• **IELTS Practice** – Take short tests and get a score out of 100.\n"
        . "• **Eligibility Calculator** – Combine CGPA, IELTS, and English course scores into a 0-100 score.\n\n"
        . "Ask me about any one of these if you want more details.";
}

// Fallback generic answer
else {
    $answer = "I’m not fully sure about that specific question, but here’s what I can help with right now:\n\n"
        . "• Explain your eligibility score and how to improve it\n"
        . "• Give tips on CGPA and IELTS requirements for scholarships\n"
        . "• Guide you to the right section (CGPA Tracker, Scholarships, Countries, etc.)\n\n"
        . "Try asking things like:\n"
        . "• \"How can I improve my eligibility score?\"\n"
        . "• \"Do I qualify for scholarships?\"\n"
        . "• \"What are the requirements for Germany?\"";
}

// Save to chat_logs table
try {
    $stmtLog = $pdo->prepare("INSERT INTO chat_logs (user_id, question, answer) VALUES (?, ?, ?)");
    $stmtLog->execute([$userId, $question, $answer]);
} catch (Exception $e) {
    // Silently ignore logging errors to avoid breaking the chatbot
}

echo json_encode([
    'status' => 'ok',
    'answer' => nl2br($answer)  // small formatting help for HTML
]);
exit;
