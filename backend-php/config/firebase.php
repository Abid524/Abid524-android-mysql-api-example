<?php
/**
 * Firebase Authentication Handler
 * Verify Firebase ID tokens and extract user information
 */

class FirebaseAuth {
    
    /**
     * Verify Firebase ID token
     * @param string $id_token - Firebase ID token from client
     * @return array|bool - User data if valid, false otherwise
     */
    public static function verifyFirebaseToken($id_token) {
        // Google's public keys endpoint for Firebase
        $url = "https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com";
        
        // Setup context with timeout
        $context = stream_context_create([
            'http' => [
                'timeout' => 5
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ]
        ]);
        
        // Fetch Google's public keys
        $response = @file_get_contents($url, false, $context);
        
        if (!$response) {
            error_log('Failed to fetch Firebase public keys');
            return false;
        }
        
        $keys = json_decode($response, true);
        
        // Decode token header to get key ID (without verification first)
        $parts = explode('.', $id_token);
        if (count($parts) !== 3) {
            return false;
        }
        
        $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        
        $kid = $header['kid'] ?? null;
        
        if (!$kid || !isset($keys[$kid])) {
            error_log('Invalid or missing kid in Firebase token');
            return false;
        }
        
        // Get the public key
        $key = $keys[$kid];
        
        // Verify signature
        if (self::verifySignature($id_token, $key)) {
            // Additional validation checks
            if (isset($payload['aud']) && isset($payload['iss'])) {
                // You can add project ID validation here if needed
                return $payload;
            }
        }
        
        return false;
    }
    
    /**
     * Verify JWT signature with public key
     * @param string $token - JWT token
     * @param string $public_key_pem - PEM formatted public key
     * @return bool - True if signature is valid
     */
    private static function verifySignature($token, $public_key_pem) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header, $payload, $signature) = $parts;
        $message = "$header.$payload";
        
        // Load the public key
        $public_key = openssl_pkey_get_public($public_key_pem);
        
        if (!$public_key) {
            error_log('Failed to load public key');
            return false;
        }
        
        // Decode the signature
        $signature_decoded = base64_decode(strtr($signature, '-_', '+/'), true);
        
        // Verify the signature
        $result = openssl_verify(
            $message,
            $signature_decoded,
            $public_key,
            OPENSSL_ALGO_SHA256
        );
        
        openssl_free_key($public_key);
        
        return $result === 1;
    }
}
?>
