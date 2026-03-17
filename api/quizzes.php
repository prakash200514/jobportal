<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

require_once '../config.php';
session_start();

try {
    $pdo = getDBConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
        if ($jobId <= 0) { http_response_code(400); echo json_encode(['error' => 'Invalid job']); exit(); }
        $stmt = $pdo->prepare('SELECT id, question, option_a, option_b, option_c, option_d FROM job_quiz_questions WHERE job_id = ? ORDER BY id ASC');
        $stmt->execute([$jobId]);
        $rows = $stmt->fetchAll();
        if (!$rows) {
            // Return small demo quiz if none
            $rows = [];
            for ($i=1;$i<=10;$i++) {
                $rows[] = [
                    'id' => $i,
                    'question' => "Question $i: Select the correct option.",
                    'option_a' => 'Option A',
                    'option_b' => 'Option B',
                    'option_c' => 'Option C',
                    'option_d' => 'Option D',
                ];
            }
        }
        echo json_encode(['questions' => $rows]);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $jobId = isset($input['job_id']) ? (int)$input['job_id'] : 0;
        $answers = isset($input['answers']) && is_array($input['answers']) ? $input['answers'] : [];
        if ($jobId <= 0 || empty($answers)) { http_response_code(400); echo json_encode(['error' => 'Invalid payload']); exit(); }

        // Load correct answers
        $stmt = $pdo->prepare('SELECT id, correct_option FROM job_quiz_questions WHERE job_id = ? ORDER BY id ASC');
        $stmt->execute([$jobId]);
        $rows = $stmt->fetchAll();

        $questionsCount = count($rows);
        if ($questionsCount === 0) {
            // Demo all A
            $questionsCount = count($answers);
            $correct = 0;
            foreach ($answers as $ans) { if (strtoupper($ans) === 'A') { $correct++; } }
        } else {
            $correctMap = [];
            foreach ($rows as $r) { $correctMap[$r['id']] = strtoupper($r['correct_option']); }
            $correct = 0;
            foreach ($answers as $qid => $ans) {
                if (isset($correctMap[$qid]) && strtoupper($ans) === $correctMap[$qid]) { $correct++; }
            }
        }

        $score = $questionsCount > 0 ? round(($correct / $questionsCount) * 100) : 0;
        $passed = $score >= 70;
        if ($passed) {
            if (!isset($_SESSION['quiz_pass'])) { $_SESSION['quiz_pass'] = []; }
            $_SESSION['quiz_pass'][$jobId] = true;
        }
        echo json_encode(['score' => $score, 'passed' => $passed]);
        exit();
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>


