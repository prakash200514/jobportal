<?php
require_once 'config.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$pdo = getDBConnection();

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Only employers can access this page
if (!$user || $user['user_type'] !== 'employer') {
    header('Location: index.php');
    exit();
}

// Get user's jobs
$stmt = $pdo->prepare("SELECT j.*, c.name as company_name, c.logo as company_logo,
                              cat.name as category_name,
                              COUNT(ja.id) as application_count
                       FROM jobs j
                       LEFT JOIN companies c ON j.company_id = c.id
                       LEFT JOIN job_categories cat ON j.category_id = cat.id
                       LEFT JOIN job_applications ja ON j.id = ja.job_id
                       WHERE j.posted_by = ?
                       GROUP BY j.id
                       ORDER BY j.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$jobs = $stmt->fetchAll();

// Handle job status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $jobId = $_POST['job_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'close') {
            $stmt = $pdo->prepare("UPDATE jobs SET status = 'closed' WHERE id = ? AND posted_by = ?");
            $stmt->execute([$jobId, $_SESSION['user_id']]);
            $success_message = "Job closed successfully!";
        } elseif ($action === 'activate') {
            $stmt = $pdo->prepare("UPDATE jobs SET status = 'active' WHERE id = ? AND posted_by = ?");
            $stmt->execute([$jobId, $_SESSION['user_id']]);
            $success_message = "Job activated successfully!";
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ? AND posted_by = ?");
            $stmt->execute([$jobId, $_SESSION['user_id']]);
            $success_message = "Job deleted successfully!";
        }
        
        // Refresh the page to show updated data
        header('Location: my-jobs.php');
        exit();
        
    } catch (Exception $e) {
        $error_message = "Error updating job: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Jobs - JobHunt</title>
    <link
      href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="styles.css" />
    <style>
        .my-jobs-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .page-header h1 {
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            color: var(--text-light);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            color: var(--primary-color);
            margin: 0 0 0.5rem 0;
        }
        
        .stat-card p {
            color: var(--text-light);
            margin: 0;
        }
        
        .jobs-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .section-header {
            background: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-header h2 {
            margin: 0;
        }
        
        .add-job-btn {
            background: white;
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .add-job-btn:hover {
            background: var(--extra-light);
        }
        
        .jobs-list {
            padding: 0;
        }
        
        .job-item {
            border-bottom: 1px solid var(--extra-light);
            padding: 1.5rem 2rem;
            transition: background-color 0.3s ease;
        }
        
        .job-item:hover {
            background-color: #f8f9fa;
        }
        
        .job-item:last-child {
            border-bottom: none;
        }
        
        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .job-info h3 {
            margin: 0 0 0.5rem 0;
            color: var(--text-dark);
        }
        
        .job-info p {
            margin: 0;
            color: var(--text-light);
        }
        
        .job-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-closed {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-inactive {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .job-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
        }
        
        .detail-item i {
            color: var(--primary-color);
        }
        
        .job-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .btn-view {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-view:hover {
            background-color: var(--primary-color-dark);
        }
        
        .btn-close {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-close:hover {
            background-color: #c82333;
        }
        
        .btn-activate {
            background-color: #28a745;
            color: white;
        }
        
        .btn-activate:hover {
            background-color: #218838;
        }
        
        .btn-delete {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #5a6268;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-light);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--extra-light);
            margin-bottom: 1rem;
        }
        
        /* Employer Navigation Styles */
        .employer-nav {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav__container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 2rem;
        }
        
        .nav__brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .nav__badge {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .nav__links {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 0.5rem;
            position: static; /* override global */
            transform: none;  /* override global */
            z-index: auto;    /* override global */
        }
        
        .nav__link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: var(--text-dark);
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            pointer-events: auto; /* ensure clickable */
            position: relative;   /* create stacking context */
            z-index: 1;
        }
        
        .nav__link:hover {
            background: var(--extra-light);
            color: var(--primary-color);
        }
        
        .nav__link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .nav__link--danger {
            color: #dc3545;
        }
        
        .nav__link--danger:hover {
            background: #f8d7da;
            color: #721c24;
        }
        
        .nav__menu__btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-dark);
        }
        
        @media (max-width: 768px) {
            .nav__container {
                padding: 1rem;
            }
            
            .nav__menu__btn {
                display: block;
            }
            
            .nav__links {
                position: fixed;
                top: 70px;
                left: -100%;
                width: 100%;
                height: calc(100vh - 70px);
                background: white;
                flex-direction: column;
                padding: 2rem;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                transition: left 0.3s ease;
            }
            
            .nav__links.open {
                left: 0;
            }
            
            .nav__link {
                padding: 1rem;
                border-radius: 8px;
                margin-bottom: 0.5rem;
            }
            
            .my-jobs-container {
                margin: 1rem;
                padding: 1rem;
            }
            
            .job-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .job-details {
                grid-template-columns: 1fr;
            }
            
            .job-actions {
                justify-content: center;
            }
            
            .section-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <nav class="employer-nav">
        <div class="nav__container">
            <div class="nav__brand">
                <a href="index.php" class="logo">Job<span>Hunt</span></a>
                <span class="nav__badge">Employer Dashboard</span>
            </div>
            <div class="nav__menu__btn" id="menu-btn">
                <i class="ri-menu-line"></i>
            </div>
            <ul class="nav__links" id="nav-links">
                <li><a href="index.php" class="nav__link"><i class="ri-home-line"></i> Home</a></li>
                <li><a href="jobs-list.php" class="nav__link"><i class="ri-briefcase-line"></i> Browse Jobs</a></li>
                <li><a href="add-job.php" class="nav__link"><i class="ri-add-circle-line"></i> Post Job</a></li>
                <li><a href="my-jobs.php" class="nav__link active"><i class="ri-file-list-line"></i> My Jobs</a></li>
                <li><a href="logout.php" class="nav__link nav__link--danger"><i class="ri-logout-box-line"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="my-jobs-container">
        <div class="page-header">
            <h1><i class="ri-briefcase-line"></i> My Job Postings</h1>
            <p>Manage your job postings and track applications</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="ri-check-circle-line"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="ri-error-warning-line"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo count($jobs); ?></h3>
                <p>Total Jobs</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($jobs, function($job) { return $job['status'] === 'active'; })); ?></h3>
                <p>Active Jobs</p>
            </div>
            <div class="stat-card">
                <h3><?php echo array_sum(array_column($jobs, 'application_count')); ?></h3>
                <p>Total Applications</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($jobs, function($job) { return $job['status'] === 'closed'; })); ?></h3>
                <p>Closed Jobs</p>
            </div>
        </div>

        <!-- Jobs List -->
        <div class="jobs-section">
            <div class="section-header">
                <h2>Your Job Postings</h2>
                <a href="add-job.php" class="add-job-btn">
                    <i class="ri-add-line"></i> Add New Job
                </a>
            </div>
            
            <div class="jobs-list">
                <?php if (empty($jobs)): ?>
                    <div class="empty-state">
                        <i class="ri-briefcase-line"></i>
                        <h3>No Jobs Posted Yet</h3>
                        <p>Start by posting your first job vacancy</p>
                        <a href="add-job.php" class="btn-primary" style="margin-top: 1rem; display: inline-block;">
                            <i class="ri-add-line"></i> Post Your First Job
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($jobs as $job): ?>
                        <div class="job-item">
                            <div class="job-header">
                                <div class="job-info">
                                    <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                                    <p><strong><?php echo htmlspecialchars($job['company_name']); ?></strong> • <?php echo htmlspecialchars($job['category_name']); ?></p>
                                </div>
                                <span class="job-status status-<?php echo $job['status']; ?>">
                                    <?php echo ucfirst($job['status']); ?>
                                </span>
                            </div>
                            
                            <div class="job-details">
                                <div class="detail-item">
                                    <i class="ri-map-pin-2-line"></i>
                                    <span><?php echo htmlspecialchars($job['location']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="ri-briefcase-line"></i>
                                    <span><?php echo htmlspecialchars($job['job_type']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <i class="ri-user-line"></i>
                                    <span><?php echo $job['positions_available']; ?> position(s)</span>
                                </div>
                                <div class="detail-item">
                                    <i class="ri-file-list-line"></i>
                                    <span><?php echo $job['application_count']; ?> application(s)</span>
                                </div>
                                <?php if ($job['salary_min'] && $job['salary_max']): ?>
                                <div class="detail-item">
                                    <i class="ri-money-dollar-circle-line"></i>
                                    <span>$<?php echo number_format($job['salary_min']); ?> - $<?php echo number_format($job['salary_max']); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="detail-item">
                                    <i class="ri-calendar-line"></i>
                                    <span>Posted <?php echo date('M j, Y', strtotime($job['created_at'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="job-actions">
                                <button class="btn-small btn-view" onclick="viewJob(<?php echo $job['id']; ?>)">
                                    <i class="ri-eye-line"></i> View
                                </button>
                                <a class="btn-small btn-view" href="view-applications.php?job_id=<?php echo $job['id']; ?>">
                                    <i class="ri-file-list-2-line"></i> View Applications
                                </a>
                                
                                <?php if ($job['status'] === 'active'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                        <input type="hidden" name="action" value="close">
                                        <button type="submit" class="btn-small btn-close" 
                                                onclick="return confirm('Are you sure you want to close this job?')">
                                            <i class="ri-close-line"></i> Close
                                        </button>
                                    </form>
                                <?php elseif ($job['status'] === 'closed'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                        <input type="hidden" name="action" value="activate">
                                        <button type="submit" class="btn-small btn-activate">
                                            <i class="ri-play-line"></i> Activate
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn-small btn-delete" 
                                            onclick="return confirm('Are you sure you want to delete this job? This action cannot be undone.')">
                                        <i class="ri-delete-bin-line"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Mobile menu functionality
        const menuBtn = document.getElementById("menu-btn");
        const navLinks = document.getElementById("nav-links");
        const menuBtnIcon = menuBtn.querySelector("i");

        menuBtn.addEventListener("click", (e) => {
            navLinks.classList.toggle("open");
            const isOpen = navLinks.classList.contains("open");
            menuBtnIcon.setAttribute("class", isOpen ? "ri-close-line" : "ri-menu-line");
        });

        navLinks.addEventListener("click", (e) => {
            navLinks.classList.remove("open");
            menuBtnIcon.setAttribute("class", "ri-menu-line");
        });

        function viewJob(jobId) {
            // Redirect to job details or open in modal
            window.open('index.php#job', '_blank');
        }
    </script>
</body>
</html>
