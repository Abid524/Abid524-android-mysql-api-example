<?php
/**
 * Database Configuration
 * MySQL connection for Hostinger
 */

// Database credentials
define('DB_HOST', 'your_hostinger_host'); // e.g., localhost or your Hostinger MySQL host
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_database_name');

// JWT Secret (keep this secure, use a strong random string minimum 32 characters)
define('JWT_SECRET', 'your_super_secret_jwt_key_min_32_chars_12345678'); 

// Firebase credentials
define('FIREBASE_PROJECT_ID', 'your_firebase_project_id');
define('FIREBASE_API_KEY', 'your_firebase_api_key');

// Admin emails authorized for POST requests
define('ADMIN_EMAILS', array(
    'admin1@example.com',
    'admin2@example.com'
));

// Database connection
try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($mysqli->connect_error) {
        throw new Exception('Connection failed: ' . $mysqli->connect_error);
    }
    
    // Set charset
    $mysqli->set_charset('utf8mb4');
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Database connection error: ' . $e->getMessage());
    die(json_encode(['error' => 'Database connection failed']));
}
?>
