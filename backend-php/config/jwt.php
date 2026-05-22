<?php
/**
 * JWT Token Handler
 * Create, verify, and decode JWT tokens for secure API authentication
 */

require_once 'database.php';

class JWTHandler {
    
    /**
     * Create JWT token
     * @param string $firebase_uid - User's Firebase UID
     * @param string $email - User's email
     * @return string - JWT token
     */
    public static function generateToken($firebase_uid, $email) {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];
        
        $payload = [
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60), // 24 hours expiration
            'firebase_uid' => $firebase_uid,
            'email' => $email
        ];
        
        $header_encoded = self::base64UrlEncode(json_encode($header));
        $payload_encoded = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac(
            'sha256',
            "$header_encoded.$payload_encoded",
            JWT_SECRET,
            true
        );
        
        $signature_encoded = self::base64UrlEncode($signature);
        
        return "$header_encoded.$payload_encoded.$signature_encoded";
    }
    
    /**
     * Verify JWT token signature
     * @param string $token - JWT token to verify
     * @return bool - True if valid, false otherwise
     */
    public static function verifyToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header_encoded, $payload_encoded, $signature_encoded) = $parts;
        
        $signature = hash_hmac(
            'sha256',
            "$header_encoded.$payload_encoded",
            JWT_SECRET,
            true
        );
        
        $signature_provided = self::base64UrlDecode($signature_encoded);
        
        // Use timing-safe comparison to prevent timing attacks
        return hash_equals($signature, $signature_provided);
    }
    
    /**
     * Decode and return JWT payload
     * @param string $token - JWT token
     * @return array|bool - Payload array if valid, false otherwise
     */
    public static function decodeToken($token) {
        if (!self::verifyToken($token)) {
            return false;
        }
        
        $parts = explode('.', $token);
        $payload_encoded = $parts[1];
        
        $payload = json_decode(self::base64UrlDecode($payload_encoded), true);
        
        // Check expiration
        if ($payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Base64 URL encode (JWT standard)
     * @param string $data - Data to encode
     * @return string - Encoded data
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode (JWT standard)
     * @param string $data - Data to decode
     * @return string - Decoded data
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 4 - strlen($data) % 4));
    }
}
?>
