# API Documentation

## Overview

Secure REST API built with PHP for Android app integration. Uses JWT tokens for authentication and Firebase for user verification.

## Base URL

```
https://your-domain.hostinger.com/api/
```

## Authentication

### Two-Step Authentication Flow

1. **Firebase Authentication** (Client)
   - User logs in with email/password
   - Firebase returns ID token

2. **JWT Token Exchange** (Client → Server)
   - Send Firebase ID token to `/api/auth.php`
   - Receive JWT token
   - Use JWT token for subsequent requests

### Headers

All requests (except auth) require:

```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

## Endpoints

### 1. Authentication

#### POST /api/auth.php

Exchange Firebase ID token for JWT token.

**Request:**
```json
{
  "firebase_token": "eyJhbGciOiJSUzI1NiIsImtpZCI6IjEifQ..."
}
```

**Response (Success):**
```json
{
  "success": true,
  "jwt_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "uid": "firebase_uid_123",
    "email": "user@example.com"
  }
}
```

**Response (Error):**
```json
{
  "error": "Invalid Firebase token"
}
```

**Status Codes:**
- 200: Success
- 400: Missing firebase_token
- 401: Invalid Firebase token

**Notes:**
- Firebase token is valid for 1 hour
- JWT token is valid for 24 hours
- Implement token refresh logic on client

---

### 2. Get Posts

#### GET /api/data.php

Fetch all posts from database.

**Request:**
```
GET /api/data.php
Authorization: Bearer <jwt_token>
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "First Post",
      "description": "This is the first post",
      "created_at": "2024-01-15 10:30:45"
    },
    {
      "id": 2,
      "title": "Second Post",
      "description": "This is the second post",
      "created_at": "2024-01-15 11:45:30"
    }
  ]
}
```

**Status Codes:**
- 200: Success
- 401: Invalid or missing JWT token
- 500: Database error

**Notes:**
- Returns max 50 most recent posts
- Implements pagination if needed
- Response is sorted by created_at DESC

---

### 3. Create Post

#### POST /api/data.php

Create new post (Admin only).

**Request:**
```json
{
  "title": "New Post Title",
  "description": "New post description content"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Post created",
  "post_id": 42
}
```

**Response (Error - Not Admin):**
```json
{
  "error": "Unauthorized: Admin access required"
}
```

**Response (Error - Missing Fields):**
```json
{
  "error": "Title and description required"
}
```

**Status Codes:**
- 201: Created successfully
- 400: Missing required fields
- 401: Invalid JWT token
- 403: User is not admin
- 500: Database error

**Validation:**
- Title: Required, string
- Description: Required, string
- Title max length: 255 characters
- Description: No max length limit

**Admin Authorization:**
User's email must be in ADMIN_EMAILS array in `config/database.php`

---

## Error Handling

### Standard Error Response

```json
{
  "error": "Error message here"
}
```

### Common Errors

| Error | Status | Cause | Solution |
|-------|--------|-------|----------|
| Missing Authorization header | 401 | No token provided | Add Authorization header |
| Invalid or expired token | 401 | Token invalid/expired | Get new JWT token |
| Admin access required | 403 | User not admin | Use admin account |
| Title and description required | 400 | Missing fields | Include all fields |
| Firebase token invalid | 401 | Firebase token bad | Re-login to get new token |
| Database connection failed | 500 | DB unreachable | Check Hostinger DB settings |

---

## Security Details

### JWT Token Structure

```
Header.Payload.Signature
```

**Header:**
```json
{
  "alg": "HS256",
  "typ": "JWT"
}
```

**Payload:**
```json
{
  "iat": 1705334400,
  "exp": 1705420800,
  "firebase_uid": "user123",
  "email": "user@example.com"
}
```

### Security Measures

1. **HMAC-SHA256 Signing** - Prevents token tampering
2. **Expiration** - Tokens expire in 24 hours
3. **Timing-Safe Comparison** - Prevents timing attacks
4. **Firebase Verification** - Confirms user authenticity
5. **Email-Based Authorization** - Controls admin access
6. **Input Sanitization** - Prevents SQL injection
7. **CORS Headers** - Prevents unauthorized access

---

## Rate Limiting

Current implementation has no rate limiting. For production:

```php
// Add to middleware
if (!isset($_SESSION['requests'])) {
    $_SESSION['requests'] = 0;
    $_SESSION['request_time'] = time();
}

if (time() - $_SESSION['request_time'] < 60) {
    $_SESSION['requests']++;
    if ($_SESSION['requests'] > 100) {
        http_response_code(429);
        die(json_encode(['error' => 'Too many requests']));
    }
}
```

---

## Pagination Example

For large datasets:

```php
$limit = 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$query = "SELECT * FROM posts LIMIT $limit OFFSET $offset";
```

---

## Caching Strategy

Android client-side caching:

```kotlin
// Cache posts for 5 minutes
val cachedTime = SharedPreferences.getLong("posts_cache_time", 0)
if (System.currentTimeMillis() - cachedTime < 300000) {
    // Use cached data
} else {
    // Fetch fresh data
}
```

---

## Testing with cURL

### Get Firebase Token (Manual)

```bash
curl -X POST https://identitytoolkit.googleapis.com/v1/accounts:signUp \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "returnSecureToken": true
  }' \
  -G --data-urlencode "key=YOUR_FIREBASE_API_KEY"
```

### Exchange for JWT

```bash
curl -X POST https://your-domain.hostinger.com/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{"firebase_token": "firebase_token_here"}'
```

### Fetch Posts

```bash
curl -X GET https://your-domain.hostinger.com/api/data.php \
  -H "Authorization: Bearer jwt_token_here"
```

### Create Post

```bash
curl -X POST https://your-domain.hostinger.com/api/data.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer jwt_token_here" \
  -d '{
    "title": "Test Post",
    "description": "This is a test post"
  }'
```

---

## Database Schema

### Posts Table

```sql
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Best Practices

### Client Side

1. **Store JWT Securely**
   ```kotlin
   // Use Android SharedPreferences with encryption
   val encryptedSharedPrefs = EncryptedSharedPreferences.create(...)
   encryptedSharedPrefs.edit().putString("jwt_token", token).apply()
   ```

2. **Refresh Tokens**
   ```kotlin
   // Refresh Firebase token before JWT expires
   if (tokenExpiry - now < 3600) {
       getNewFirebaseToken()
   }
   ```

3. **Handle Errors Gracefully**
   ```kotlin
   when (response.code()) {
       401 -> { /* Re-authenticate */ }
       403 -> { /* Show permission error */ }
       500 -> { /* Retry or show error */ }
   }
   ```

### Server Side

1. **Log Errors**
   ```php
   error_log('Authentication failed: ' . $error, 0);
   ```

2. **Monitor Performance**
   ```php
   $start = microtime(true);
   // ... code ...
   $time = microtime(true) - $start;
   ```

3. **Regular Backups**
   - Setup automated MySQL backups
   - Test restore procedures

---

## Versioning

Current API version: **1.0**

For future versions:
```
GET /api/v2/posts
POST /api/v2/posts
```

---

## Support

For API issues:
1. Check status codes in responses
2. Review error messages
3. Verify all required headers are sent
4. Check Hostinger error logs
5. Create GitHub issue with detailed information
