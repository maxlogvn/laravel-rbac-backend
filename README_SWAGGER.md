# Swagger API Documentation

This project uses L5-Swagger to generate interactive API documentation.

## Installation

Packages installed:
- `darkaonline/l5-swagger` v10.1.0 - Swagger documentation generator
- `tymon/jwt-auth` v2.2.1 - JWT authentication

## Accessing Documentation

Development: http://localhost:8000/api/documentation

## Generating Documentation

```bash
# Generate docs
composer docs

# Or use artisan directly
php artisan l5-swagger:generate
```

## Authentication

This API uses JWT (JSON Web Tokens) for authentication.

### Getting a Token

**Request:**
```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "your-password"
}
```

**Response:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

### Using the Token

**Method 1: Authorization Header**
```bash
Authorization: Bearer {your-token}
```

**Method 2: Swagger UI**
1. Click the "Authorize" button (lock icon)
2. Enter: `Bearer {your-token}`
3. Click "Authorize"
4. Close the dialog

## Available Endpoints

### Public Endpoints
- `GET /api/hello` - Hello World example

### Authentication
- `POST /api/auth/login` - Get JWT token

### Protected Endpoints (require JWT)
- `GET /api/protected` - Protected data example

## Annotating Controllers

Add Swagger annotations to document your endpoints:

```php
/**
 * @OA\Get(
 *     path="/api/endpoint",
 *     summary="Endpoint description",
 *     tags={"Category"},
 *     @OA\Response(
 *         response=200,
 *         description="Success"
 *     )
 * )
 */
public function methodName()
{
    // ...
}
```

For protected endpoints:
```php
/**
 * @OA\Get(
 *     path="/api/protected",
 *     security={{"bearerAuth":{}}},
 *     // ... rest of annotation
 * )
 */
```

## Common Annotations

- `@OA\Info` - API metadata (in base Controller)
- `@OA\Get`, `@OA\Post`, etc. - HTTP methods
- `@OA\Parameter` - Query/path parameters
- `@OA\RequestBody` - Request body schema
- `@OA\Response` - Response documentation
- `@OA\Property` - Object properties
- `@OA\Tag` - Endpoint categories

## Troubleshooting

**Documentation not updating:**
```bash
php artisan l5-swagger:generate
```

**Check annotation syntax in your controllers.**

## References

- [OpenAPI Specification](https://swagger.io/specification/)
- [L5-Swagger Documentation](https://github.com/DarkaOnLine/L5-Swagger)
- [JWT-Auth Documentation](https://github.com/tymondesigns/jwt-auth)
