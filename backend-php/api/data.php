<?php
/**
 * Data Endpoint
 * GET /api/data.php - Fetch data (requires JWT)
 * POST /api/data.php - Insert data (requires JWT + admin email)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/firebase.php';
require_once __DIR__ . '/../middleware/auth.php';

// Verify JWT token
$jwt_payload = AuthMiddleware::verifyJWT();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch data
    $query = "SELECT id, title, description, created_at FROM posts ORDER BY created_at DESC LIMIT 50";
    $result = $mysqli->query($query);
    
    if (!$result) {
        http_response_code(500);
        die(json_encode(['error' => 'Database query failed: ' . $mysqli->error]));
    }
    
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $posts,
        'count' => count($posts)
    ]);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For POST requests, verify Firebase token for admin check
    $headers = getHeaders();
    
    // DEBUG: Log headers
    error_log('POST Headers: ' . json_encode($headers));
    
    $firebase_user = null;
    $firebase_error = null;
    
    if (isset($headers['Authorization'])) {
        error_log('Authorization header found');
        if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
            $token = $matches[1];
            error_log('Token extracted, length: ' . strlen($token));
            $firebase_user = FirebaseAuth::verifyFirebaseToken($token);
            
            if (!$firebase_user) {
                $firebase_error = 'Firebase token verification failed';
                error_log($firebase_error);
            } else {
                error_log('Firebase user verified: ' . $firebase_user['email']);
            }
        } else {
            $firebase_error = 'Invalid Authorization header format';
            error_log($firebase_error);
        }
    } else {
        $firebase_error = 'No Authorization header provided';
        error_log($firebase_error);
    }
    
    // Check if user is admin
    if (!$firebase_user) {
        http_response_code(403);
        die(json_encode([
            'error' => 'Firebase authentication failed: ' . ($firebase_error ?? 'Unknown error'),
            'debug' => [
                'has_auth_header' => isset($headers['Authorization']),
                'admin_emails_configured' => count(ADMIN_EMAILS),
                'message' => 'Add this email to ADMIN_EMAILS in config/database.php'
            ]
        ]));
    }
    
    if (!in_array($firebase_user['email'], ADMIN_EMAILS)) {
        http_response_code(403);
        die(json_encode([
            'error' => 'Admin access required for POST requests',
            'your_email' => $firebase_user['email'],
            'admin_emails_required' => ADMIN_EMAILS,
            'message' => 'Your email is not in the admin list. Add it to ADMIN_EMAILS array.'
        ]));
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['title']) || !isset($input['description'])) {
        http_response_code(400);
        die(json_encode(['error' => 'Title and description required']));
    }
    
    $title = $mysqli->real_escape_string($input['title']);
    $description = $mysqli->real_escape_string($input['description']);
    
    // Validate input
    if (strlen($title) === 0 || strlen($title) > 255) {
        http_response_code(400);
        die(json_encode(['error' => 'Title must be between 1 and 255 characters']));
    }
    
    if (strlen($description) === 0) {
        http_response_code(400);
        die(json_encode(['error' => 'Description cannot be empty']));
    }
    
    $query = "INSERT INTO posts (title, description, created_at) VALUES ('$title', '$description', NOW())";
    
    if ($mysqli->query($query)) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Post created successfully',
            'post_id' => $mysqli->insert_id
        ]);
    } else {
        http_response_code(500);
        die(json_encode(['error' => 'Failed to create post: ' . $mysqli->error]));
    }
}

function getHeaders() {
    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (substr($key, 0, 5) === 'HTTP_') {
            $key = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$key] = $value;
        }
    }
    return $headers;
}
?>
