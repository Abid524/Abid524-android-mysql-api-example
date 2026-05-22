<?php
/**
 * JWT Authentication Middleware
 * Handles authorization for API endpoints
 */

require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/firebase.php';

class AuthMiddleware {
    
    /**
     * Verify JWT token from Authorization header
     * @return array - JWT payload if valid
     */
    public static function verifyJWT() {
        $headers = self::getHeaders();
        
        if (!isset($headers['Authorization'])) {
            self::sendError('Missing Authorization header', 401);
        }
        
        $auth_header = $headers['Authorization'];
        
        if (!preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            self::sendError('Invalid Authorization header format', 401);
        }
        
        $token = $matches[1];
        $payload = JWTHandler::decodeToken($token);
        
        if (!$payload) {
            self::sendError('Invalid or expired token', 401);
        }
        
        return $payload;
    }
    
    /**
     * Verify Firebase ID token from Authorization header
     * @return array - Firebase user info if valid
     */
    public static function verifyFirebaseToken() {
        $headers = self::getHeaders();
        
        if (!isset($headers['Authorization'])) {
            self::sendError('Missing Authorization header', 401);
        }
        
        $auth_header = $headers['Authorization'];
        
        if (!preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            self::sendError('Invalid Authorization header format', 401);
        }
        
        $id_token = $matches[1];
        $firebase_user = FirebaseAuth::verifyFirebaseToken($id_token);
        
        if (!$firebase_user) {
            self::sendError('Invalid Firebase token', 401);
        }
        
        return $firebase_user;
    }
    
    /**
     * Check if user is admin based on email
     * @param array $firebase_user - Firebase user data
     * @return bool - True if user is admin
     */
    public static function checkAdmin($firebase_user) {
        $email = $firebase_user['email'] ?? null;
        
        if (!$email || !in_array($email, ADMIN_EMAILS)) {
            self::sendError('Unauthorized: Admin access required', 403);
        }
        
        return true;
    }
    
    /**
     * Get all headers from request
     * @return array - Headers array
     */
    private static function getHeaders() {
        $headers = [];
        
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $key = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$key] = $value;
            } elseif ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH') {
                $headers[$key] = $value;
            }
        }
        
        return $headers;
    }
    
    /**
     * Send error response and exit
     * @param string $message - Error message
     * @param int $code - HTTP status code
     */
    private static function sendError($message, $code = 400) {
        http_response_code($code);
        die(json_encode(['error' => $message]));
    }
}
?>
