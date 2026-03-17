<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDBConnection();
    
    switch ($method) {
        case 'GET':
            handleGetJobs($pdo);
            break;
        case 'POST':
            handleCreateJob($pdo, $input);
            break;
        case 'PUT':
            handleUpdateJob($pdo, $input);
            break;
        case 'DELETE':
            handleDeleteJob($pdo);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetJobs($pdo) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : null;
    $location = isset($_GET['location']) ? $_GET['location'] : null;
    $job_type = isset($_GET['job_type']) ? $_GET['job_type'] : null;
    
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT j.*, c.name as company_name, c.logo as company_logo, c.location as company_location,
                   cat.name as category_name, cat.icon as category_icon
            FROM jobs j
            LEFT JOIN companies c ON j.company_id = c.id
            LEFT JOIN job_categories cat ON j.category_id = cat.id
            WHERE j.status = 'active'";
    
    $params = [];
    
    if ($category) {
        $sql .= " AND cat.name = ?";
        $params[] = $category;
    }
    
    if ($search) {
        $sql .= " AND (j.title LIKE ? OR j.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($location) {
        $sql .= " AND j.location LIKE ?";
        $params[] = "%$location%";
    }
    
    if ($job_type) {
        $sql .= " AND j.job_type = ?";
        $params[] = $job_type;
    }
    
    $sql .= " ORDER BY j.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) FROM jobs j
                 LEFT JOIN companies c ON j.company_id = c.id
                 LEFT JOIN job_categories cat ON j.category_id = cat.id
                 WHERE j.status = 'active'";
    
    $countParams = [];
    if ($category) {
        $countSql .= " AND cat.name = ?";
        $countParams[] = $category;
    }
    if ($search) {
        $countSql .= " AND (j.title LIKE ? OR j.description LIKE ?)";
        $countParams[] = "%$search%";
        $countParams[] = "%$search%";
    }
    if ($location) {
        $countSql .= " AND j.location LIKE ?";
        $countParams[] = "%$location%";
    }
    if ($job_type) {
        $countSql .= " AND j.job_type = ?";
        $countParams[] = $job_type;
    }
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $total = $countStmt->fetchColumn();
    
    echo json_encode([
        'jobs' => $jobs,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function handleCreateJob($pdo, $input) {
    if (!$input || !isset($input['title']) || !isset($input['description'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    $sql = "INSERT INTO jobs (title, description, company_id, category_id, location, job_type, 
                             salary_min, salary_max, salary_currency, positions_available, 
                             requirements, benefits, posted_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $input['title'],
        $input['description'],
        $input['company_id'] ?? null,
        $input['category_id'] ?? null,
        $input['location'] ?? null,
        $input['job_type'] ?? 'Full Time',
        $input['salary_min'] ?? null,
        $input['salary_max'] ?? null,
        $input['salary_currency'] ?? 'USD',
        $input['positions_available'] ?? 1,
        $input['requirements'] ?? null,
        $input['benefits'] ?? null,
        $input['posted_by'] ?? null
    ]);
    
    $jobId = $pdo->lastInsertId();
    
    // Get the created job with company and category details
    $sql = "SELECT j.*, c.name as company_name, c.logo as company_logo, c.location as company_location,
                   cat.name as category_name, cat.icon as category_icon
            FROM jobs j
            LEFT JOIN companies c ON j.company_id = c.id
            LEFT JOIN job_categories cat ON j.category_id = cat.id
            WHERE j.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    
    echo json_encode(['message' => 'Job created successfully', 'job' => $job]);
}

function handleUpdateJob($pdo, $input) {
    $jobId = $_GET['id'] ?? null;
    if (!$jobId || !$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing job ID or data']);
        return;
    }
    
    $sql = "UPDATE jobs SET title = ?, description = ?, company_id = ?, category_id = ?, 
                           location = ?, job_type = ?, salary_min = ?, salary_max = ?, 
                           salary_currency = ?, positions_available = ?, requirements = ?, 
                           benefits = ?, status = ?
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $input['title'] ?? null,
        $input['description'] ?? null,
        $input['company_id'] ?? null,
        $input['category_id'] ?? null,
        $input['location'] ?? null,
        $input['job_type'] ?? null,
        $input['salary_min'] ?? null,
        $input['salary_max'] ?? null,
        $input['salary_currency'] ?? null,
        $input['positions_available'] ?? null,
        $input['requirements'] ?? null,
        $input['benefits'] ?? null,
        $input['status'] ?? 'active',
        $jobId
    ]);
    
    echo json_encode(['message' => 'Job updated successfully']);
}

function handleDeleteJob($pdo) {
    $jobId = $_GET['id'] ?? null;
    if (!$jobId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing job ID']);
        return;
    }
    
    $sql = "UPDATE jobs SET status = 'closed' WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$jobId]);
    
    echo json_encode(['message' => 'Job closed successfully']);
}
?>
