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
        case 'POST':
            $action = $_GET['action'] ?? '';
            switch ($action) {
                case 'register':
                    handleRegister($pdo, $input);
                    break;
                case 'login':
                    handleLogin($pdo, $input);
                    break;
                case 'logout':
                    handleLogout();
                    break;
                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
            }
            break;
        case 'GET':
            handleGetProfile($pdo);
            break;
        case 'PUT':
            handleUpdateProfile($pdo, $input);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleRegister($pdo, $input) {
    if (!$input || !isset($input['first_name']) || !isset($input['last_name']) || 
        !isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    // Validate email format
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        return;
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$input['email']]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'Email already registered']);
        return;
    }
    
    // Hash password
    $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
    
    // Insert new user
    $sql = "INSERT INTO users (first_name, last_name, email, password, phone, user_type) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $input['first_name'],
        $input['last_name'],
        $input['email'],
        $hashedPassword,
        $input['phone'] ?? null,
        $input['user_type'] ?? 'job_seeker'
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Start session
    session_start();
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $input['email'];
    $_SESSION['user_type'] = $input['user_type'] ?? 'job_seeker';
    
    echo json_encode([
        'message' => 'Registration successful',
        'user' => [
            'id' => $userId,
            'first_name' => $input['first_name'],
            'last_name' => $input['last_name'],
            'email' => $input['email'],
            'user_type' => $input['user_type'] ?? 'job_seeker'
        ]
    ]);
}

function handleLogin($pdo, $input) {
    if (!$input || !isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing email or password']);
        return;
    }
    
    // Get user by email
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, password, user_type FROM users WHERE email = ?");
    $stmt->execute([$input['email']]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($input['password'], $user['password'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
        return;
    }
    
    // Start session
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_type'] = $user['user_type'];
    
    echo json_encode([
        'message' => 'Login successful',
        'user' => [
            'id' => $user['id'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'user_type' => $user['user_type']
        ]
    ]);
}

function handleLogout() {
    session_start();
    session_destroy();
    echo json_encode(['message' => 'Logout successful']);
}

function handleGetProfile($pdo) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, user_type, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        return;
    }
    
    echo json_encode(['user' => $user]);
}

function handleUpdateProfile($pdo, $input) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        return;
    }
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'No data provided']);
        return;
    }
    
    $updateFields = [];
    $params = [];
    
    if (isset($input['first_name'])) {
        $updateFields[] = "first_name = ?";
        $params[] = $input['first_name'];
    }
    
    if (isset($input['last_name'])) {
        $updateFields[] = "last_name = ?";
        $params[] = $input['last_name'];
    }
    
    if (isset($input['phone'])) {
        $updateFields[] = "phone = ?";
        $params[] = $input['phone'];
    }
    
    if (isset($input['password'])) {
        $updateFields[] = "password = ?";
        $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
    }
    
    if (empty($updateFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid fields to update']);
        return;
    }
    
    $params[] = $_SESSION['user_id'];
    
    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode(['message' => 'Profile updated successfully']);
}
?>
