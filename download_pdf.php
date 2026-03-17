<?php
require_once 'config.php';
require_once 'pdf_generator.php';

session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$applicationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($applicationId <= 0) {
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDBConnection();
    
    // Verify the application belongs to the current user
    $stmt = $pdo->prepare("SELECT ja.id FROM job_applications ja WHERE ja.id = ? AND ja.user_id = ?");
    $stmt->execute([$applicationId, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        header('Location: index.php');
        exit();
    }
    
    // Generate PDF
    $pdfGenerator = new JobOfferPDFGenerator();
    $pdfData = $pdfGenerator->generateJobOfferPDF($applicationId);
    
    // Output PDF for download
    $pdfGenerator->downloadPDF($pdfData['content'], $pdfData['filename']);
    
} catch (Exception $e) {
    header('Location: index.php');
    exit();
}
?>
