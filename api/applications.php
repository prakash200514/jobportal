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
// Fallback for multipart/form-data where php://input is empty
if (!$input && !empty($_POST)) {
    $input = $_POST;
}

try {
    $pdo = getDBConnection();
    
    switch ($method) {
        case 'GET':
            handleGetApplications($pdo);
            break;
        case 'POST':
            handleCreateApplication($pdo, $input);
            break;
        case 'PUT':
            handleUpdateApplication($pdo, $input);
            break;
        case 'DELETE':
            handleDeleteApplication($pdo);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetApplications($pdo) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $jobId = $_GET['job_id'] ?? null;
    
    if ($jobId) {
        // Get specific application
        $sql = "SELECT ja.*, j.title as job_title, c.name as company_name, c.logo as company_logo
                FROM job_applications ja
                JOIN jobs j ON ja.job_id = j.id
                LEFT JOIN companies c ON j.company_id = c.id
                WHERE ja.job_id = ? AND ja.user_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$jobId, $userId]);
        $application = $stmt->fetch();
        
        if (!$application) {
            http_response_code(404);
            echo json_encode(['error' => 'Application not found']);
            return;
        }
        
        echo json_encode(['application' => $application]);
    } else {
        // Get all applications for user
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $status = $_GET['status'] ?? null;
        
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT ja.*, j.title as job_title, j.location as job_location, j.job_type,
                       c.name as company_name, c.logo as company_logo,
                       cat.name as category_name
                FROM job_applications ja
                JOIN jobs j ON ja.job_id = j.id
                LEFT JOIN companies c ON j.company_id = c.id
                LEFT JOIN job_categories cat ON j.category_id = cat.id
                WHERE ja.user_id = ?";
        
        $params = [$userId];
        
        if ($status) {
            $sql .= " AND ja.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY ja.applied_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $applications = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM job_applications ja WHERE ja.user_id = ?";
        $countParams = [$userId];
        
        if ($status) {
            $countSql .= " AND ja.status = ?";
            $countParams[] = $status;
        }
        
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetchColumn();
        
        echo json_encode([
            'applications' => $applications,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
}

function handleCreateApplication($pdo, $input) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        return;
    }
    
    if (!$input || !isset($input['job_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing job ID']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    $jobId = $input['job_id'];
    
    // Check if job exists and is active
    $stmt = $pdo->prepare("SELECT id, title FROM jobs WHERE id = ? AND status = 'active'");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    
    if (!$job) {
        http_response_code(404);
        echo json_encode(['error' => 'Job not found or not available']);
        return;
    }
    
    // Check if user already applied
    $stmt = $pdo->prepare("SELECT id FROM job_applications WHERE job_id = ? AND user_id = ?");
    $stmt->execute([$jobId, $userId]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Already applied to this job']);
        return;
    }
    
    // Handle file upload for resume
    $resumePath = null;
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/resumes/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['resume']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['resume']['tmp_name'], $targetPath)) {
            $resumePath = 'uploads/resumes/' . $fileName;
        }
    }
    
    // Create application
    $sql = "INSERT INTO job_applications (job_id, user_id, resume_path, cover_letter) 
            VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $jobId,
        $userId,
        $resumePath,
        $input['cover_letter'] ?? null
    ]);
    
    $applicationId = $pdo->lastInsertId();
    
    echo json_encode([
        'message' => 'Application submitted successfully',
        'application_id' => $applicationId
    ]);
}

function handleUpdateApplication($pdo, $input) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        return;
    }
    
    $applicationId = $_GET['id'] ?? null;
    if (!$applicationId || !$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing application ID or data']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Check if application belongs to user
    $stmt = $pdo->prepare("SELECT id FROM job_applications WHERE id = ? AND user_id = ?");
    $stmt->execute([$applicationId, $userId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Application not found']);
        return;
    }
    
    $updateFields = [];
    $params = [];
    
    if (isset($input['cover_letter'])) {
        $updateFields[] = "cover_letter = ?";
        $params[] = $input['cover_letter'];
    }
    
    if (isset($input['status'])) {
        $updateFields[] = "status = ?";
        $params[] = $input['status'];
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid fields to update']);
        return;
    }
    
    $params[] = $applicationId;
    
    $sql = "UPDATE job_applications SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode(['message' => 'Application updated successfully']);
}

function handleDeleteApplication($pdo) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        return;
    }
    
    $applicationId = $_GET['id'] ?? null;
    if (!$applicationId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing application ID']);
        return;
    }
    
    $userId = $_SESSION['user_id'];
    
    // Check if application belongs to user
    $stmt = $pdo->prepare("SELECT id FROM job_applications WHERE id = ? AND user_id = ?");
    $stmt->execute([$applicationId, $userId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Application not found']);
        return;
    }
    
    $sql = "DELETE FROM job_applications WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$applicationId]);
    
    echo json_encode(['message' => 'Application deleted successfully']);
}
?>
