<?php
require_once 'config.php';

$pdo = getDBConnection();

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 30;
$offset = ($page - 1) * $limit;

// Optional category filter by name
$categoryName = isset($_GET['category']) ? trim($_GET['category']) : '';

// Build base SQL
$baseSql = "FROM jobs j
            LEFT JOIN companies c ON j.company_id = c.id
            LEFT JOIN job_categories cat ON j.category_id = cat.id
            WHERE j.status = 'active'";
$params = [];

if ($categoryName !== '') {
	$baseSql .= " AND cat.name = ?";
	$params[] = $categoryName;
}

// Fetch jobs
$sql = "SELECT j.*, c.name as company_name, c.logo as company_logo, cat.name as category_name " . $baseSql . " ORDER BY j.created_at DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
foreach ($params as $idx => $val) {
	$stmt->bindValue($idx + 1, $val);
}
$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$jobs = $stmt->fetchAll();

// Count total
$countSql = "SELECT COUNT(*) " . $baseSql;
$countStmt = $pdo->prepare($countSql);
foreach ($params as $idx => $val) {
	$countStmt->bindValue($idx + 1, $val);
}
$countStmt->execute();
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $limit));
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Job Vacancies - JobHunt</title>
	<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />
	<link rel="stylesheet" href="styles.css" />
	<style>
		.jobs-page { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
		.page-header { text-align: center; margin-bottom: 1.5rem; }
		.job__grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1rem; }
		.pagination { display: flex; justify-content: center; gap: .5rem; margin-top: 1.5rem; }
		.pagination a, .pagination span { padding: .6rem .9rem; border-radius: 6px; border: 1px solid var(--extra-light); text-decoration: none; color: var(--text-dark); }
		.pagination .active { background: var(--primary-color); color: #fff; border-color: var(--primary-color); }
		.breadcrumb { text-align:center; color: var(--text-light); margin-bottom: .5rem; }
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
			<li><a href="jobs-list.php" class="btn">Jobs</a></li>
		</ul>
	</nav>

	<div class="jobs-page">
		<div class="page-header">
			<div class="breadcrumb">Home / Jobs<?php if ($categoryName !== '') echo ' / ' . htmlspecialchars($categoryName); ?></div>
			<h2 class="section__header"><?php echo $categoryName !== '' ? htmlspecialchars($categoryName) . ' Jobs' : 'Latest Job Vacancies'; ?></h2>
			<p class="section__description">Showing <?php echo count($jobs); ?> of <?php echo $total; ?> results</p>
		</div>

		<div class="job__grid">
			<?php foreach ($jobs as $job): ?>
			<div class="job__card" data-category="<?php echo htmlspecialchars($job['category_name']); ?>">
				<div class="job__card__header">
					<img src="<?php echo htmlspecialchars($job['company_logo']); ?>" alt="job" />
					<div>
						<h5><?php echo htmlspecialchars($job['company_name']); ?></h5>
						<h6><?php echo htmlspecialchars($job['location']); ?></h6>
					</div>
				</div>
				<h4><?php echo htmlspecialchars($job['title']); ?></h4>
				<p><?php echo htmlspecialchars(substr($job['description'], 0, 150)) . '...'; ?></p>
				<div class="job__card__footer">
					<span><?php echo (int)$job['positions_available']; ?> Positions</span>
					<span><?php echo htmlspecialchars($job['job_type']); ?></span>
					<span>
						<?php if ($job['salary_min'] && $job['salary_max']): ?>
							$<?php echo number_format($job['salary_min']); ?>-<?php echo number_format($job['salary_max']); ?>/Year
						<?php else: ?>
							Salary Not Specified
						<?php endif; ?>
					</span>
				</div>
				<div class="job__card__actions">
					<button class="btn btn--small" onclick="goToJob(<?php echo (int)$job['id']; ?>)">View Details</button>
					<button class="btn btn--outline" onclick="goToJob(<?php echo (int)$job['id']; ?>)">Apply Now</button>
				</div>
			</div>
			<?php endforeach; ?>
		</div>

		<div class="pagination">
			<?php if ($page > 1): ?>
				<a href="?page=<?php echo $page - 1; ?><?php echo $categoryName !== '' ? '&category=' . urlencode($categoryName) : ''; ?>">Prev</a>
			<?php endif; ?>
			<?php for ($p = 1; $p <= $pages; $p++): ?>
				<?php if ($p === $page): ?>
					<span class="active"><?php echo $p; ?></span>
				<?php else: ?>
					<a href="?page=<?php echo $p; ?><?php echo $categoryName !== '' ? '&category=' . urlencode($categoryName) : ''; ?>"><?php echo $p; ?></a>
				<?php endif; ?>
			<?php endfor; ?>
			<?php if ($page < $pages): ?>
				<a href="?page=<?php echo $page + 1; ?><?php echo $categoryName !== '' ? '&category=' . urlencode($categoryName) : ''; ?>">Next</a>
			<?php endif; ?>
		</div>
	</div>

	<script src="main-db.js"></script>
</body>
</html>


