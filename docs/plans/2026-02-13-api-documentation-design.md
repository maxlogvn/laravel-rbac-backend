# API Documentation Design - Laravel Scribe Integration

**Date:** 2026-02-13
**Author:** Claude + User
**Status:** Approved

## Overview

Tích hợp Scribe (knuckleswtf/scribe) để tạo API documentation tự động giống FastAPI, bao gồm cả Swagger UI và ReDoc interfaces.

## Requirements

- ✅ Cả Swagger UI và ReDoc như FastAPI
- ✅ Tự động sinh documentation từ PHPDoc annotations
- ✅ Hỗ trợ authentication (Sanctum/Passport/JWT)
- ✅ Development-only dependency
- ✅ Laravel 12 compatible

## Architecture

```
┌─────────────────────────────────────────────────┐
│                   Laravel App                    │
├─────────────────────────────────────────────────┤
│  Controllers (with PHPDoc annotations)          │
│  └─ @group, @authenticated, @bodyParam, etc.   │
├─────────────────────────────────────────────────┤
│  Scribe Package                                 │
│  └─ Extract annotations & generate docs         │
├─────────────────────────────────────────────────┤
│  Generated Documentation                         │
│  ├─ public/docs/ (static HTML)                  │
│  └─ /docs (dynamic - Swagger UI)                │
│  └─ /redoc (dynamic - ReDoc)                    │
└─────────────────────────────────────────────────┘
```

## Data Flow

1. **Code Changes** → Developer writes PHPDoc in controllers
2. **Generate** → Run `php artisan scribe:generate`
3. **Extract** → Scribe scans routes, controllers, request classes
4. **Generate Outputs**:
   - `public/docs/index.html` (static docs)
   - `public/docs/collection.json` (Postman collection)
   - `public/docs/openapi.yaml` (OpenAPI spec)
5. **Access** → Browse `/docs` (Swagger UI) or `/redoc` (ReDoc)

## Components

### 1. Scribe Configuration (`config/scribe.php`)
- Authentication setup (Sanctum tokens)
- Output formats (HTML, Swagger/ReDoc)
- Route configuration
- Theme and styling

### 2. Documentation Annotations
```php
/**
 * @group User Management
 * authenticated
 * @bodyParam name string required User's name. Example: John Doe
 * @response 200 {"message": "User created"}
 */
```

### 3. Generated Files (`public/docs/`)
- `index.html` - Main documentation
- `collection.json` - Postman collection
- `openapi.yaml` - OpenAPI specification

### 4. Dynamic Routes
- `/docs` → Swagger UI (interactive testing)
- `/redoc` → ReDoc (clean reference)

### 5. Authentication Setup
- Configure `Authorization: Bearer {token}` header
- Sanctum token support in Swagger UI

## Implementation Steps

1. Install Scribe: `composer require --dev knuckleswtf/scribe`
2. Publish config: `php artisan vendor:publish --tag=scribe-config`
3. Configure authentication (Sanctum)
4. Add annotations to HelloController (example)
5. Generate docs: `php artisan scribe:generate`
6. Test UIs: Access `/docs` and `/redoc`
7. Optional: Add composer scripts for auto-regenerate

## Error Handling

### Documentation Errors
- **Missing annotations** → Generate with basic info
- **Invalid PHPDoc** → Console warning, no crash
- **Failed response capture** → Fallback to @response examples

### Runtime Errors
- Docs routes separate from API routes
- Dev dependency only
- No impact on production if docs fail

## Testing Strategy

1. **Verify Generated Docs**
   - Check `/docs` accessible
   - Test Swagger UI renders correctly
   - Test ReDoc renders correctly
   - Validate OpenAPI spec

2. **Test Authentication**
   - Verify "Authorize" button in Swagger UI
   - Test with Sanctum token

3. **Example Test**
   ```php
   it('generates documentation', function () {
       $this->get('/docs')->assertStatus(200);
       $this->get('/redoc')->assertStatus(200);
   });
   ```

4. **Workflow Testing**
   - Change annotation
   - Re-run `scribe:generate`
   - Verify docs update

## Success Criteria

- [ ] Swagger UI accessible at `/docs`
- [ ] ReDoc accessible at `/redoc`
- [ ] HelloController documentation generated correctly
- [ ] Authentication button working in Swagger UI
- [ ] OpenAPI spec valid
- [ ] Postman collection export working
- [ ] Tests passing

## Alternatives Considered

### L5 Swagger (Rejected)
- ❌ Only Swagger UI, no ReDoc
- ❌ More manual annotation work
- ❌ Less automated

### Custom OpenAPI (Rejected)
- ❌ High maintenance burden
- ❌ Manual spec management
- ❌ Doesn't meet "auto from code" requirement

## Notes

- Scribe is dev-only dependency (require-dev)
- Regenerate docs after API changes
- Can add `scribe:generate` to deployment pipeline
