<?php
require_once 'config.php';
require_once 'pdf_generator.php';

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

// Enforce quiz pass gate
if (!isset($_SESSION['quiz_pass']) || !isset($_SESSION['quiz_pass'][$jobId]) || $_SESSION['quiz_pass'][$jobId] !== true) {
	header('Location: quiz.php?job_id=' . $jobId);
	exit();
}

// Ensure job exists and is active
$stmt = $pdo->prepare("SELECT j.id, j.title, j.location, j.job_type, c.name AS company_name, c.logo AS company_logo FROM jobs j LEFT JOIN companies c ON j.company_id = c.id WHERE j.id = ? AND j.status = 'active'");
$stmt->execute([$jobId]);
$job = $stmt->fetch();
if (!$job) {
	header('Location: index.php');
	exit();
}

$success_message = null;
$error_message = null;
$pdf_filename = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Quick, direct handling (no CURL round-trip)
        $coverLetter = trim($_POST['cover_letter'] ?? '');

        // Save resume locally if provided (optional)
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $allowedExt = ['pdf', 'doc', 'docx'];
            $ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExt)) {
                $uploadDir = __DIR__ . '/uploads/resumes';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $safeName = 'resume_u' . (int)$_SESSION['user_id'] . '_j' . (int)$jobId . '_' . time() . '.' . $ext;
                $destPath = $uploadDir . '/' . $safeName;
                @move_uploaded_file($_FILES['resume']['tmp_name'], $destPath);
            }
        }

        // Upsert behavior: if application exists for this job+user, update it instead of inserting
        $check = $pdo->prepare("SELECT id FROM job_applications WHERE job_id = ? AND user_id = ? LIMIT 1");
        $check->execute([$jobId, $_SESSION['user_id']]);
        $existing = $check->fetch();

        if ($existing) {
            $applicationId = (int)$existing['id'];
            $upd = $pdo->prepare("UPDATE job_applications SET cover_letter = COALESCE(?, cover_letter) WHERE id = ?");
            $upd->execute([$coverLetter !== '' ? $coverLetter : null, $applicationId]);
            $success_message = 'Application updated successfully!';
        } else {
            $stmt = $pdo->prepare("INSERT INTO job_applications (job_id, user_id, cover_letter) VALUES (?, ?, ?)");
            $stmt->execute([$jobId, $_SESSION['user_id'], $coverLetter !== '' ? $coverLetter : null]);
            $applicationId = $pdo->lastInsertId();
            $success_message = 'Application submitted successfully!';
        }
        unset($_SESSION['quiz_pass'][$jobId]);

        // Generate PDF summary
        if ($applicationId) {
            $pdfGenerator = new JobOfferPDFGenerator();
            $pdfData = $pdfGenerator->generateJobOfferPDF($applicationId);
            $pdf_filename = $pdfData['filename'];
            $pdfGenerator->savePDFToFile($pdfData['content'], $pdf_filename);
        }
    } catch (Exception $e) {
        $error_message = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Apply for Job - JobHunt</title>
	<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />
	<link rel="stylesheet" href="styles.css" />
	<style>
		.apply-container { max-width: 800px; margin: 2rem auto; background: #fff; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); padding: 2rem; }
		.apply-header { border-bottom: 2px solid var(--extra-light); padding-bottom: 1rem; margin-bottom: 1.5rem; }
		.alert { padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
		.alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
		.alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
		.company { display:flex; align-items:center; gap:12px; }
		.company img { width:40px; height:40px; object-fit:cover; border-radius:6px; }
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

	<div class="apply-container">
		<div class="apply-header">
			<h2><i class="ri-file-list-line"></i> Apply for <?php echo htmlspecialchars($job['title']); ?></h2>
			<div class="company">
				<?php if (!empty($job['company_logo'])): ?><img src="<?php echo htmlspecialchars($job['company_logo']); ?>" alt="logo"><?php endif; ?>
				<p><strong><?php echo htmlspecialchars($job['company_name']); ?></strong> • <?php echo htmlspecialchars($job['location']); ?> • <?php echo htmlspecialchars($job['job_type']); ?></p>
			</div>
		</div>

		<?php if ($success_message): ?>
			<div class="alert alert-success">
				<i class="ri-check-line"></i> <?php echo htmlspecialchars($success_message); ?>
				<?php if ($pdf_filename): ?>
					<br><br>
					<a href="view_application.php?application_id=<?php echo urlencode($applicationId); ?>" target="_blank" class="btn" style="margin-top: 10px;">
						<i class="ri-external-link-line"></i> View Application Summary
					</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
		<?php if ($error_message): ?>
			<div class="alert alert-error"><i class="ri-error-warning-line"></i> <?php echo htmlspecialchars($error_message); ?></div>
		<?php endif; ?>

		<form method="POST" enctype="multipart/form-data">
			<div class="form-group">
				<label for="resumeFile">Upload Resume</label>
				<input type="file" id="resumeFile" name="resume" accept=".pdf,.doc,.docx">
			</div>
			<div class="form-group">
				<label for="coverLetter">Cover Letter</label>
				<textarea id="coverLetter" name="cover_letter" rows="6" placeholder="Write a short cover letter..."></textarea>
			</div>
			<button type="submit" class="btn">Submit Application</button>
			<a href="index.php#job" class="btn btn--outline" style="margin-left:8px;">Cancel</a>
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


