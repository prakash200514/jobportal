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
            handleGetTestimonials($pdo);
            break;
        case 'POST':
            handleCreateTestimonial($pdo, $input);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGetTestimonials($pdo) {
    $sql = "SELECT * FROM testimonials WHERE status = 'active' ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $testimonials = $stmt->fetchAll();
    
    echo json_encode(['testimonials' => $testimonials]);
}

function handleCreateTestimonial($pdo, $input) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        return;
    }
    
    if (!$input || !isset($input['name']) || !isset($input['content'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        return;
    }
    
    $sql = "INSERT INTO testimonials (user_id, name, position, content, rating, image) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_SESSION['user_id'],
        $input['name'],
        $input['position'] ?? null,
        $input['content'],
        $input['rating'] ?? 5,
        $input['image'] ?? null
    ]);
    
    $testimonialId = $pdo->lastInsertId();
    
    echo json_encode([
        'message' => 'Testimonial submitted successfully',
        'testimonial_id' => $testimonialId
    ]);
}
?>
