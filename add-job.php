<?php
require_once 'config.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get user info
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Only employers can access this page
if (!$user || $user['user_type'] !== 'employer') {
    header('Location: index.php');
    exit();
}

// (Deprecated in UI) Company selection list kept for backward compatibility if needed
$stmt = $pdo->query("SELECT * FROM companies ORDER BY name ASC");
$allCompanies = $stmt->fetchAll();

// Get job categories
$stmt = $pdo->query("SELECT * FROM job_categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

// Success notice after redirect
if (isset($_GET['created']) && $_GET['created'] == '1' && isset($_GET['id'])) {
    $success_message = 'Job posted successfully! You can now add quiz questions.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = $_POST['title'];
        $description = $_POST['description'];
        // Company can be typed freely; create if not exists
        $company_name = trim($_POST['company_name'] ?? '');
        if ($company_name === '') { throw new Exception('Company name is required'); }
        // Find or create company
        $stmtFind = $pdo->prepare("SELECT id FROM companies WHERE name = ? LIMIT 1");
        $stmtFind->execute([$company_name]);
        $companyRow = $stmtFind->fetch();
        if ($companyRow) {
            $company_id = (int)$companyRow['id'];
        } else {
            $stmtIns = $pdo->prepare("INSERT INTO companies (name) VALUES (?)");
            $stmtIns->execute([$company_name]);
            $company_id = (int)$pdo->lastInsertId();
        }
        $category_id = $_POST['category_id'];
        $location = $_POST['location'];
        $job_type = $_POST['job_type'];
        $salary_min = $_POST['salary_min'] ?: null;
        $salary_max = $_POST['salary_max'] ?: null;
        $salary_currency = $_POST['salary_currency'];
        $positions_available = $_POST['positions_available'];
        $requirements = $_POST['requirements'];
        $benefits = $_POST['benefits'];
        
        // Insert job
        $sql = "INSERT INTO jobs (title, description, company_id, category_id, location, job_type, 
                                 salary_min, salary_max, salary_currency, positions_available, 
                                 requirements, benefits, posted_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $title, $description, $company_id, $category_id, $location, $job_type,
            $salary_min, $salary_max, $salary_currency, $positions_available,
            $requirements, $benefits, $_SESSION['user_id']
        ]);
        
        // New Job ID
        $newJobId = $pdo->lastInsertId();
        $success_message = "Job posted successfully!";
        
        // Update job count for category
        $stmt = $pdo->prepare("UPDATE job_categories SET job_count = job_count + 1 WHERE id = ?");
        $stmt->execute([$category_id]);
        
        // Redirect to enable quiz management tied to this job
        header('Location: add-job.php?id=' . $newJobId . '&created=1');
        exit();
        
    } catch (Exception $e) {
        $error_message = "Error posting job: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Job Vacancy - JobHunt</title>
    <link
      href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="styles.css" />
    <style>
        .add-job-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .add-job-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--extra-light);
        }
        
        .add-job-header h1 {
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }
        
        .add-job-header p {
            color: var(--text-light);
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section h3 {
            color: var(--text-dark);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--extra-light);
        }
        .quiz-admin table{width:100%;border-collapse:collapse}
        .quiz-admin th, .quiz-admin td{border:1px solid var(--extra-light);padding:8px;text-align:left}
        .quiz-admin th{background:#f7f7f7}
        .quiz-actions{display:flex;gap:.5rem;flex-wrap:wrap}
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--extra-light);
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .required {
            color: #e74c3c;
        }
        
        .btn-container {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-color-dark);
        }
        
        .btn-secondary {
            background-color: var(--extra-light);
            color: var(--text-dark);
            padding: 1rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary:hover {
            background-color: #d1d1d1;
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
        
        .user-info {
            background-color: var(--extra-light);
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
        }
        
        .user-info h4 {
            margin: 0 0 0.5rem 0;
            color: var(--text-dark);
        }
        
        .user-info p {
            margin: 0;
            color: var(--text-light);
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
            text-decoration: none;
            display: inline-block;
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
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .add-job-container {
                margin: 1rem;
                padding: 1rem;
            }
            
            .btn-container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="employer-nav" style="position: sticky; top: 0; z-index: 1000;">
        <div class="nav__container">
            <div class="nav__brand">
                <a href="index.php" class="logo">Job<span>Hunt</span></a>
                <a href="my-jobs.php" class="nav__badge">Employer Dashboard</a>
            </div>
            <div class="nav__menu__btn" id="menu-btn">
                <i class="ri-menu-line"></i>
            </div>
            <ul class="nav__links" id="nav-links">
                <li><a href="index.php" class="nav__link"><i class="ri-home-line"></i> Home</a></li>
                <li><a href="add-job.php" class="nav__link active"><i class="ri-add-circle-line"></i> Post Job</a></li>
                <li><a href="my-jobs.php" class="nav__link"><i class="ri-file-list-line"></i> My Jobs</a></li>
                <li><a href="logout.php" class="nav__link nav__link--danger"><i class="ri-logout-box-line"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="add-job-container">
        <div class="add-job-header">
            <h1><i class="ri-add-circle-line"></i> Add Job Vacancy</h1>
            <p>Post a new job opening and find the perfect candidate</p>
        </div>

        <div class="user-info">
            <h4>Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h4>
            <p>You are posting as: <strong><?php echo ucfirst($user['user_type']); ?></strong></p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="ri-check-circle-line"></i> <?php echo $success_message; ?>
                <div style="margin-top:.5rem">
                    <a class="btn-secondary" href="add-job.php"><i class="ri-add-line"></i> Post another job</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="ri-error-warning-line"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="add-job.php">
            <!-- Basic Information -->
            <div class="form-section">
                <h3><i class="ri-briefcase-line"></i> Job Information</h3>
                
                <div class="form-group">
                    <label for="title">Job Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required 
                           placeholder="e.g., Senior Software Developer">
                </div>

                <div class="form-group">
                    <label for="description">Job Description <span class="required">*</span></label>
                    <textarea id="description" name="description" required 
                              placeholder="Describe the role, responsibilities, and what you're looking for in a candidate..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="company_name">Company <span class="required">*</span></label>
                        <input type="text" id="company_name" name="company_name" required placeholder="Type company name">
                    </div>

                    <div class="form-group">
                        <label for="category_id">Job Category <span class="required">*</span></label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="location">Location <span class="required">*</span></label>
                        <input type="text" id="location" name="location" required 
                               placeholder="e.g., New York, NY">
                    </div>

                    <div class="form-group">
                        <label for="job_type">Job Type <span class="required">*</span></label>
                        <select id="job_type" name="job_type" required>
                            <option value="Full Time">Full Time</option>
                            <option value="Part Time">Part Time</option>
                            <option value="Contract">Contract</option>
                            <option value="Internship">Internship</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="positions_available">Number of Positions <span class="required">*</span></label>
                    <input type="number" id="positions_available" name="positions_available" 
                           min="1" value="1" required>
                </div>
            </div>

            <!-- Salary Information -->
            <div class="form-section">
                <h3><i class="ri-money-dollar-circle-line"></i> Salary Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="salary_min">Minimum Salary</label>
                        <input type="number" id="salary_min" name="salary_min" 
                               placeholder="e.g., 50000" min="0">
                    </div>

                    <div class="form-group">
                        <label for="salary_max">Maximum Salary</label>
                        <input type="number" id="salary_max" name="salary_max" 
                               placeholder="e.g., 80000" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label for="salary_currency">Currency</label>
                    <select id="salary_currency" name="salary_currency">
                        <option value="USD">USD ($)</option>
                        <option value="EUR">EUR (€)</option>
                        <option value="GBP">GBP (£)</option>
                        <option value="INR">INR (₹)</option>
                        <option value="CAD">CAD (C$)</option>
                    </select>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="form-section">
                <h3><i class="ri-file-text-line"></i> Additional Information</h3>
                
                <div class="form-group">
                    <label for="requirements">Requirements & Qualifications</label>
                    <textarea id="requirements" name="requirements" 
                              placeholder="List the required skills, experience, education, and qualifications..."></textarea>
                </div>

                <div class="form-group">
                    <label for="benefits">Benefits & Perks</label>
                    <textarea id="benefits" name="benefits" 
                              placeholder="List the benefits, perks, and what makes this job attractive..."></textarea>
                </div>
            </div>

            <div class="btn-container">
                <button type="submit" class="btn-primary">
                    <i class="ri-save-line"></i> Post Job
                </button>
                <a href="index.php" class="btn-secondary">
                    <i class="ri-arrow-left-line"></i> Cancel
                </a>
            </div>
        </form>

        <!-- Quiz Management -->
        <div class="form-section">
            <h3><i class="ri-questionnaire-line"></i> Quiz Questions</h3>
            <div class="quiz-admin" id="quiz-admin">
                <!-- Filled by JS -->
            </div>
            <div style="margin-top:1rem">
                <button class="btn-primary" type="button" id="addQuestionBtn"><i class="ri-add-line"></i> Add Question</button>
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

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const salaryMin = document.getElementById('salary_min').value;
            const salaryMax = document.getElementById('salary_max').value;
            
            if (salaryMin && salaryMax && parseInt(salaryMin) > parseInt(salaryMax)) {
                e.preventDefault();
                alert('Minimum salary cannot be greater than maximum salary');
                return false;
            }
        });

        // Suggest company names from existing companies (simple datalist)
        (function(){
            const input = document.getElementById('company_name');
            if (!input) return;
            // Build datalist from embedded PHP array
            const companies = <?php echo json_encode(array_map(function($c){return $c['name'];}, $allCompanies)); ?>;
            const dlId = 'companies_list_dl';
            let dl = document.getElementById(dlId);
            if (!dl) {
                dl = document.createElement('datalist');
                dl.id = dlId;
                document.body.appendChild(dl);
            }
            dl.innerHTML = companies.map(n=>`<option value="${n.replace(/"/g,'&quot;')}"></option>`).join('');
            input.setAttribute('list', dlId);
        })();
    </script>
    <script>
        // Quiz Management CRUD via simple fetch to inline endpoints
        const jobId = <?php echo isset($_GET['id']) ? (int)$_GET['id'] : 0; ?>;

        document.addEventListener('DOMContentLoaded', () => {
            const addBtn = document.getElementById('addQuestionBtn');
            const createdFlag = new URLSearchParams(location.search).get('created');
            if (jobId > 0) {
                // If there are pending questions from before save, push them now
                const pending = getPendingQuestions();
                if (createdFlag === '1' && pending.length > 0) {
                    bulkSavePendingQuestions(pending).then(() => {
                        clearPendingQuestions();
                        loadQuiz();
                    }).catch(()=>{ loadQuiz(); });
                } else {
                    loadQuiz();
                }
                addBtn.disabled = false;
                addBtn.addEventListener('click', () => openQuestionForm());
            } else {
                // Allow adding locally before the job exists
                addBtn.disabled = false;
                addBtn.addEventListener('click', () => openQuestionForm());
                // Start new job with a clean slate unless explicitly preserving drafts
                const keepDraft = new URLSearchParams(location.search).get('draft') === '1';
                if (!keepDraft) { clearPendingQuestions(); }
                renderPendingTable();
                if (!document.getElementById('preSaveInfo')) {
                    const info = document.createElement('p');
                    info.id = 'preSaveInfo';
                    info.className = 'section__description';
                    info.textContent = 'You can draft questions now. They will be saved to this job after you click Post Job.';
                    document.getElementById('quiz-admin').prepend(info);
                }
            }
        });

        async function loadQuiz() {
            try {
                const res = await fetch('quiz-admin.php?job_id=' + jobId);
                const html = await res.text();
                document.getElementById('quiz-admin').innerHTML = html;
            } catch(e) {
                document.getElementById('quiz-admin').innerHTML = '<p class="section__description">Failed to load quiz questions.</p>';
            }
        }

        function openQuestionForm(question={}) {
            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.style.display = 'block';
            modal.innerHTML = `
              <div class="modal-content">
                <span class="close" onclick="this.parentElement.parentElement.remove()">&times;</span>
                <h2>${question.id ? 'Edit' : 'Add'} Question</h2>
                <form id="qForm">
                  <input type="hidden" name="id" value="${question.id||''}">
                  <div class="form-group"><label>Question</label><textarea name="question" required>${question.question||''}</textarea></div>
                  <div class="form-row">
                    <div class="form-group"><label>Option A</label><input name="option_a" required value="${question.option_a||''}"></div>
                    <div class="form-group"><label>Option B</label><input name="option_b" required value="${question.option_b||''}"></div>
                  </div>
                  <div class="form-row">
                    <div class="form-group"><label>Option C</label><input name="option_c" required value="${question.option_c||''}"></div>
                    <div class="form-group"><label>Option D</label><input name="option_d" required value="${question.option_d||''}"></div>
                  </div>
                  <div class="form-group"><label>Correct Option</label>
                    <select name="correct_option" required>
                      ${['A','B','C','D'].map(x=>`<option value="${x}" ${question.correct_option===x?'selected':''}>${x}</option>`).join('')}
                    </select>
                  </div>
                  <div class="btn-container"><button type="submit" class="btn-primary">Save</button></div>
                </form>
              </div>`;
            document.body.appendChild(modal);
            modal.querySelector('#qForm').addEventListener('submit', async (e)=>{
                e.preventDefault();
                const fd = new FormData(e.target);
                if (jobId > 0) {
                    fd.append('job_id', String(jobId));
                    try {
                        const res = await fetch('quiz-admin.php', { method:'POST', body: fd });
                        const text = await res.text();
                        if (!res.ok) {
                            alert('Failed to save question: ' + text);
                            return;
                        }
                        modal.remove();
                        loadQuiz();
                    } catch(err) {
                        alert('Network error while saving question');
                    }
                } else {
                    // Save locally for later
                    const entry = Object.fromEntries(fd.entries());
                    delete entry.id; // treat as new until persisted
                    addPendingQuestion(entry);
                    modal.remove();
                    renderPendingTable();
                }
            });
        }

        async function deleteQuestion(id) {
            if (!confirm('Delete this question?')) return;
            const fd = new FormData();
            fd.append('delete','1');
            fd.append('id', id);
            try {
                const res = await fetch('quiz-admin.php', { method:'POST', body: fd });
                if (!res.ok) {
                    const text = await res.text();
                    alert('Failed to delete: ' + text);
                    return;
                }
                loadQuiz();
            } catch(err) {
                alert('Network error while deleting');
            }
        }

        // ---------- Pending (pre-save) quiz helpers ----------
        function getPendingQuestions() {
            try { return JSON.parse(sessionStorage.getItem('pending_quiz_questions') || '[]'); } catch { return []; }
        }
        function setPendingQuestions(arr) {
            sessionStorage.setItem('pending_quiz_questions', JSON.stringify(arr));
        }
        function clearPendingQuestions() { sessionStorage.removeItem('pending_quiz_questions'); }
        function addPendingQuestion(q) {
            const items = getPendingQuestions();
            items.push(q);
            setPendingQuestions(items);
        }
        function removePendingQuestion(index) {
            const items = getPendingQuestions();
            items.splice(index,1);
            setPendingQuestions(items);
            renderPendingTable();
        }
        function renderPendingTable() {
            const items = getPendingQuestions();
            const container = document.getElementById('quiz-admin');
            if (items.length === 0) {
                container.innerHTML = '<p class="section__description">No questions drafted yet.</p>';
                return;
            }
            let html = '<table class="pending-table"><thead><tr><th>#</th><th>Question</th><th>Options</th><th>Answer</th><th>Actions</th></tr></thead><tbody>';
            items.forEach((r, i) => {
                const opts = `A) ${escapeHtml(r.option_a)}<br>B) ${escapeHtml(r.option_b)}<br>C) ${escapeHtml(r.option_c)}<br>D) ${escapeHtml(r.option_d)}`;
                html += `<tr><td>${i+1}</td><td>${escapeHtml(r.question)}</td><td>${opts}</td><td>${escapeHtml(r.correct_option)}</td><td><button class="btn--small btn btn--outline" onclick="(${removePendingQuestion.toString()})(${i})">Remove</button></td></tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        }
        function escapeHtml(str) {
            return String(str).replace(/[&<>"]/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));
        }
        async function bulkSavePendingQuestions(items) {
            for (const entry of items) {
                const fd = new FormData();
                fd.append('job_id', String(jobId));
                fd.append('question', entry.question);
                fd.append('option_a', entry.option_a);
                fd.append('option_b', entry.option_b);
                fd.append('option_c', entry.option_c);
                fd.append('option_d', entry.option_d);
                fd.append('correct_option', entry.correct_option);
                const res = await fetch('quiz-admin.php', { method:'POST', body: fd });
                if (!res.ok) { throw new Error('Failed to persist pending question'); }
            }
        }
    </script>
</body>
</html>
