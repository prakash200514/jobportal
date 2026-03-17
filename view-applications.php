<?php
require_once 'config.php';
require_once 'pdf_generator.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$pdo = getDBConnection();

// Ensure current user is employer and owns the job
$stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user || $user['user_type'] !== 'employer') {
    header('Location: index.php');
    exit();
}

$jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
if ($jobId <= 0) {
    header('Location: my-jobs.php');
    exit();
}

// Check ownership
$stmt = $pdo->prepare("SELECT j.id, j.title FROM jobs j WHERE j.id = ? AND j.posted_by = ?");
$stmt->execute([$jobId, $_SESSION['user_id']]);
$job = $stmt->fetch();
if (!$job) {
    header('Location: my-jobs.php');
    exit();
}

// Fetch applications
$stmt = $pdo->prepare("SELECT ja.id, ja.applied_at, ja.status, ja.cover_letter,
                              u.first_name, u.last_name, u.email
                       FROM job_applications ja
                       JOIN users u ON ja.user_id = u.id
                       WHERE ja.job_id = ?");
$stmt->execute([$jobId]);
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications - JobHunt</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />
    <link rel="stylesheet" href="styles.css" />
    <style>
        .container { max-width: 1100px; margin: 2rem auto; padding: 1rem; }
        .header { display:flex; justify-content: space-between; align-items:center; margin-bottom:1rem; }
        table { width:100%; border-collapse: collapse; background:white; }
        th, td { padding: 12px 14px; border-bottom: 1px solid var(--extra-light); text-align:left; }
        th { background: var(--extra-light); }
        .actions a { margin-right: 8px; }
    </style>
    </head>
<body>
    <div class="container">
        <div class="header">
            <h2><i class="ri-file-user-line"></i> Applications for: <?php echo htmlspecialchars($job['title']); ?></h2>
            <a class="btn" href="my-jobs.php"><i class="ri-arrow-left-line"></i> Back to My Jobs</a>
        </div>
        <?php if (empty($applications)): ?>
            <p>No applications yet.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Email</th>
                        <th>Applied</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $a): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($a['first_name'] . ' ' . $a['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($a['email']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($a['applied_at'])); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($a['status'])); ?></td>
                        <td class="actions">
                            <a class="btn" target="_blank" href="view_application.php?application_id=<?php echo $a['id']; ?>">
                                <i class="ri-external-link-line"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>


