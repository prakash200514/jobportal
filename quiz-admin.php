<?php
require_once 'config.php';
session_start();

// Only employers
if (!isset($_SESSION['user_id'])) { http_response_code(401); exit('Unauthorized'); }

$pdo = getDBConnection();

// Handle create/update/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare('DELETE FROM job_quiz_questions WHERE id = ?');
            $stmt->execute([$id]);
        }
        exit('OK');
    }

    $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
    $jobId = (int)($_POST['job_id'] ?? 0);
    $question = trim($_POST['question'] ?? '');
    $option_a = trim($_POST['option_a'] ?? '');
    $option_b = trim($_POST['option_b'] ?? '');
    $option_c = trim($_POST['option_c'] ?? '');
    $option_d = trim($_POST['option_d'] ?? '');
    $correct = strtoupper(trim($_POST['correct_option'] ?? 'A'));

    if ($jobId <= 0 || $question === '' || $option_a === '' || $option_b === '' || $option_c === '' || $option_d === '' || !in_array($correct, ['A','B','C','D'])) {
        http_response_code(400);
        exit('Invalid data');
    }

    if ($id) {
        $stmt = $pdo->prepare('UPDATE job_quiz_questions SET question=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_option=? WHERE id=?');
        $stmt->execute([$question, $option_a, $option_b, $option_c, $option_d, $correct, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO job_quiz_questions (job_id, question, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$jobId, $question, $option_a, $option_b, $option_c, $option_d, $correct]);
    }
    exit('OK');
}

// Render table
$jobId = (int)($_GET['job_id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM job_quiz_questions WHERE job_id = ? ORDER BY id ASC');
$stmt->execute([$jobId]);
$rows = $stmt->fetchAll();

if (!$rows) {
    echo '<p class="section__description">No questions added yet.</p>';
    exit;
}

echo '<table><thead><tr><th>#</th><th>Question</th><th>Options</th><th>Answer</th><th>Actions</th></tr></thead><tbody>';
foreach ($rows as $i => $r) {
    $opts = 'A) '.htmlspecialchars($r['option_a']).'<br>B) '.htmlspecialchars($r['option_b']).'<br>C) '.htmlspecialchars($r['option_c']).'<br>D) '.htmlspecialchars($r['option_d']);
    echo '<tr>';
    echo '<td>'.($i+1).'</td>';
    echo '<td>'.htmlspecialchars($r['question']).'</td>';
    echo '<td>'.$opts.'</td>';
    echo '<td>'.htmlspecialchars($r['correct_option']).'</td>';
    echo '<td class="quiz-actions">'
        .'<button class="btn--small btn" onclick="openQuestionForm('.htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8').')">Edit</button>'
        .'<button class="btn--small btn btn--outline" onclick="deleteQuestion('.(int)$r['id'].')">Delete</button>'
        .'</td>';
    echo '</tr>';
}
echo '</tbody></table>';
?>


