# Android MySQL API Example

Complete example of an Android app connecting to MySQL database via a secure PHP REST API with JWT authentication and Firebase integration.

## Features

✅ **Firebase Authentication** - Secure user signup/login
✅ **JWT Token System** - Secure API access
✅ **MySQL Database** - On Hostinger
✅ **Admin Authorization** - Email-based access control
✅ **HTTPS Support** - Production-ready security
✅ **Kotlin/Retrofit** - Modern Android development
✅ **Coroutines** - Async API calls

## Project Structure

```
.
├── backend-php/                 # PHP REST API
│   ├── config/
│   │   ├── database.php        # MySQL connection
│   │   ├── jwt.php             # JWT token handler
│   │   └── firebase.php        # Firebase verification
│   ├── middleware/
│   │   └── auth.php            # Authentication middleware
│   └── api/
│       ├── auth.php            # Authentication endpoint
│       └── data.php            # Data CRUD endpoint
├── android-app/                # Android Application
│   ├── app/
│   │   ├── src/main/
│   │   │   ├── kotlin/
│   │   │   │   └── com/example/apiapp/
│   │   │   │       ├── data/
│   │   │   │       │   ├── ApiClient.kt
│   │   │   │       │   └── ApiService.kt
│   │   │   │       └── ui/
│   │   │   │           └── MainActivity.kt
│   │   │   └── res/
│   │   │       └── layout/
│   │   │           └── activity_main.xml
│   │   └── AndroidManifest.xml
│   └── build.gradle.kts
├── DATABASE_SCHEMA.sql
├── SETUP_GUIDE.md
└── API_DOCUMENTATION.md
```

## Quick Start

### 1. Backend Setup (Hostinger)

- Upload PHP files to your Hostinger public_html
- Create MySQL database
- Run the database schema
- Update `config/database.php` with your credentials

### 2. Firebase Setup

- Create Firebase project at https://console.firebase.google.com
- Enable Email/Password authentication
- Download `google-services.json` for your Android app

### 3. Android Setup

- Clone this repository
- Copy `google-services.json` to `android-app/app/`
- Update `BASE_URL` in `ApiClient.kt`
- Build and run on device/emulator

## Security Features

- **JWT Tokens** expire in 24 hours
- **Firebase verification** for token authenticity
- **Email-based admin authorization**
- **CORS headers** prevent unauthorized access
- **Timing-safe comparison** prevents timing attacks
- **Input sanitization** prevents SQL injection

## API Endpoints

### POST /api/auth.php
Exchange Firebase token for JWT

```json
{
  "firebase_token": "firebase_id_token_here"
}
```

### GET /api/data.php
Fetch all posts (requires JWT)

### POST /api/data.php
Create new post (requires JWT + admin email)

```json
{
  "title": "Post Title",
  "description": "Post description"
}
```

## Configuration

Update these files with your credentials:

1. **backend-php/config/database.php**
   - DB_HOST
   - DB_USER
   - DB_PASS
   - DB_NAME
   - JWT_SECRET
   - ADMIN_EMAILS

2. **android-app/data/ApiClient.kt**
   - BASE_URL (your Hostinger domain)

3. **android-app/app/google-services.json**
   - Firebase configuration

## Dependencies

### Android
- Firebase Auth 32.7.0
- Retrofit 2.9.0
- OkHttp 4.11.0
- Kotlin Coroutines 1.7.3

### PHP
- PHP 7.4+
- MySQL 5.7+
- cURL (for Firebase verification)

## Testing

1. Create a test admin account with email in ADMIN_EMAILS
2. Login with Firebase credentials
3. Fetch posts to verify JWT authentication
4. Create a post (admin only)
5. Verify post appears in the list

## Troubleshooting

### 401 Unauthorized
- Firebase token might be expired
- JWT token might be invalid
- Check Authorization header format: `Bearer <token>`

### 403 Forbidden
- User email not in ADMIN_EMAILS for POST requests
- Update admin email list in database.php

### Connection Errors
- Verify BASE_URL is correct
- Check Hostinger MySQL host setting
- Ensure HTTPS is enabled

## Production Deployment

- [ ] Use environment variables for secrets
- [ ] Enable HTTPS/SSL certificate
- [ ] Set strong JWT_SECRET (min 32 chars)
- [ ] Update ADMIN_EMAILS with real admin emails
- [ ] Enable database backups
- [ ] Set up monitoring/logging
- [ ] Use rate limiting on API endpoints

## License

MIT License - Feel free to use this project as a template

## Support

For issues or questions, please create a GitHub issue.
