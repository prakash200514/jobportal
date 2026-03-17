<?php
require_once 'config.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$pdo = getDBConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    $website = $_POST['website'];
    
    try {
        $sql = "INSERT INTO companies (name, location, description, website) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $location, $description, $website]);
        $success_message = "Company added successfully!";
    } catch (Exception $e) {
        $error_message = "Error adding company: " . $e->getMessage();
    }
}

// Get all companies
$stmt = $pdo->query("SELECT * FROM companies ORDER BY name ASC");
$companies = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Companies - JobHunt</title>
    <link
      href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="styles.css" />
    <style>
        .manage-companies-container {
            max-width: 1000px;
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
        
        .add-company-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .companies-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .section-header {
            background: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
        }
        
        .section-header h2 {
            margin: 0;
        }
        
        .company-item {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--extra-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .company-item:last-child {
            border-bottom: none;
        }
        
        .company-info h3 {
            margin: 0 0 0.5rem 0;
            color: var(--text-dark);
        }
        
        .company-info p {
            margin: 0;
            color: var(--text-light);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--extra-light);
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-color-dark);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .company-item {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
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
            <li><a href="index.php">Home</a></li>
            <li><a href="add-job.php">Add Job</a></li>
            <li><a href="my-jobs.php">My Jobs</a></li>
            <li><a href="manage-companies.php">Companies</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="manage-companies-container">
        <div class="page-header">
            <h1><i class="ri-building-line"></i> Manage Companies</h1>
            <p>Add and manage company information</p>
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

        <!-- Add Company Form -->
        <div class="add-company-section">
            <h2><i class="ri-add-circle-line"></i> Add New Company</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Company Name *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="location">Location *</label>
                        <input type="text" id="location" name="location" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="website">Website</label>
                    <input type="url" id="website" name="website" placeholder="https://example.com">
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="ri-add-line"></i> Add Company
                </button>
            </form>
        </div>

        <!-- Companies List -->
        <div class="companies-list">
            <div class="section-header">
                <h2>All Companies (<?php echo count($companies); ?>)</h2>
            </div>
            
            <?php if (empty($companies)): ?>
                <div style="text-align: center; padding: 3rem; color: var(--text-light);">
                    <i class="ri-building-line" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                    <p>No companies found. Add your first company above.</p>
                </div>
            <?php else: ?>
                <?php foreach ($companies as $company): ?>
                    <div class="company-item">
                        <div class="company-info">
                            <h3><?php echo htmlspecialchars($company['name']); ?></h3>
                            <p><i class="ri-map-pin-2-line"></i> <?php echo htmlspecialchars($company['location']); ?></p>
                            <?php if ($company['description']): ?>
                                <p><?php echo htmlspecialchars($company['description']); ?></p>
                            <?php endif; ?>
                            <?php if ($company['website']): ?>
                                <p><i class="ri-global-line"></i> <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank"><?php echo htmlspecialchars($company['website']); ?></a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
    </script>
</body>
</html>
