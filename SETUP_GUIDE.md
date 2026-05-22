# Complete Setup Guide

## Prerequisites

- Hostinger account with PHP and MySQL support
- Firebase account
- Android Studio
- JDK 11 or higher
- Git

## Step 1: Backend Setup on Hostinger

### 1.1 Upload PHP Files

1. Connect to Hostinger via FTP/File Manager
2. Navigate to `public_html` directory
3. Create folder structure:
   ```
   public_html/
   └── api/
       ├── config/
       ├── middleware/
       └── api/
   ```
4. Upload all PHP files from `backend-php/` directory

### 1.2 Create MySQL Database

1. Go to Hostinger Control Panel → Databases
2. Create new database (e.g., `android_api_db`)
3. Create new database user
4. Assign user to database with all privileges
5. Note down:
   - Database name
   - Username
   - Password
   - Hostname (usually localhost or specific host)

### 1.3 Run Database Schema

1. Go to phpMyAdmin in Hostinger Control Panel
2. Select your database
3. Go to SQL tab
4. Copy and paste content from `DATABASE_SCHEMA.sql`
5. Click Execute

### 1.4 Configure database.php

1. Edit `backend-php/config/database.php`
2. Update these values:
   ```php
   define('DB_HOST', 'your_hostinger_host');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'your_database_name');
   define('JWT_SECRET', 'generate_a_random_32_char_string');
   define('ADMIN_EMAILS', array('your_admin_email@example.com'));
   ```
3. Generate JWT_SECRET: Use a tool like https://www.random.org/passwords/

### 1.5 Test API Connection

```bash
curl -X POST https://your-domain.hostinger.com/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{"firebase_token": "test_token"}'
```

## Step 2: Firebase Setup

### 2.1 Create Firebase Project

1. Go to https://console.firebase.google.com
2. Click "Add project"
3. Enter project name
4. Accept terms and create

### 2.2 Enable Authentication

1. In Firebase Console, go to Authentication
2. Click "Get started"
3. Select "Email/Password"
4. Enable it
5. Go to Settings → Project settings
6. Copy your Project ID

### 2.3 Add Android App

1. Click "Add app" → Android
2. Enter package name: `com.example.apiapp`
3. Download `google-services.json`
4. Follow on-screen instructions

### 2.4 Update Firebase Configuration

In `backend-php/config/firebase.php`:
```php
define('FIREBASE_PROJECT_ID', 'your_project_id');
define('FIREBASE_API_KEY', 'your_api_key');
```

## Step 3: Android App Setup

### 3.1 Clone Repository

```bash
git clone https://github.com/Abid524/Abid524-android-mysql-api-example.git
cd Abid524-android-mysql-api-example
```

### 3.2 Add Firebase Configuration

1. Copy downloaded `google-services.json`
2. Place it in `android-app/app/` directory
3. Sync Gradle (Android Studio will prompt)

### 3.3 Update API Configuration

Edit `android-app/app/src/main/kotlin/com/example/apiapp/data/ApiClient.kt`:

```kotlin
private const val BASE_URL = "https://your-domain.hostinger.com/api/"
```

### 3.4 Build and Run

1. Open project in Android Studio
2. Let Gradle sync
3. Connect Android device or open emulator
4. Click Run → Run 'app'

## Step 4: Testing

### 4.1 Create Admin Account

1. In Firebase Console → Authentication
2. Create user with admin email (must match ADMIN_EMAILS in database.php)

### 4.2 Test Login Flow

1. Run app on device
2. Enter admin email and password
3. Click "Login / Sign Up"
4. If successful, you'll see the data interface

### 4.3 Test Data Fetch

1. Click "Fetch Posts"
2. Should display posts from database (empty if no posts created)

### 4.4 Test Admin Creation

1. Enter title and description
2. Click "Create Post (Admin Only)"
3. Post should appear in list

### 4.5 Test Non-Admin User

1. Create regular user (email NOT in ADMIN_EMAILS)
2. Login with that account
3. Try creating post
4. Should get "Admin access required" error

## Troubleshooting

### Issue: 404 on API endpoints

**Solution:**
- Verify BASE_URL includes `/api/` at end
- Check API files are uploaded correctly
- Verify .htaccess allows access

### Issue: 401 Unauthorized

**Solution:**
- Firebase token might have expired (valid for 1 hour)
- Check JWT_SECRET matches in database.php and auth verification
- Verify Authorization header format: `Bearer <token>`

### Issue: Firebase token verification fails

**Solution:**
- Verify FIREBASE_PROJECT_ID is correct
- Check internet connectivity on Hostinger
- Ensure cURL is enabled on Hostinger

### Issue: JWT token not generating

**Solution:**
- Verify JWT_SECRET is at least 32 characters
- Check PHP version supports hash_hmac()
- Review error logs in Hostinger

### Issue: Android app crashes on login

**Solution:**
- Verify google-services.json is in correct location
- Check package name matches firebase config
- Review logcat for detailed error messages

### Issue: Post creation fails with 403

**Solution:**
- Verify user email is in ADMIN_EMAILS array
- Check Firebase token is still valid
- Ensure JWT token is being sent in Authorization header

## Security Checklist

- [ ] Changed all default credentials
- [ ] Generated strong JWT_SECRET (min 32 chars)
- [ ] Enabled HTTPS on Hostinger
- [ ] Set ADMIN_EMAILS to actual admin emails
- [ ] Updated BASE_URL to your domain
- [ ] Firebase security rules configured
- [ ] Database backups enabled
- [ ] Removed debug logging from production

## Next Steps

1. **Add more endpoints** - Add edit, delete, search functionality
2. **Implement pagination** - Load posts in batches
3. **Add caching** - Cache posts locally on device
4. **User profiles** - Store and display user information
5. **Error handling** - Add retry logic and better error messages
6. **Analytics** - Track user actions and API performance

## Support

For issues:
1. Check logs in Hostinger File Manager → error_log
2. Review Firebase Console for authentication errors
3. Use Android Studio's Logcat for app errors
4. Create GitHub issue with detailed error message
