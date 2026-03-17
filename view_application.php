<?php
require_once 'config.php';
require_once 'pdf_generator.php';

session_start();

// Require authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$applicationId = isset($_GET['application_id']) ? (int)$_GET['application_id'] : 0;
if ($applicationId <= 0) {
    header('Location: index.php');
    exit();
}

$pdo = getDBConnection();

// Fetch application with job ownership
$stmt = $pdo->prepare("SELECT ja.user_id AS applicant_id, ja.job_id, j.posted_by AS employer_id
                       FROM job_applications ja
                       JOIN jobs j ON ja.job_id = j.id
                       WHERE ja.id = ?");
$stmt->execute([$applicationId]);
$app = $stmt->fetch();
if (!$app) {
    header('Location: index.php');
    exit();
}

// Current user info
$stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

$isApplicant = ((int)$app['applicant_id'] === (int)$_SESSION['user_id']);
$isEmployerOwner = ((int)$app['employer_id'] === (int)$_SESSION['user_id']);
$isAdmin = ($currentUser && $currentUser['user_type'] === 'admin');

if (!($isApplicant || $isEmployerOwner || $isAdmin)) {
    header('Location: index.php');
    exit();
}

$generator = new JobOfferPDFGenerator();
$pdfData = $generator->generateJobOfferPDF($applicationId);

// If applicant, render a small toolbar with Download button; then show content
if ($isApplicant) {
    echo '<div style="position:sticky;top:0;z-index:1000;background:#fff;border-bottom:1px solid #eee;padding:10px;display:flex;justify-content:flex-end;gap:8px;">
            <a href="download_pdf.php?id=' . htmlspecialchars($applicationId) . '" class="btn" style="background:#6a38c2;color:#fff;padding:8px 12px;border-radius:6px;text-decoration:none;">Download Offer Letter</a>
          </div>';
}

echo $pdfData['content'];
exit;
?>


