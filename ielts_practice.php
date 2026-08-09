<?php
// FILE: ielts_practice.php
require_once 'auth_check.php';

$page_title = "IELTS Practice Test";

// Available options
$skills = ['Reading','Listening','Writing','Speaking'];
$difficulties = ['Easy','Medium','Hard'];

$questions = [];
$selectedSkill = '';
$selectedDifficulty = '';
$score = null;
$total = null;
$noQuestions = false;

// Step 1: Start test – load questions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_test'])) {
    $selectedSkill = $_POST['skill'] ?? '';
    $selectedDifficulty = $_POST['difficulty'] ?? '';

    $stmt = $pdo->prepare("
        SELECT * FROM ielts_questions
        WHERE skill = ? AND difficulty = ?
        ORDER BY RAND()
        LIMIT 10
    ");
    $stmt->execute([$selectedSkill, $selectedDifficulty]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($questions && count($questions) > 0) {
        $_SESSION['ielts_questions']   = $questions;
        $_SESSION['ielts_skill']       = $selectedSkill;
        $_SESSION['ielts_difficulty']  = $selectedDifficulty;
    } else {
        $noQuestions = true;
        unset($_SESSION['ielts_questions'], $_SESSION['ielts_skill'], $_SESSION['ielts_difficulty']);
    }
}

// Step 2: Submit answers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_answers'])) {
    $userAnswers      = $_POST['answer'] ?? [];
    $storedQuestions  = $_SESSION['ielts_questions'] ?? [];
    $selectedSkill    = $_SESSION['ielts_skill'] ?? '';
    $selectedDifficulty = $_SESSION['ielts_difficulty'] ?? '';

    $correct = 0;
    $total   = count($storedQuestions);

    foreach ($storedQuestions as $q) {
        $qid = $q['id'];

        if (!isset($userAnswers[$qid])) {
            continue;
        }

        $given = trim($userAnswers[$qid]);              // e.g. "A" or typed text
        $correctRaw = trim((string)$q['correct_option']);

        // If this question has MCQ options
        if (!empty($q['option_a'])) {
            // Case 1: DB stores letter A/B/C/D
            if (in_array(strtoupper($correctRaw), ['A','B','C','D'], true)) {
                if (strtoupper($given) === strtoupper($correctRaw)) {
                    $correct++;
                }
            } else {
                // Case 2: DB stores full correct text
                $optionsMap = [
                    'A' => $q['option_a'],
                    'B' => $q['option_b'],
                    'C' => $q['option_c'],
                    'D' => $q['option_d'],
                ];

                // If form sends A/B/C/D, convert to text
                $givenText = $optionsMap[strtoupper($given)] ?? $given;

                if (strcasecmp(trim($givenText), $correctRaw) === 0) {
                    $correct++;
                }
            }
        } else {
            // Free text question (Writing/Speaking) – very simple exact check
            if ($correctRaw !== '' && strcasecmp($given, $correctRaw) === 0) {
                $correct++;
            }
        }
    }

    $score = $total > 0 ? round(($correct / $total) * 100) : 0;

    // Save result in DB
    if ($selectedSkill && $selectedDifficulty) {
        $stmt = $pdo->prepare("
            INSERT INTO ielts_results (user_id, skill, difficulty, score)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $selectedSkill, $selectedDifficulty, $score]);
    }

    // Clear session
    unset($_SESSION['ielts_questions'], $_SESSION['ielts_skill'], $_SESSION['ielts_difficulty']);
}

include 'header.php';
include 'sidebar.php';
?>

<div class="page-title">
    <h1>IELTS Practice</h1>
    <p>Take a quick mock test and see your score out of 100.</p>
</div>

<div class="glass-card">
    <?php if ($score !== null): ?>
        <h2>Your Result</h2>

        <!-- Score line with proper spacing -->
        <p class="score-display">
            You scored <strong><?= $score ?></strong> / 100
        </p>

        <!-- Feedback text -->
        <?php if ($score >= 75): ?>
            <p class="score-high">Excellent! You're performing at a strong IELTS level.</p>
        <?php elseif ($score >= 50): ?>
            <p class="score-medium">Good! Keep practicing to improve further.</p>
        <?php else: ?>
            <p class="score-low">Needs improvement. Try more practice tests.</p>
        <?php endif; ?>

        <a href="ielts_practice.php" class="btn-primary mt-2">Take Another Test</a>

    <?php elseif ($noQuestions): ?>
        <h2>No Questions Found</h2>
        <div class="alert warning">
            No questions are available for
            <strong><?= htmlspecialchars($selectedSkill) ?></strong> –
            <strong><?= htmlspecialchars($selectedDifficulty) ?></strong>.<br>
            Please choose another combination or insert questions into the
            <code>ielts_questions</code> table.
        </div>
        <a href="ielts_practice.php" class="btn-primary mt-2">Back</a>

    <?php elseif (!empty($questions)): ?>
        <h2><?= htmlspecialchars($selectedSkill) ?> Test (<?= htmlspecialchars($selectedDifficulty) ?>)</h2>

        <form method="post">
            <?php foreach ($questions as $index => $q): ?>
                <div class="question-block glass-card-light">
                    <h3>Q<?= $index + 1 ?>. <?= htmlspecialchars($q['question']) ?></h3>

                    <?php if ($q['option_a']): ?>
                        <div class="option-row">
                            <label>
                                <input type="radio" name="answer[<?= $q['id'] ?>]" value="A">
                                <?= htmlspecialchars($q['option_a']) ?>
                            </label>
                        </div>
                        <div class="option-row">
                            <label>
                                <input type="radio" name="answer[<?= $q['id'] ?>]" value="B">
                                <?= htmlspecialchars($q['option_b']) ?>
                            </label>
                        </div>
                        <div class="option-row">
                            <label>
                                <input type="radio" name="answer[<?= $q['id'] ?>]" value="C">
                                <?= htmlspecialchars($q['option_c']) ?>
                            </label>
                        </div>
                        <div class="option-row">
                            <label>
                                <input type="radio" name="answer[<?= $q['id'] ?>]" value="D">
                                <?= htmlspecialchars($q['option_d']) ?>
                            </label>
                        </div>
                    <?php else: ?>
                        <textarea name="answer[<?= $q['id'] ?>]" rows="3"
                                  placeholder="Type your answer here..."></textarea>
                        <small>(Writing/Speaking answers are evaluated manually later)</small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <button type="submit" name="submit_answers" class="btn-primary full-width">
                Submit Test
            </button>
        </form>

    <?php else: ?>
        <h2>Start IELTS Practice Test</h2>
        <form method="post" class="grid grid-2">
            <div>
                <label>Select Skill</label>
                <select name="skill" required>
                    <option value="">Choose...</option>
                    <?php foreach ($skills as $sk): ?>
                        <option value="<?= $sk ?>"><?= $sk ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Select Difficulty</label>
                <select name="difficulty" required>
                    <option value="">Choose...</option>
                    <?php foreach ($difficulties as $dif): ?>
                        <option value="<?= $dif ?>"><?= $dif ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="full-width">
                <button type="submit" name="start_test" class="btn-primary">
                    Start Test
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<style>
    .score-display {
        font-size: 20px;
        margin-bottom: 8px;
    }

    .score-high,
    .score-medium,
    .score-low {
        margin-top: 8px;
        margin-bottom: 20px;
        display: block;
    }

    .score-high { color: green; }
    .score-medium { color: orange; }
    .score-low { color: red; }

    .question-block {
        padding: 12px;
        margin-bottom: 15px;
        border-radius: 10px;
        background: rgba(255,255,255,0.12);
        backdrop-filter: blur(10px);
    }

    /* Fix IELTS radio button alignment */
    .option-row {
        display: flex;
        align-items: center;
        margin: 6px 0;
    }

    .option-row label {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 16px;
        cursor: pointer;
    }

    .option-row input[type="radio"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        margin: 0;
        position: relative;
        top: 0;
    }
</style>

<?php include 'footer.php'; ?>
