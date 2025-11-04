# API Comparison: Staff vs Client

## Overview

Dokumentasi ini membandingkan implementasi API antara Staff dan Client untuk memastikan konsistensi dan kelengkapan fitur.

## Files Created/Modified

### Client API Files

✅ **Created:**

1. `app/Http/Resources/ClientResource.php` - Resource transformer untuk Client
2. `app/Http/Controllers/API/ClientController.php` - Controller untuk Client API
3. `CLIENT_API_DOCUMENTATION.md` - Dokumentasi lengkap Client API

✅ **Modified:**

1. `routes/api.php` - Menambahkan routes untuk Client API

## Feature Comparison

| Feature                  | Staff API          | Client API          | Status      |
| ------------------------ | ------------------ | ------------------- | ----------- |
| **Resource Transformer** | ✅ StaffResource   | ✅ ClientResource   | ✅ Complete |
| **API Controller**       | ✅ StaffController | ✅ ClientController | ✅ Complete |
| **Routes Registration**  | ✅ `/api/staff`    | ✅ `/api/clients`   | ✅ Complete |
| **Authentication**       | ✅ Sanctum         | ✅ Sanctum          | ✅ Complete |
| **CRUD Operations**      |                    |                     |             |
| - List (Index)           | ✅                 | ✅                  | ✅ Complete |
| - Create (Store)         | ✅                 | ✅                  | ✅ Complete |
| - Read (Show)            | ✅                 | ✅                  | ✅ Complete |
| - Update                 | ✅                 | ✅                  | ✅ Complete |
| - Delete                 | ✅                 | ✅                  | ✅ Complete |
| **Advanced Features**    |                    |                     |             |
| - Pagination             | ✅                 | ✅                  | ✅ Complete |
| - Search                 | ✅                 | ✅                  | ✅ Complete |
| - Filtering              | ✅                 | ✅                  | ✅ Complete |
| - Sorting                | ✅                 | ✅                  | ✅ Complete |
| - Soft Delete            | ✅                 | ✅                  | ✅ Complete |
| - Restore                | ❌                 | ✅                  | ⭐ Enhanced |
| - Force Delete           | ❌                 | ✅                  | ⭐ Enhanced |
| - Relationship Loading   | ✅                 | ✅                  | ✅ Complete |

## API Endpoints

### Staff API

```
GET     /api/staff              - List all staff
POST    /api/staff              - Create new staff
GET     /api/staff/{id}         - Get single staff
PUT     /api/staff/{id}         - Update staff
DELETE  /api/staff/{id}         - Delete staff
```

### Client API (with enhancements)

```
GET     /api/clients            - List all clients
POST    /api/clients            - Create new client
GET     /api/clients/{id}       - Get single client
PUT     /api/clients/{id}       - Update client
DELETE  /api/clients/{id}       - Soft delete client
POST    /api/clients/{id}/restore   - Restore deleted client ⭐
DELETE  /api/clients/{id}/force     - Force delete client ⭐
```

## Resource Structure

### StaffResource

```php
- Basic fields (name, email, phone, etc.)
- Department relationship (whenLoaded)
- Position relationship (whenLoaded)
- Clients relationship (whenLoaded)
- Trainings relationship (whenLoaded)
- Timestamps (ISO 8601)
- Meta version
```

### ClientResource

```php
- Basic fields (code, company_name, phone, etc.)
- Owner information
- Tax reporting fields (PPh, PPN, SPT)
- MoUs relationship (whenLoaded)
- Staff relationship (whenLoaded)
- Timestamps (ISO 8601)
- Meta version
```

## Search & Filter Capabilities

### Staff API Search Fields

-   name
-   email
-   phone
-   no_ktp

### Staff API Filters

-   is_active (boolean)
-   department_id
-   position_id

### Client API Search Fields

-   company_name
-   code
-   owner_name
-   phone
-   npwp

### Client API Filters

-   status
-   type
-   grade
-   jenis_wp

## Validation Rules

### Staff Store/Update

-   Menggunakan FormRequest classes:
    -   `StaffStoreRequest`
    -   `StaffUpdateRequest`

### Client Store/Update

-   Inline validation dalam controller
-   Required: `code` (unique), `company_name`
-   Optional: semua field lainnya
-   Max length: 255 untuk string fields

## Response Format

Both APIs return consistent JSON structure:

```json
{
  "data": { ... },
  "meta": {
    "version": "1.0.0"
  }
}
```

For collections (index):

```json
{
  "data": [ ... ],
  "links": { ... },
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "version": "1.0.0"
  }
}
```

## Enhancements in Client API

Client API includes additional features not present in Staff API:

1. **Restore Endpoint**
    - `POST /api/clients/{id}/restore`
    - Mengembalikan client yang telah di-soft delete
2. **Force Delete Endpoint**
    - `DELETE /api/clients/{id}/force`
    - Hapus permanen client dari database

These features leverage Laravel's SoftDeletes trait effectively.

## Authentication

Both APIs use Laravel Sanctum for authentication:

```bash
# Login to get token
POST /api/auth/login
{
  "email": "user@example.com",
  "password": "password"
}

# Use token in subsequent requests
Authorization: Bearer {token}
Accept: application/json
```

## Error Handling

Both APIs return consistent error responses:

-   **401 Unauthorized** - Missing or invalid token
-   **403 Forbidden** - Insufficient permissions
-   **404 Not Found** - Resource not found
-   **422 Validation Error** - Invalid input data
-   **500 Server Error** - Internal server error

## Testing

Both APIs can be tested using:

1. **Postman** - Import collection and set bearer token
2. **cURL** - Command line HTTP client
3. **PHPUnit** - Unit and feature tests (recommended)

Example test command:

```bash
php artisan test --filter=ClientApiTest
```

## Documentation

-   **Staff API**: Not yet documented separately
-   **Client API**: Complete documentation in `CLIENT_API_DOCUMENTATION.md`

## Next Steps / Recommendations

1. ✅ Create FormRequest classes for Client validation
2. ✅ Add restore/force delete endpoints to Staff API for consistency
3. ✅ Create comprehensive PHPUnit tests for both APIs
4. ✅ Add API versioning (v1, v2, etc.)
5. ✅ Implement rate limiting for API endpoints
6. ✅ Add API response caching for better performance
7. ✅ Create separate documentation for Staff API

## Conclusion

Client API telah berhasil dibuat dengan fitur yang lengkap dan bahkan lebih advanced dari Staff API dengan penambahan restore dan force delete endpoints. Kedua API menggunakan pattern yang konsisten dan mudah dipahami.

---

**Created:** 2025-11-03  
**Version:** 1.0.0
