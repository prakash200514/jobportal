<?php
require_once 'config.php';

session_start();

// Require login and job_seeker role
if (!isset($_SESSION['user_id'])) {
	header('Location: index.php');
	exit();
}

$pdo = getDBConnection();

$stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['user_type'] !== 'job_seeker') {
	header('Location: index.php');
	exit();
}

// Validate job id
$jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
if ($jobId <= 0) {
	header('Location: index.php');
	exit();
}

// Ensure job exists and is active
$stmt = $pdo->prepare("SELECT j.id, j.title, c.name AS company_name FROM jobs j LEFT JOIN companies c ON j.company_id = c.id WHERE j.id = ? AND j.status = 'active'");
$stmt->execute([$jobId]);
$job = $stmt->fetch();
if (!$job) {
	header('Location: index.php');
	exit();
}

// If already passed recently, skip quiz
if (isset($_SESSION['quiz_pass']) && isset($_SESSION['quiz_pass'][$jobId]) && $_SESSION['quiz_pass'][$jobId] === true) {
	header('Location: apply.php?job_id=' . $jobId);
	exit();
}

// Load quiz questions for this job (fallback to generated if none)
$stmt = $pdo->prepare("SELECT id, question, option_a, option_b, option_c, option_d, correct_option FROM job_quiz_questions WHERE job_id = ? ORDER BY id ASC");
$stmt->execute([$jobId]);
$dbQuestions = $stmt->fetchAll();

$questions = [];
if ($dbQuestions) {
    $i = 0;
    foreach ($dbQuestions as $row) {
        $i++;
        $questions[] = [
            'id' => (int)$row['id'],
            'question' => $row['question'],
            'options' => [
                'A' => $row['option_a'],
                'B' => $row['option_b'],
                'C' => $row['option_c'],
                'D' => $row['option_d']
            ],
            'answer' => strtoupper($row['correct_option'])
        ];
    }
} else {
    // Fallback demo questions if none configured
    for ($i = 1; $i <= 10; $i++) {
        $questions[] = [
            'id' => $i,
            'question' => "Question $i: Select the correct option.",
            'options' => [
                'A' => 'Option A',
                'B' => 'Option B',
                'C' => 'Option C',
                'D' => 'Option D'
            ],
            'answer' => 'A'
        ];
    }
}

$error = null;
$score = null;
$passed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$userAnswers = [];
	$correct = 0;
	foreach ($questions as $q) {
		$field = 'q_' . $q['id'];
		$ans = isset($_POST[$field]) ? $_POST[$field] : null;
		$userAnswers[$q['id']] = $ans;
		if ($ans !== null && strtoupper($ans) === $q['answer']) {
			$correct++;
		}
	}

	$score = round(($correct / count($questions)) * 100);
	$passed = $score >= 70; // Pass threshold 70%

	if ($passed) {
		if (!isset($_SESSION['quiz_pass'])) {
			$_SESSION['quiz_pass'] = [];
		}
		$_SESSION['quiz_pass'][$jobId] = true;
		header('Location: apply.php?job_id=' . $jobId);
		exit();
	} else {
		$error = 'You scored ' . $score . '%. Minimum 70% required. Please try again.';
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Pre-Application Quiz - JobHunt</title>
	<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />
	<link rel="stylesheet" href="styles.css" />
	<style>
		.quiz-container { max-width: 900px; margin: 2rem auto; background: #fff; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 2rem; }
		.quiz-header { border-bottom: 2px solid var(--extra-light); padding-bottom: 1rem; margin-bottom: 1.5rem; }
		.quiz-question { margin-bottom: 1rem; padding: 1rem; border: 1px solid var(--extra-light); border-radius: 6px; }
		.quiz-question h4 { margin: 0 0 0.75rem 0; color: var(--text-dark); }
		.quiz-options label { display: block; margin-bottom: 0.35rem; cursor: pointer; }
		.quiz-actions { margin-top: 1.5rem; text-align: center; }
		.alert { padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
		.alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
		.score { text-align: center; color: var(--text-light); margin-bottom: 1rem; }
	</style>
</head>
<body>
	<nav>
		<div class="nav__header">
			<div class="nav__logo">
				<a href="index.php" class="logo">Job<span>Hunt</span></a>
			</div>
			<div class="nav__menu__btn" id="menu-btn">
				<i class="ri-menu-line"></i>
			</div>
		</div>
		<ul class="nav__links" id="nav-links">
			<li><a href="index.php#home">Home</a></li>
			<li><a href="index.php#job">Jobs</a></li>
			<li><a href="logout.php">Logout</a></li>
		</ul>
	</nav>

	<div class="quiz-container">
		<div class="quiz-header">
			<h2><i class="ri-questionnaire-line"></i> Pre-Application Quiz</h2>
			<p>Job: <strong><?php echo htmlspecialchars($job['title']); ?></strong> at <strong><?php echo htmlspecialchars($job['company_name']); ?></strong></p>
			<p class="section__description">Answer 30 questions. You must score at least 70% to proceed to the application form.</p>
		</div>

		<?php if ($error): ?>
			<div class="alert alert-error"><i class="ri-error-warning-line"></i> <?php echo htmlspecialchars($error); ?></div>
			<?php if ($score !== null): ?><div class="score">Your score: <?php echo $score; ?>%</div><?php endif; ?>
		<?php endif; ?>

		<form method="POST">
			<?php foreach ($questions as $q): ?>
				<div class="quiz-question">
					<h4><?php echo htmlspecialchars($q['question']); ?></h4>
					<div class="quiz-options">
						<?php foreach ($q['options'] as $key => $label): ?>
							<label>
								<input type="radio" name="q_<?php echo $q['id']; ?>" value="<?php echo $key; ?>"> <?php echo htmlspecialchars($key . '. ' . $label); ?>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
			<div class="quiz-actions">
				<button type="submit" class="btn">Submit Quiz</button>
			</div>
		</form>
	</div>

	<script>
		const menuBtn = document.getElementById("menu-btn");
		const navLinks = document.getElementById("nav-links");
		const menuBtnIcon = menuBtn.querySelector("i");
		menuBtn.addEventListener("click", () => {
			navLinks.classList.toggle("open");
			const isOpen = navLinks.classList.contains("open");
			menuBtnIcon.setAttribute("class", isOpen ? "ri-close-line" : "ri-menu-line");
		});
		navLinks.addEventListener("click", () => {
			navLinks.classList.remove("open");
			menuBtnIcon.setAttribute("class", "ri-menu-line");
		});
	</script>
</body>
</html>


