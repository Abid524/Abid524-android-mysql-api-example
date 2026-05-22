<?php
/**
 * Authentication Endpoint
 * POST /api/auth.php - Exchange Firebase token for JWT
 * GET /api/auth.php - Health check
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Health check for GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Authentication API is running',
        'timestamp' => date('Y-m-d H:i:s'),
        'usage' => 'POST firebase_token to get JWT'
    ]);
    exit;
}

require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/firebase.php';
require_once __DIR__ . '/../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed. Use POST.']));
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['firebase_token'])) {
    http_response_code(400);
    die(json_encode(['error' => 'Firebase token required']));
}

// Verify Firebase token
$firebase_user = FirebaseAuth::verifyFirebaseToken($input['firebase_token']);

if (!$firebase_user) {
    http_response_code(401);
    die(json_encode(['error' => 'Invalid Firebase token']));
}

// Generate JWT token
$jwt_token = JWTHandler::generateToken(
    $firebase_user['sub'], // Firebase UID is in 'sub' claim
    $firebase_user['email']
);

http_response_code(200);
echo json_encode([
    'success' => true,
    'jwt_token' => $jwt_token,
    'user' => [
        'uid' => $firebase_user['sub'],
        'email' => $firebase_user['email']
    ]
]);
?>
