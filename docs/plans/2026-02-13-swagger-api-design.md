# Swagger API Documentation System Design

**Date:** 2026-02-13
**Status:** Approved
**Author:** Claude + User Collaboration

## Overview

Implement Swagger API documentation system using L5-Swagger package for Laravel 12, with auto-generation from PHPDoc annotations and JWT authentication support.

## Requirements

- **UI:** Swagger UI only (interactive API testing interface)
- **Generation:** Automatic from PHPDoc annotations in controllers
- **Authentication:** JWT tokens (tymon/jwt-auth)
- **Format:** OpenAPI 3.0 specification

## Architecture

```
Developer writes PHPDoc annotations → L5-Swagger scans → Generates OpenAPI JSON → Swagger UI displays
```

### Components

1. **darkaonline/l5-swagger** - Core Swagger package
2. **tymon/jwt-auth** - JWT authentication
3. **PHPDoc annotations** - Documentation source in controllers
4. **config/l5-swagger.php** - Package configuration

### File Structure

```
backend/
├── app/
│   └── Http/
│       └── Controllers/
│           ├── Controller.php (base với annotations chung)
│           └── HelloController.php (với Swagger annotations)
├── config/
│   └── l5-swagger.php (L5-Swagger configuration)
├── storage/api-docs/ (generated OpenAPI JSON - auto)
├── routes/
│   └── api.php (API routes)
└── vendor/darkaonline/ (package files)
```

## JWT Authentication

**Setup:**
- Package: `tymon/jwt-auth`
- Token format: Bearer token in Authorization header
- Issuance: `/api/auth/login` endpoint (username/password → token)
- Expiration: 1 hour (configurable)

**Swagger Security Scheme:**
```php
@OA\SecurityScheme(
    securityScheme="bearerAuth",
    type="http",
    scheme="bearer",
    bearerFormat="JWT"
)
```

**Usage in Swagger UI:**
- Protected endpoints show "lock" icon
- User clicks "Authorize" button
- Inputs JWT token
- All requests include `Authorization: Bearer {token}` header

## Data Flow

**Development Workflow:**

1. Developer writes code with PHPDoc annotations
2. Run: `php artisan l5-swagger:generate`
3. L5-Swagger scans `app/` directory
4. Parses annotations → generates OpenAPI JSON
5. Output: `storage/api-docs/api-docs.json`
6. Access: `http://localhost:8000/api/documentation`

**Generation Options:**
- Manual: `php artisan l5-swagger:generate`
- Auto: Enable `generate_always` in config (not for production)
- Validate: `php artisan l5-swagger:generate --validate`

## Annotation Examples

**Base Controller (Common metadata):**
```php
/**
 * @OA\Info(
 *     title="My API",
 *     version="1.0.0",
 *     description="API documentation"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class Controller extends BaseController
{
    //
}
```

**Endpoint Documentation:**
```php
/**
 * Hello World
 *
 * @OA\Get(
 *     path="/api/hello",
 *     summary="Get hello message",
 *     @OA\Response(
 *         response="200",
 *         description="Success",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Hello World"),
 *             @OA\Property(property="status", type="string", example="success")
 *         )
 *     )
 * )
 */
public function __invoke(): array
{
    return ['message' => 'Hello World', 'status' => 'success'];
}
```

**Protected Endpoint:**
```php
/**
 * @OA\Get(
 *     path="/api/protected",
 *     summary="Protected endpoint",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="401", description="Unauthenticated")
 * )
 */
```

## Error Handling

**Common Issues:**

1. **Invalid annotation syntax:**
   - Symptom: PHP warning during generation
   - Fix: Check annotations, correct syntax

2. **Missing required annotations:**
   - Symptom: Incomplete OpenAPI spec
   - Fix: Add `@OA\Info`, `@OA\Scheme`

3. **JWT token expired:**
   - Symptom: 401 Unauthorized in Swagger UI
   - Fix: Refresh token via "Authorize" button

4. **Validation errors:**
   - Prevention: Use `--validate` flag
   - CI/CD: Run validation in pipeline

**Common Mistakes:**
- Missing `@OA\` prefix
- Invalid JSON in examples
- Duplicate operation IDs
- Missing required fields

## Testing Strategy

**1. Unit Tests (Pest):**
- JWT authentication endpoints
- API endpoint responses
- Token generation

**2. Documentation Tests:**
```php
it('generates swagger documentation', function () {
    expect(storage_path('api-docs/api-docs.json'))->toBeFile();
});

it('documentation route is accessible', function () {
    $this->get('/api/documentation')->assertStatus(200);
});
```

**3. Manual Testing:**
- Test "Try it out" button
- Verify JWT authorization flow
- Check request/response examples

**4. Validation Testing:**
```bash
php artisan l5-swagger:generate --validate
```

**Test Files:**
```
tests/
├── Feature/
│   ├── HelloWorldApiTest.php
│   ├── JwtAuthTest.php
│   └── SwaggerDocumentationTest.php
```

## Implementation Plan

See separate implementation plan created via writing-plans skill.

## Success Criteria

- ✅ Swagger UI accessible at `/api/documentation`
- ✅ OpenAPI JSON auto-generated from PHPDoc
- ✅ JWT authentication integrated
- ✅ All tests passing (unit + documentation)
- ✅ Annotations validated without errors
- ✅ Documentation matches actual API behavior

## References

- L5-Swagger: https://github.com/DarkaOnLine/L5-Swagger
- JWT-Auth: https://github.com/tymondesigns/jwt-auth
- OpenAPI 3.0 Spec: https://swagger.io/specification/
